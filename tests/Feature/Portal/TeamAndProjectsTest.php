<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\TeamInvite;
use App\Models\Order;
use App\Models\OrderChatMember;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class TeamAndProjectsTest extends PortalTestCase
{
    private function staff(array $permissions = [], string $email = 'sam@example.test', string $name = 'Sam'): User
    {
        $role = Role::firstOrCreate(['slug' => 'r-' . md5(implode(',', $permissions))], ['name' => 'Role ' . count($permissions) . md5(implode(',', $permissions)), 'permissions' => $permissions]);

        return User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => $name, 'last_name' => 'Staff', 'email' => $email, 'password' => bcrypt('Secret-pass-1'), 'is_active' => true]);
    }

    /* ------------------------------------------------------------ roles + permissions */

    public function test_staff_sign_in_and_are_limited_by_their_role(): void
    {
        $staff = $this->staff(['customers.view']);

        $this->post('/admin/login', ['email' => 'sam@example.test', 'password' => 'Secret-pass-1'])->assertRedirect(route('admin.tasks.mine'));   // no dashboard permission: lands on their own work
        $this->get('/admin/customers')->assertOk();
        $this->get('/admin/customers/create')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/blog/posts')->assertForbidden();
        $this->get('/admin/team/members')->assertForbidden();
        $this->get('/admin/inbox')->assertForbidden();
        $this->get('/account')->assertRedirect();                       // not a customer
    }

    public function test_super_admin_still_has_everything_and_staff_cannot_use_customer_login(): void
    {
        $this->actingAs($this->admin());
        foreach (['/admin/dashboard', '/admin/customers', '/admin/team/members', '/admin/team/roles', '/admin/projects', '/admin/tasks'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_orders_are_scoped_to_permission_or_chat_membership(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        $order->update(['status' => 'active']);
        $other = $this->order($customer, $admin, ['title' => 'Other job']);
        $other->update(['status' => 'active']);

        $member = $this->staff([], 'm@example.test', 'Mia');
        $this->actingAs($member);
        $this->get('/admin/orders/' . $order->number)->assertForbidden();             // not in the chat yet

        OrderChatMember::create(['order_id' => $order->id, 'user_id' => $member->id, 'added_by' => $admin->id]);
        $this->get('/admin/orders/' . $order->number)->assertOk()->assertDontSee('Download PDF');
        $this->get('/admin/orders/' . $other->number)->assertForbidden();
        $this->get('/admin/orders')->assertOk()->assertSee('Riverside House')->assertSee('My chats');
        $this->get('/portal/orders/' . $order->number . '/messages')->assertOk();     // chat feed
        $this->get('/portal/orders/' . $other->number . '/messages')->assertForbidden();
        $this->get('/portal/orders/' . $order->number . '/invoice')->assertForbidden();   // no billing access

        $wide = $this->staff(['orders.view'], 'w@example.test', 'Wes');
        $this->actingAs($wide)->get('/admin/orders/' . $other->number)->assertOk();
    }

    public function test_only_permitted_staff_can_change_orders(): void
    {
        $admin = $this->admin();
        $order = $this->order($this->customer(), $admin);

        $viewer = $this->staff(['orders.view']);
        $this->actingAs($viewer)->get('/admin/orders/' . $order->number . '/edit')->assertForbidden();
        $this->post('/admin/orders/' . $order->number . '/send')->assertForbidden();
        $this->delete('/admin/orders/' . $order->number)->assertForbidden();

        $editor = $this->staff(['orders.view', 'orders.edit', 'orders.delete'], 'e@example.test', 'Eve');
        $this->actingAs($editor)->get('/admin/orders/' . $order->number . '/edit')->assertOk();
        $this->delete('/admin/orders/' . $order->number)->assertRedirect();
    }

    public function test_admin_manages_roles_and_unknown_permissions_are_dropped(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/team/roles/create')->assertOk()->assertSee('Customers');
        $this->post('/admin/team/roles', ['name' => 'Reviewer', 'color' => '#336699', 'permissions' => ['orders.view', 'chat.access_all', 'hack.everything']])->assertRedirect(route('admin.team.roles.index'));
        $role = Role::where('name', 'Reviewer')->firstOrFail();
        $this->assertSame(['orders.view', 'chat.access_all'], $role->permissionList());

        $this->post('/admin/team/roles', ['name' => 'Reviewer'])->assertSessionHasErrors('name');   // names are unique
        $this->put('/admin/team/roles/' . $role->id, ['name' => 'Reviewer', 'permissions' => ['customers.view']])->assertRedirect();
        $this->assertSame(['customers.view'], $role->fresh()->permissionList());

        $this->staff([], 'x@example.test');
        $used = User::where('email', 'x@example.test')->first()->teamRole;
        $this->delete('/admin/team/roles/' . $used->id)->assertSessionHas('error');             // still has a person
        $this->assertDatabaseHas('roles', ['id' => $used->id]);
        $this->delete('/admin/team/roles/' . $role->id)->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_role_change_applies_immediately(): void
    {
        $staff = $this->staff(['customers.view']);
        $this->actingAs($staff)->get('/admin/customers')->assertOk();
        $staff->teamRole->update(['permissions' => []]);
        $this->actingAs(User::find($staff->id))->get('/admin/customers')->assertForbidden();
    }

    /* ------------------------------------------------------------ team members */

    public function test_admin_adds_a_team_member_who_sets_their_own_password(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $role = Role::create(['name' => 'Designer', 'slug' => 'designer-x', 'permissions' => ['tasks.create']]);

        $this->post('/admin/team/members', ['first_name' => 'Nina', 'last_name' => 'Lee', 'email' => 'Nina@Example.test', 'role_id' => $role->id, 'send_invite' => 1])->assertRedirect(route('admin.team.users.index'));
        $nina = User::where('email', 'nina@example.test')->firstOrFail();
        $this->assertSame('staff', $nina->role);
        $this->assertSame($role->id, $nina->role_id);

        $link = null;
        Mail::assertSent(TeamInvite::class, function (TeamInvite $m) use (&$link) { $link = $m->link; return $m->hasTo('nina@example.test'); });
        parse_str(parse_url($link, PHP_URL_QUERY), $q);
        $token = basename(parse_url($link, PHP_URL_PATH));
        $this->post('/admin/logout');

        $this->post('/admin/password/reset', ['token' => $token, 'email' => 'nina@example.test', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post('/admin/password/reset', ['token' => $token, 'email' => 'nina@example.test', 'password' => 'Brand-new-9', 'password_confirmation' => 'Brand-new-9'])->assertRedirect(route('admin.login'));
        $this->post('/admin/login', ['email' => 'nina@example.test', 'password' => 'Brand-new-9'])->assertRedirect();
        $this->assertAuthenticatedAs($nina->fresh());
    }

    public function test_switched_off_staff_cannot_sign_in_and_customers_get_no_reset_link(): void
    {
        Mail::fake();
        $staff = $this->staff(['customers.view']);
        $staff->update(['is_active' => false]);
        $this->post('/admin/login', ['email' => 'sam@example.test', 'password' => 'Secret-pass-1'])->assertSessionHasErrors('email');

        $this->customer();
        $this->post('/admin/forgot-password', ['email' => 'client@example.test'])->assertSessionHas('success');   // same answer, nothing sent
        Mail::assertNothingSent();

        $active = $this->staff(['customers.view'], 'act@example.test');
        $this->post('/admin/forgot-password', ['email' => 'act@example.test'])->assertSessionHas('success');
        Mail::assertSent(TeamInvite::class, fn ($m) => $m->hasTo('act@example.test') && ! $m->invite);
    }

    public function test_staff_cannot_switch_themselves_off_or_edit_the_team_without_permission(): void
    {
        $manager = $this->staff(['team.view', 'team.manage_users'], 'mgr@example.test', 'Max');
        $this->actingAs($manager);
        $this->get('/admin/team/members')->assertOk();
        $this->put('/admin/team/members/' . $manager->id, ['first_name' => 'Max', 'email' => 'mgr@example.test', 'role_id' => $manager->role_id])->assertSessionHas('error');
        $this->assertTrue($manager->fresh()->is_active);
        $this->get('/admin/team/roles')->assertForbidden();
    }

    /* ------------------------------------------------------------ projects + tasks */

    public function test_project_and_task_flow_with_assignee_limits(): void
    {
        Mail::fake();
        $lead = $this->staff(['projects.create', 'tasks.create'], 'lead@example.test', 'Lena');
        $worker = $this->staff([], 'w1@example.test', 'Will');
        $outsider = $this->staff([], 'o@example.test', 'Omar');

        $this->actingAs($lead)->post('/admin/projects', ['name' => 'Riverside', 'member_ids' => [$lead->id, $worker->id], 'lead_id' => $lead->id])->assertRedirect();
        $project = Project::where('name', 'Riverside')->firstOrFail();
        $this->assertSame('lead', $project->members()->where('users.id', $lead->id)->first()->pivot->role);

        $res = $this->postJson('/admin/projects/' . $project->id . '/tasks', ['title' => 'Model the facade', 'assignee_ids' => [$worker->id], 'due_date' => now()->addDays(2)->toDateString()])->assertOk();
        $task = Task::findOrFail($res->json('id'));
        Mail::assertSent(\App\Mail\Portal\TaskAssigned::class, fn ($m) => $m->hasTo('w1@example.test'));

        // the assignee can move the task along but not rewrite it
        $this->actingAs($worker);
        $this->patchJson('/admin/tasks/' . $task->id, ['status' => 'in_progress'])->assertOk()->assertJson(['status' => 'in_progress']);
        $this->patchJson('/admin/tasks/' . $task->id, ['title' => 'Hacked'])->assertForbidden();
        $this->postJson('/admin/tasks/' . $task->id . '/comments', ['body' => 'Started today'])->assertOk();
        $this->get('/admin/tasks')->assertOk()->assertSee('Model the facade');

        // someone outside the project cannot see it
        $this->actingAs($outsider);
        $this->get('/admin/tasks/' . $task->id)->assertForbidden();
        $this->get('/admin/projects/' . $project->id)->assertForbidden();
        $this->get('/admin/projects')->assertOk()->assertDontSee('Riverside');

        // the project lead can edit everything and finish the task
        $this->actingAs($lead);
        $this->patchJson('/admin/tasks/' . $task->id, ['title' => 'Model the facade v2', 'priority' => 'high'])->assertOk();
        $this->postJson('/admin/tasks/' . $task->id . '/move', ['status' => 'done', 'index' => 0])->assertOk();
        $task->refresh();
        $this->assertSame('done', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertGreaterThanOrEqual(3, $task->comments()->where('kind', 'event')->count());   // history is written for people
        $this->get('/admin/projects/' . $project->id)->assertOk()->assertSee('Model the facade v2')->assertSee('100%');
    }

    public function test_board_move_reorders_within_a_column(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $project = Project::create(['name' => 'P', 'created_by' => $admin->id]);
        $a = $project->tasks()->create(['title' => 'A', 'status' => 'todo', 'position' => 0]);
        $b = $project->tasks()->create(['title' => 'B', 'status' => 'todo', 'position' => 1]);
        $c = $project->tasks()->create(['title' => 'C', 'status' => 'review', 'position' => 0]);

        $this->postJson('/admin/tasks/' . $c->id . '/move', ['status' => 'todo', 'index' => 1])->assertOk();
        $this->assertSame(['A', 'C', 'B'], Task::where('status', 'todo')->orderBy('position')->pluck('title')->all());
    }

    public function test_deleting_a_project_removes_its_tasks_and_needs_permission(): void
    {
        $admin = $this->admin();
        $project = Project::create(['name' => 'Gone', 'created_by' => $admin->id]);
        $project->tasks()->create(['title' => 'T']);

        $this->actingAs($this->staff(['projects.view']))->delete('/admin/projects/' . $project->id)->assertForbidden();
        $this->actingAs($admin)->delete('/admin/projects/' . $project->id)->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_customer_can_only_see_shared_project_tasks(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $other = $this->customer('other@example.test');
        $project = Project::create(['name' => 'Shared', 'customer_id' => $customer->id, 'visible_to_customer' => true, 'created_by' => $admin->id]);
        $task = $project->tasks()->create(['title' => 'Visible task']);
        $hidden = Project::create(['name' => 'Private', 'customer_id' => $customer->id, 'visible_to_customer' => false, 'created_by' => $admin->id])->tasks()->create(['title' => 'Secret task']);

        $this->assertTrue($customer->can('view', $task));
        $this->assertFalse($customer->can('view', $hidden));
        $this->assertFalse($other->can('view', $task));
        $this->assertTrue(Project::visibleTo($customer)->whereKey($project->id)->exists());
        $this->assertFalse(Project::visibleTo($customer)->where('name', 'Private')->exists());
    }
}
