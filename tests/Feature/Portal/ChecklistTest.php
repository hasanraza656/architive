<?php

namespace Tests\Feature\Portal;

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;

/** To-do checklists inside tasks and projects. */
class ChecklistTest extends PortalTestCase
{
    private function staff(array $permissions = [], string $email = 'sam@example.test', string $name = 'Sam'): User
    {
        $role = Role::firstOrCreate(['slug' => 'r-' . md5(implode(',', $permissions))], ['name' => 'Role ' . md5(implode(',', $permissions)), 'permissions' => $permissions]);

        return User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => $name, 'last_name' => 'Staff', 'email' => $email, 'password' => bcrypt('Secret-pass-1'), 'is_active' => true]);
    }

    private function task(User $admin): Task
    {
        $p = Project::create(['name' => 'Alpha', 'created_by' => $admin->id]);

        return Task::create(['project_id' => $p->id, 'title' => 'Draft the plan', 'status' => 'todo', 'created_by' => $admin->id, 'position' => 0]);
    }

    public function test_lines_can_be_added_in_order_ticked_by_a_person_edited_and_removed(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $this->actingAs($admin);

        $a = $this->postJson("/admin/checklists/task/{$task->id}", ['body' => '  Measure the site  '])->assertOk()->json();
        $this->assertSame(['Measure the site'], collect($a['items'])->pluck('body')->all());
        $this->postJson("/admin/checklists/task/{$task->id}", ['body' => 'Trace the sketch'])->assertOk();
        $first = $a['created'][0];

        // goes in below the first line, and at the very top with after = 0
        $this->postJson("/admin/checklists/task/{$task->id}", ['body' => 'Between', 'after' => $first])->assertOk();
        $top = $this->postJson("/admin/checklists/task/{$task->id}", ['body' => 'Very first', 'after' => 0])->assertOk()->json();
        $this->assertSame(['Very first', 'Measure the site', 'Between', 'Trace the sketch'], collect($top['items'])->pluck('body')->all());

        $r = $this->patchJson("/admin/checklist-items/{$first}", ['done' => true])->assertOk()->json();
        $item = collect($r['items'])->firstWhere('id', $first);
        $this->assertTrue($item['done']);
        $this->assertSame($admin->name, $item['by']['name']);
        $this->assertNotNull($item['at']);
        $this->assertSame([1, 4], [$r['done'], $r['total']]);

        $r = $this->patchJson("/admin/checklist-items/{$first}", ['done' => false, 'body' => 'Measure the site twice'])->assertOk()->json();
        $item = collect($r['items'])->firstWhere('id', $first);
        $this->assertFalse($item['done']);
        $this->assertNull($item['by']);
        $this->assertSame('Measure the site twice', $item['body']);

        $this->deleteJson("/admin/checklist-items/{$first}")->assertOk()->assertJson(['total' => 3]);
        $this->patchJson("/admin/checklist-items/" . $a['created'][0], ['body' => 'x'])->assertNotFound();
        $this->assertSame([0, 1, 2], ChecklistItem::where('owner_type', 'task')->orderBy('position')->pluck('position')->all());     // order stays tidy
    }

    public function test_a_pasted_list_adds_many_lines_and_empty_lines_are_refused(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $this->actingAs($admin);

        $res = $this->postJson("/admin/checklists/task/{$task->id}", ['bodies' => ['One', '', 'Two', 'Three']])->assertOk()->json();
        $this->assertSame(['One', 'Two', 'Three'], collect($res['items'])->pluck('body')->all());
        $this->assertCount(3, $res['created']);

        $this->postJson("/admin/checklists/task/{$task->id}", ['body' => '   '])->assertStatus(422);
        $this->patchJson('/admin/checklist-items/' . $res['created'][0], ['body' => '   '])->assertStatus(422);
    }

    public function test_the_list_can_be_reordered_and_the_version_only_changes_when_something_changed(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $this->actingAs($admin);
        $made = $this->postJson("/admin/checklists/task/{$task->id}", ['bodies' => ['A', 'B', 'C']])->json('created');

        $first = $this->getJson("/admin/checklists/task/{$task->id}")->assertOk()->json();
        $this->getJson("/admin/checklists/task/{$task->id}?v={$first['v']}")->assertOk()->assertJson(['changed' => false]);

        $res = $this->postJson("/admin/checklists/task/{$task->id}/reorder", ['ids' => [$made[2], $made[0], $made[1]]])->assertOk()->json();
        $this->assertSame(['C', 'A', 'B'], collect($res['items'])->pluck('body')->all());
        $this->assertNotSame($first['v'], $res['v']);

        $this->patchJson("/admin/checklist-items/{$made[0]}", ['done' => true]);
        $this->assertTrue($this->getJson("/admin/checklists/task/{$task->id}?v={$res['v']}")->json('changed'));
    }

    public function test_projects_have_their_own_checklist_and_it_is_cleaned_up_with_the_project(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $project = $task->project;
        $this->actingAs($admin);

        $this->postJson("/admin/checklists/project/{$project->id}", ['body' => 'Kick-off call'])->assertOk()->assertJson(['total' => 1]);
        $this->postJson("/admin/checklists/task/{$task->id}", ['body' => 'Task step'])->assertOk();
        $this->assertSame(2, ChecklistItem::count());

        $project->delete();
        $this->assertSame(0, ChecklistItem::count());
    }

    public function test_who_may_tick_follows_the_task_rules(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $line = ChecklistItem::create(['owner_type' => 'task', 'owner_id' => $task->id, 'body' => 'Step', 'position' => 0, 'created_by' => $admin->id]);

        $outsider = $this->staff(['tasks.view'], 'o@example.test', 'Olly');           // can see tasks, but is not assigned and may not edit
        $this->actingAs($outsider);
        $this->getJson("/admin/checklists/task/{$task->id}")->assertOk();
        $this->patchJson("/admin/checklist-items/{$line->id}", ['done' => true])->assertForbidden();
        $this->postJson("/admin/checklists/task/{$task->id}", ['body' => 'Nope'])->assertForbidden();

        $task->assignees()->sync([$outsider->id]);                                       // now it is theirs to work on
        $this->patchJson("/admin/checklist-items/{$line->id}", ['done' => true])->assertOk();
        $this->assertSame($outsider->id, $line->fresh()->done_by);

        $stranger = $this->staff([], 'x@example.test', 'Xena');
        $this->actingAs($stranger)->getJson("/admin/checklists/task/{$task->id}")->assertForbidden();

        $this->actingAs($this->customer())->getJson("/admin/checklists/task/{$task->id}")->assertForbidden();     // customers never reach the team area
    }

    public function test_the_panel_the_project_tab_and_the_board_card_show_the_checklist(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $task->project->members()->attach($admin->id, ['role' => 'lead']);
        ChecklistItem::create(['owner_type' => 'task', 'owner_id' => $task->id, 'body' => 'Step one', 'position' => 0, 'is_done' => true, 'done_by' => $admin->id, 'done_at' => now()]);
        ChecklistItem::create(['owner_type' => 'task', 'owner_id' => $task->id, 'body' => 'Step two', 'position' => 1]);
        $this->actingAs($admin);

        $this->get("/admin/tasks/{$task->id}/panel")->assertOk()->assertSee('data-checklist', false)->assertSee('Step one');
        $this->get("/admin/tasks/{$task->id}")->assertOk()->assertSee('data-checklist', false);
        $page = $this->get("/admin/projects/{$task->project_id}")->assertOk()->assertSee('data-tab="checklist"', false)->assertSee('Project to-dos');
        $page->assertSee('data-cl-card="task:' . $task->id . '"', false)->assertSee('1/2');
    }

    public function test_ticking_a_line_changes_the_board_version_so_other_tabs_refresh_their_cards(): void
    {
        $admin = $this->admin();
        $task = $this->task($admin);
        $line = ChecklistItem::create(['owner_type' => 'task', 'owner_id' => $task->id, 'body' => 'Step', 'position' => 0]);
        $this->actingAs($admin);

        $v = $this->getJson("/admin/projects/{$task->project_id}/live")->json('v');
        $this->patchJson("/admin/checklist-items/{$line->id}", ['done' => true])->assertOk();

        $next = $this->getJson("/admin/projects/{$task->project_id}/live?v={$v}")->json();
        $this->assertTrue($next['changed']);
        $this->assertStringContainsString('1/1', implode('', $next['cols']));
    }
}
