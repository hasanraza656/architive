<?php

namespace Tests\Feature\Portal;

use App\Models\McpToken;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Support\Facades\Mail;

class McpTest extends PortalTestCase
{
    private string $key = '';

    private function connect(?User $user = null): User
    {
        $user ??= $this->admin();
        [, $this->key] = McpToken::issue($user, 'test');

        return $user;
    }

    private function rpc(string $method, array $params = [], ?string $key = null, bool $withId = true)
    {
        $body = ['jsonrpc' => '2.0', 'method' => $method, 'params' => (object) $params] + ($withId ? ['id' => 1] : []);

        return $this->postJson('/mcp', $body, ['Authorization' => 'Bearer ' . ($key ?? $this->key)]);
    }

    /** Call a tool and return [isError, data-or-message]. */
    private function tool(string $name, array $args = []): array
    {
        $r = $this->rpc('tools/call', ['name' => $name, 'arguments' => (object) $args])->assertOk()->json('result');
        $text = $r['content'][0]['text'];

        return [$r['isError'], $r['isError'] ? $text : json_decode($text, true)];
    }

    private function team(string $email, array $permissions): User
    {
        $role = Role::create(['name' => 'R' . $email, 'slug' => 'r-' . md5($email), 'permissions' => $permissions]);

        return User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => 'Tess', 'last_name' => 'Team', 'email' => $email, 'password' => bcrypt('x'), 'is_active' => true]);
    }

    public function test_requests_need_a_valid_key(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], ['Authorization' => 'Bearer arc_' . str_repeat('a', 48)])->assertStatus(401);
        $this->get('/mcp')->assertStatus(405);

        $admin = $this->connect();
        $this->rpc('ping')->assertOk()->assertJsonPath('id', 1);
        $this->postJson('/mcp/' . $this->key, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping'])->assertOk();      // key in the address

        McpToken::where('user_id', $admin->id)->update(['revoked_at' => now()]);
        $this->rpc('ping')->assertStatus(401);
    }

    public function test_customers_and_inactive_users_cannot_use_a_key(): void
    {
        $customer = $this->customer();
        [, $plain] = McpToken::issue($customer, 'sneaky');
        $this->rpc('ping', [], $plain)->assertStatus(401);

        $admin = $this->connect();
        $admin->update(['is_active' => false]);
        $this->rpc('ping')->assertStatus(401);
    }

    public function test_protocol_basics(): void
    {
        $this->connect();
        $init = $this->rpc('initialize', ['protocolVersion' => '2025-03-26', 'capabilities' => (object) [], 'clientInfo' => ['name' => 't', 'version' => '1']])->assertOk()->json('result');
        $this->assertSame('2025-03-26', $init['protocolVersion']);
        $this->assertArrayHasKey('tools', $init['capabilities']);

        $this->rpc('notifications/initialized', [], null, false)->assertStatus(202);
        $tools = collect($this->rpc('tools/list')->assertOk()->json('result.tools'));
        foreach (['whoami', 'create_project', 'create_task', 'update_task', 'assign_task', 'reply_to_customer', 'get_conversation', 'list_customer_messages'] as $name) {
            $this->assertTrue($tools->contains('name', $name), "missing tool $name");
        }
        $this->assertFalse($tools->contains(fn ($t) => str_contains($t['name'], 'delete')));      // nothing destructive is offered
        $this->rpc('nope/never')->assertOk()->assertJsonPath('error.code', -32601);
    }

    public function test_projects_and_tasks_can_be_created_changed_and_assigned(): void
    {
        Mail::fake();
        $admin = $this->connect();
        $sam = $this->team('sam@example.test', ['tasks.create']);

        [$err, $p] = $this->tool('create_project', ['name' => 'Riverside', 'due_date' => '2026-12-01', 'start_date' => '2026-11-01', 'member_ids' => [$sam->id]]);
        $this->assertFalse($err, json_encode($p));
        $pid = $p['project']['id'];
        $this->assertSame('active', $p['project']['status']);
        $this->assertCount(2, Project::find($pid)->members);

        [$err, $t] = $this->tool('create_task', ['project_id' => $pid, 'title' => 'Model the facade', 'priority' => 'high', 'due_date' => '2026-11-20', 'assignee_ids' => [$sam->id]]);
        $this->assertFalse($err, json_encode($t));
        $tid = $t['task']['id'];
        $this->assertSame('2026-11-20', $t['task']['due_date']);

        [$err, $t] = $this->tool('update_task', ['task_id' => $tid, 'due_date' => '2026-11-25', 'status' => 'in_progress']);
        $this->assertFalse($err, json_encode($t));
        $task = Task::find($tid);
        $this->assertSame('2026-11-25', $task->due_date->toDateString());
        $this->assertSame('in_progress', $task->status);
        $this->assertSame('high', $task->priority);                       // untouched

        [$err] = $this->tool('set_task_status', ['task_id' => $tid, 'status' => 'done']);
        $this->assertFalse($err);
        $this->assertNotNull(Task::find($tid)->completed_at);

        [$err] = $this->tool('update_task', ['task_id' => $tid, 'due_date' => null]);
        $this->assertFalse($err);
        $this->assertNull(Task::find($tid)->due_date);

        [$err, $m] = $this->tool('assign_task', ['task_id' => $tid, 'remove_user_ids' => [$sam->id], 'add_user_ids' => [$admin->id]]);
        $this->assertFalse($err, json_encode($m));
        $this->assertSame([$admin->id], Task::find($tid)->assignees()->pluck('users.id')->all());

        [$err] = $this->tool('add_task_comment', ['task_id' => $tid, 'comment' => 'Looks good']);
        $this->assertFalse($err);
        $this->assertTrue(Task::find($tid)->comments()->where('body', 'Looks good')->exists());

        [$err, $list] = $this->tool('list_tasks', ['project_id' => $pid, 'include_done' => true]);
        $this->assertFalse($err);
        $this->assertCount(1, $list['tasks']);
    }

    public function test_bad_input_changes_nothing_and_explains_why(): void
    {
        $admin = $this->connect();
        $project = Project::create(['name' => 'P', 'created_by' => $admin->id]);
        $task = Task::create(['project_id' => $project->id, 'title' => 'T', 'status' => 'todo', 'created_by' => $admin->id]);

        [$err, $m] = $this->tool('set_task_status', ['task_id' => $task->id, 'status' => 'finished']);
        $this->assertTrue($err);
        $this->assertStringContainsString('Nothing was changed', $m);
        $this->assertSame('todo', $task->fresh()->status);

        [$err] = $this->tool('update_task', ['task_id' => $task->id, 'due_date' => '20/11/2026']);
        $this->assertTrue($err);
        [$err] = $this->tool('update_task', ['task_id' => 999999, 'title' => 'x']);
        $this->assertTrue($err);
        [$err, $m] = $this->tool('assign_task', ['task_id' => $task->id, 'add_user_ids' => [424242]]);
        $this->assertTrue($err);
        $this->assertStringContainsString('not active team members', $m);
        [$err] = $this->tool('update_project', ['project_id' => $project->id, 'start_date' => '2026-12-10', 'due_date' => '2026-12-01']);
        $this->assertTrue($err);
        [$err] = $this->tool('no_such_tool');
        $this->assertTrue($err);
    }

    public function test_a_team_key_is_limited_by_the_role(): void
    {
        $this->connect($this->team('lim@example.test', []));
        [$err, $m] = $this->tool('create_project', ['name' => 'Nope']);
        $this->assertTrue($err);
        $this->assertStringContainsString('permission', $m);
        $this->assertSame(0, Project::count());

        [$err] = $this->tool('list_customers');
        $this->assertTrue($err);                                          // customers.view missing
    }

    public function test_customer_messages_can_be_read_and_answered_once(): void
    {
        Mail::fake();
        $admin = $this->connect();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        app(OrderWorkflow::class)->send($order, $admin);
        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');
        $this->actingAs($customer)->postJson("/portal/orders/{$order->number}/messages", ['body' => 'Can you move the deadline?'])->assertCreated();
        $this->app['auth']->forgetGuards();

        [$err, $in] = $this->tool('list_customer_messages');
        $this->assertFalse($err, json_encode($in));
        $this->assertSame('Can you move the deadline?', $in['messages'][0]['text']);
        $this->assertTrue($in['messages'][0]['unread']);

        [$err, $conv] = $this->tool('get_conversation', ['order_number' => $order->number]);
        $this->assertFalse($err, json_encode($conv));
        $this->assertSame('customer', $conv['messages'][0]['from_type']);

        [$err, $sent] = $this->tool('reply_to_customer', ['order_number' => $order->number, 'message' => 'Yes, new date is Friday.', 'request_id' => 'abc-1']);
        $this->assertFalse($err, json_encode($sent));
        [, $again] = $this->tool('reply_to_customer', ['order_number' => $order->number, 'message' => 'Yes, new date is Friday.', 'request_id' => 'abc-1']);
        $this->assertTrue($again['duplicate_request_ignored']);
        $this->assertSame(1, $order->messages()->where('user_id', $admin->id)->where('body', 'Yes, new date is Friday.')->count());

        [, $in] = $this->tool('list_customer_messages');                  // answering marks the conversation read
        $this->assertCount(0, $in['messages']);

        [$err] = $this->tool('reply_to_customer', ['order_number' => 'ARC-9999', 'message' => 'hi']);
        $this->assertTrue($err);
        [$err] = $this->tool('reply_to_customer', ['order_number' => $order->number, 'message' => '   ']);
        $this->assertTrue($err);
    }

    public function test_keys_are_managed_on_the_system_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/system')->assertOk()->assertSee('Connect Claude');
        $this->post('/admin/system/mcp-keys', ['name' => 'Laptop'])->assertRedirect()->assertSessionHas('mcp_key');
        $token = McpToken::first();
        $this->assertSame(64, strlen($token->token_hash));                // only a hash is stored

        $other = $this->team('o@example.test', ['team.view']);
        $this->actingAs($other)->delete('/admin/system/mcp-keys/' . $token->id)->assertRedirect();
        $this->assertNull($token->fresh()->revoked_at);                   // not an admin: refused

        $this->actingAs($admin)->delete('/admin/system/mcp-keys/' . $token->id)->assertRedirect();
        $this->assertNotNull($token->fresh()->revoked_at);
    }
}
