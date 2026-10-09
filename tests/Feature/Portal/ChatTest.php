<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\NewChatMessages;
use App\Models\OrderReadState;
use App\Services\Chat\ChatNotifier;
use App\Services\Chat\ChatService;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ChatTest extends PortalTestCase
{
    private function paidOrder(): array
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        app(OrderWorkflow::class)->send($order, $admin);
        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');
        Mail::fake();   // forget the invoice/payment mails

        return [$order->fresh(), $admin, $customer];
    }

    public function test_both_sides_can_post_and_poll(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();

        $this->actingAs($customer)->postJson("/portal/orders/{$order->number}/messages", ['body' => 'Hello team'])->assertCreated()->assertJsonPath('mine', true);
        $feed = $this->actingAs($admin)->getJson("/portal/orders/{$order->number}/messages?after=0")->assertOk()->json();

        $this->assertCount(1, $feed['messages']);
        $this->assertFalse($feed['messages'][0]['mine']);
        $this->assertSame('Hello team', $feed['messages'][0]['body']);

        $this->actingAs($admin)->postJson("/portal/orders/{$order->number}/messages", ['body' => 'Hi!'])->assertCreated();
        $feed = $this->actingAs($customer)->getJson("/portal/orders/{$order->number}/messages?after=1")->json();
        $this->assertCount(1, $feed['messages']);
        $this->assertGreaterThan(0, $feed['seen_up_to']);        // admin has read the customer's message
    }

    public function test_empty_messages_and_foreign_orders_are_rejected(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        $other = $this->customer('other@example.test');

        $this->actingAs($customer)->postJson("/portal/orders/{$order->number}/messages", ['body' => '  '])->assertStatus(422);
        $this->actingAs($other)->getJson("/portal/orders/{$order->number}/messages")->assertForbidden();
        $this->actingAs($other)->postJson("/portal/orders/{$order->number}/messages", ['body' => 'hi'])->assertForbidden();
    }

    public function test_attachments_are_stored_privately_and_blocked_types_rejected(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        Storage::fake('local');

        $res = $this->actingAs($customer)->post("/portal/orders/{$order->number}/messages", ['files' => [UploadedFile::fake()->create('brief.pdf', 50)]], ['Accept' => 'application/json'])->assertCreated();
        $file = $order->files()->firstOrFail();
        Storage::disk('local')->assertExists($file->path);

        $this->actingAs($admin)->get($res->json('files.0.url'))->assertOk();
        $this->actingAs($this->customer('other@example.test'))->get($res->json('files.0.url'))->assertForbidden();

        $this->actingAs($customer)->post("/portal/orders/{$order->number}/messages", ['files' => [UploadedFile::fake()->create('evil.php', 1)]], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_first_message_emails_an_offline_recipient_but_follow_ups_are_throttled(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        $chat = app(ChatService::class);

        $chat->post($order, $admin, 'First');
        Mail::assertSent(NewChatMessages::class, fn ($m) => $m->hasTo('client@example.test') && ! $m->toAdmin);

        $chat->post($order, $admin, 'Second');
        $chat->post($order, $admin, 'Third');
        Mail::assertSent(NewChatMessages::class, 1);              // still just one e-mail
    }

    public function test_nobody_is_emailed_while_they_are_on_the_page(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        $chat = app(ChatService::class);

        $chat->markRead($order, $customer);                       // customer has the page open right now
        $chat->post($order, $admin, 'You there?');
        Mail::assertNothingSent();
    }

    public function test_digest_sends_one_summary_for_messages_that_slipped_through_the_cooldown(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        $chat = app(ChatService::class);

        $chat->post($order, $admin, 'One');                       // e-mailed immediately
        $chat->post($order, $admin, 'Two');                       // swallowed by the cooldown
        Mail::assertSent(NewChatMessages::class, 1);

        // 40 minutes later the digest picks up the stragglers
        $this->travel(40)->minutes();
        app(ChatNotifier::class)->sendDigests();
        Mail::assertSent(NewChatMessages::class, 2);
        Mail::assertSent(NewChatMessages::class, fn ($m) => $m->messages->pluck('body')->all() === ['Two']);

        app(ChatNotifier::class)->sendDigests();                  // nothing new: no third e-mail
        Mail::assertSent(NewChatMessages::class, 2);
    }

    public function test_customer_messages_go_to_the_admin_address(): void
    {
        [$order, $admin, $customer] = $this->paidOrder();
        config(['site.admin_email' => 'alerts@example.test']);

        app(ChatService::class)->post($order, $customer, 'Question about the render');
        Mail::assertSent(NewChatMessages::class, fn ($m) => $m->hasTo('alerts@example.test') && $m->toAdmin);
    }
}
