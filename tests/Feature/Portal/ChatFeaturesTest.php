<?php

namespace Tests\Feature\Portal;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ChatFeaturesTest extends PortalTestCase
{
    private function team(string $email = 'sam@example.test', array $permissions = ['chat.manage_members']): User
    {
        $role = Role::firstOrCreate(['slug' => 'chat-' . md5(implode(',', $permissions))], ['name' => 'Chat ' . md5(implode(',', $permissions)), 'permissions' => $permissions]);

        return User::create(['role' => 'staff', 'role_id' => $role->id, 'first_name' => ucfirst(strtok($email, '@')), 'last_name' => 'Staff', 'email' => $email, 'password' => bcrypt('Secret-pass-1'), 'is_active' => true]);
    }

    /** @return array{0: Order, 1: User, 2: User} */
    private function activeOrder(): array
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        app(OrderWorkflow::class)->send($order, $admin);
        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');
        Mail::fake();

        return [$order->fresh(), $admin, $customer];
    }

    private function url(Order $order, string $tail = 'messages'): string
    {
        return "/portal/orders/{$order->number}/{$tail}";
    }

    public function test_reactions_toggle_and_show_in_the_feed(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $id = $this->actingAs($customer)->postJson($this->url($order), ['body' => 'Look'])->json('id');

        $this->actingAs($admin)->postJson($this->url($order, "messages/{$id}/react"), ['emoji' => '👍'])->assertOk();
        $msg = $this->actingAs($customer)->getJson($this->url($order))->json('messages.0');
        $this->assertSame('👍', $msg['reactions'][0]['emoji']);
        $this->assertSame(1, $msg['reactions'][0]['count']);

        $this->actingAs($admin)->postJson($this->url($order, "messages/{$id}/react"), ['emoji' => '👍'])->assertOk();   // second click removes it
        $this->assertEmpty($this->actingAs($customer)->getJson($this->url($order))->json('messages.0.reactions'));
        $this->actingAs($admin)->postJson($this->url($order, "messages/{$id}/react"), [])->assertStatus(422);
    }

    public function test_replies_make_a_thread_that_stays_out_of_the_main_list(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $root = $this->actingAs($customer)->postJson($this->url($order), ['body' => 'Question'])->json('id');
        $this->actingAs($admin)->postJson($this->url($order), ['body' => 'Answer', 'reply_to' => $root])->assertCreated();

        $feed = $this->actingAs($customer)->getJson($this->url($order))->json();
        $this->assertCount(1, $feed['messages']);                       // reply is not in the main flow
        $this->assertSame(1, $feed['messages'][0]['thread']['count']);

        $thread = $this->actingAs($customer)->getJson($this->url($order, "messages/{$root}/thread"))->assertOk()->json();
        $this->assertSame('Question', $thread['root']['body']);
        $this->assertSame('Answer', $thread['replies'][0]['body']);
    }

    public function test_tags_are_validated_and_customers_only_open_what_they_may_see(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $project = Project::create(['name' => 'Riverside', 'created_by' => $admin->id, 'customer_id' => $customer->id, 'order_id' => $order->id, 'visible_to_customer' => true]);
        $shown = Task::create(['project_id' => $project->id, 'title' => 'Shown task', 'status' => 'todo', 'created_by' => $admin->id]);
        $secret = Project::create(['name' => 'Internal', 'created_by' => $admin->id, 'customer_id' => $customer->id, 'order_id' => $order->id, 'visible_to_customer' => false]);
        $hidden = Task::create(['project_id' => $secret->id, 'title' => 'Internal task', 'status' => 'todo', 'created_by' => $admin->id]);

        $body = "Ping @[Wrong Name](u:{$customer->id}) about #[Fake](t:{$shown->id}) and #[Internal](t:{$hidden->id}) and @[Ghost](u:99999)";
        $this->actingAs($admin)->postJson($this->url($order), ['body' => $body])->assertCreated();

        $text = $this->actingAs($admin)->getJson($this->url($order))->json('messages.0.body');
        $this->assertStringContainsString("@[{$customer->name}](u:{$customer->id})", $text);   // labels rewritten from real data
        $this->assertStringContainsString('#[Shown task]', $text);
        $this->assertStringNotContainsString('(u:99999)', $text);                                  // unknown user tag is not kept as a tag

        $this->actingAs($customer)->get("/portal/cards/tasks/{$shown->id}")->assertOk()->assertSee('Shown task');
        $this->actingAs($customer)->get("/portal/cards/tasks/{$hidden->id}")->assertForbidden();
        $this->actingAs($customer)->get("/portal/cards/projects/{$project->id}")->assertOk();
        $this->actingAs($customer)->get("/portal/cards/projects/{$secret->id}")->assertForbidden();
    }

    public function test_team_members_can_be_added_and_removed_but_customers_cannot_manage(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $mia = $this->team('mia@example.test', []);
        $this->actingAs($mia)->getJson($this->url($order))->assertForbidden();

        $this->actingAs($customer)->postJson($this->url($order, 'members'), ['user_id' => $mia->id])->assertForbidden();
        $this->actingAs($admin)->postJson($this->url($order, 'members'), ['user_id' => $customer->id])->assertStatus(422);   // only team members
        $this->actingAs($admin)->postJson($this->url($order, 'members'), ['user_id' => $mia->id])->assertOk();

        $this->actingAs($mia)->getJson($this->url($order))->assertOk();
        $this->actingAs($mia)->postJson($this->url($order), ['body' => 'Joined'])->assertCreated();
        $this->assertTrue($order->messages()->where('kind', 'system')->exists());                // "added" note in the chat

        $this->actingAs($admin)->deleteJson($this->url($order, "members/{$mia->id}"))->assertOk();
        $this->actingAs($mia)->getJson($this->url($order))->assertForbidden();
    }

    public function test_tag_search_lists_people_of_the_chat_and_visible_tasks(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $project = Project::create(['name' => 'Riverside', 'created_by' => $admin->id, 'customer_id' => $customer->id, 'order_id' => $order->id]);
        Task::create(['project_id' => $project->id, 'title' => 'Model the facade', 'status' => 'todo', 'created_by' => $admin->id]);

        $people = $this->actingAs($admin)->getJson($this->url($order, 'tags?kind=people&q=cle'))->assertOk()->json('items');
        $this->assertSame($customer->id, $people[0]['id']);

        $found = $this->actingAs($admin)->getJson($this->url($order, 'tags?q=facade'))->assertOk()->json('items');
        $this->assertContains('Model the facade', array_column($found, 'label'));
        $this->actingAs($customer)->getJson($this->url($order, 'tags?q=facade'))->assertOk();    // customers get a (filtered) list, never an error
    }

    public function test_voice_and_video_notes_and_annotated_copies_are_kept_with_their_kind(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        Storage::fake('uploads');

        $orig = $this->actingAs($customer)->postJson($this->url($order), ['files' => [UploadedFile::fake()->create('plan.pdf', 40, 'application/pdf')]])->assertCreated()->json();
        $fileId = $orig['files'][0]['id'];
        $this->assertSame('pdf', $orig['files'][0]['kind']);

        $copy = $this->actingAs($admin)->postJson($this->url($order), ['files' => [UploadedFile::fake()->create('annotated-plan.pdf', 50, 'application/pdf')], 'annotated_of' => [$fileId]])->assertCreated()->json();
        $this->assertSame($fileId, OrderFile::find($copy['files'][0]['id'])->annotated_of);

        $voice = $this->actingAs($admin)->postJson($this->url($order), ['files' => [UploadedFile::fake()->create('voice.webm', 20, 'audio/webm')], 'kinds' => ['voice'], 'durations' => [7]])->assertCreated()->json();
        $this->assertSame('voice', $voice['files'][0]['kind']);
        $this->assertSame(7, $voice['files'][0]['duration']);

        $this->actingAs($admin)->postJson($this->url($order), ['files' => [UploadedFile::fake()->create('run.exe', 10)]])->assertStatus(422);
    }

    public function test_files_support_range_requests_for_audio_and_video_scrubbing(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        $m = $this->actingAs($customer)->postJson($this->url($order), ['files' => [UploadedFile::fake()->createWithContent('clip.webm', str_repeat('a', 5000))], 'kinds' => ['video']])->assertCreated()->json();
        $url = $m['files'][0]['url'];

        $this->actingAs($admin)->get($url, ['Range' => 'bytes=0-99'])->assertStatus(206)->assertHeader('Content-Range');
        $this->actingAs($this->customer('other@example.test'))->get($url)->assertForbidden();
    }
    public function test_big_files_can_arrive_in_pieces(): void
    {
        [$order, $admin, $customer] = $this->activeOrder();
        Storage::fake('uploads');
        $id = 'T' . bin2hex(random_bytes(10));      // unique per run so leftovers of an earlier run can never interfere
        $data = random_bytes(3000);

        foreach ([0 => substr($data, 0, 1000), 1 => substr($data, 1000, 1000), 2 => substr($data, 2000)] as $n => $part) {
            $this->actingAs($customer)->postJson($this->url($order, 'chunks'), ['upload' => $id, 'index' => $n, 'chunk' => UploadedFile::fake()->createWithContent('piece', $part)])->assertOk();
        }
        $this->actingAs($customer)->postJson($this->url($order, 'chunks'), ['upload' => '../evil', 'index' => 0, 'chunk' => UploadedFile::fake()->createWithContent('piece', 'x')])->assertStatus(422);

        $m = $this->actingAs($customer)->postJson($this->url($order), ['upload_ids' => [$id], 'upload_names' => ['clip.webm'], 'upload_totals' => [3], 'upload_types' => ['video/webm'], 'kinds' => ['video']])->assertCreated()->json();
        $this->assertSame(3000, OrderFile::find($m['files'][0]['id'])->size);

        $this->actingAs($customer)->postJson($this->url($order), ['upload_ids' => ['zzzzzzzzzzzzzzzzzz'], 'upload_names' => ['a.webm'], 'upload_totals' => [2]])->assertStatus(422);   // never uploaded
    }
}
