<?php

namespace Tests\Feature\Portal;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class ProjectLiveTest extends PortalTestCase
{
    private function project(User $admin): array
    {
        $p = Project::create(['name' => 'Live', 'created_by' => $admin->id]);
        $a = Task::create(['project_id' => $p->id, 'title' => 'Alpha', 'status' => 'todo', 'created_by' => $admin->id, 'position' => 0]);
        $b = Task::create(['project_id' => $p->id, 'title' => 'Beta', 'status' => 'in_progress', 'created_by' => $admin->id, 'position' => 0]);

        return [$p, $a, $b];
    }

    public function test_the_live_endpoint_only_answers_when_something_changed(): void
    {
        $admin = $this->admin();
        [$p, $a] = $this->project($admin);
        $this->actingAs($admin);

        $first = $this->getJson("/admin/projects/{$p->id}/live")->assertOk()->json();
        $this->assertTrue($first['changed']);
        $this->assertStringContainsString('Alpha', $first['cols']['todo']);
        $this->assertStringContainsString('Beta', $first['cols']['in_progress']);
        $this->assertStringContainsString('Alpha', $first['list']);
        $this->assertSame(2, $first['total']);

        $this->getJson("/admin/projects/{$p->id}/live?v={$first['v']}")->assertOk()->assertJson(['changed' => false]);

        $this->patchJson("/admin/tasks/{$a->id}", ['status' => 'done'])->assertOk();           // a change made anywhere (board, list, panel, Claude)
        $next = $this->getJson("/admin/projects/{$p->id}/live?v={$first['v']}")->assertOk()->json();
        $this->assertTrue($next['changed']);
        $this->assertStringContainsString('Alpha', $next['cols']['done']);
        $this->assertStringNotContainsString('Alpha', $next['cols']['todo']);
        $this->assertSame(50, $next['progress']);

        $this->getJson("/admin/projects/{$p->id}/live?v={$next['v']}")->assertJson(['changed' => false]);
    }

    public function test_people_and_titles_also_change_the_version(): void
    {
        $admin = $this->admin();
        [$p, $a] = $this->project($admin);
        $this->actingAs($admin);
        $v = $this->getJson("/admin/projects/{$p->id}/live")->json('v');

        $this->patchJson("/admin/tasks/{$a->id}", ['title' => 'Alpha renamed'])->assertOk();
        $v2 = $this->getJson("/admin/projects/{$p->id}/live?v={$v}")->json();
        $this->assertTrue($v2['changed']);

        $this->patchJson("/admin/tasks/{$a->id}", ['assignee_ids' => [$admin->id]])->assertOk();
        $this->assertTrue($this->getJson("/admin/projects/{$p->id}/live?v={$v2['v']}")->json('changed'));
    }

    public function test_the_list_rows_have_a_status_picker_and_the_endpoint_is_protected(): void
    {
        $admin = $this->admin();
        [$p] = $this->project($admin);
        $this->actingAs($admin)->get("/admin/projects/{$p->id}")->assertOk()->assertSee('data-row-status', false)->assertSee('data-live-url', false);

        auth()->logout();
        $this->getJson("/admin/projects/{$p->id}/live")->assertUnauthorized();
        $this->actingAs($this->customer())->getJson("/admin/projects/{$p->id}/live")->assertStatus(403);
    }
}
