<?php

namespace Tests\Feature\Portal;

use App\Models\OrderMessage;
use App\Services\Chat\ChatService;
use Illuminate\Support\Facades\Storage;

class InboxAndDeleteTest extends PortalTestCase
{
    private function thread(): array
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $order = $this->order($customer, $admin);
        $order->update(['status' => 'active']);
        $chat = app(ChatService::class);
        $first = $chat->post($order, $customer, 'Hello, first question');
        $second = $chat->post($order, $customer, 'Second question about dusk');
        $reply = $chat->post($order, $admin, 'Team reply');

        return [$admin, $customer, $order, $first, $second, $reply];
    }

    public function test_inbox_lists_customer_messages_with_unread_state_and_filters(): void
    {
        [$admin, $customer, $order, $first, $second] = $this->thread();
        $other = $this->customer('other@example.test', ['first_name' => 'Olga']);
        $order2 = $this->order($other, $admin);
        $order2->update(['status' => 'active']);
        app(ChatService::class)->post($order2, $other, 'Unrelated hello');
        // admin has read nothing yet, but posting a reply moved the read marker in order 1
        $this->actingAs($admin);

        $this->get('/admin/inbox')->assertOk()->assertSee('Unrelated hello')->assertDontSee('Team reply');
        $this->get('/admin/inbox?from=team')->assertOk()->assertSee('Team reply');
        $this->get('/admin/inbox?state=unread')->assertOk()->assertSee('Unrelated hello')->assertDontSee('Second question');
        $this->get('/admin/inbox?customer=' . $other->id)->assertOk()->assertSee('Unrelated hello')->assertDontSee('Second question');
        $this->get('/admin/inbox?order=' . $order->number)->assertOk()->assertSee('Second question')->assertDontSee('Unrelated hello');
        $this->get('/admin/inbox?q=dusk')->assertOk()->assertSee('Second question')->assertDontSee('Hello, first');
        $this->get('/admin/inbox?date_from=2999-01-01')->assertOk()->assertSee('No messages match');
        $this->get('/admin/inbox?date_from=not-a-date')->assertOk();
        $this->get('/admin/orders/' . $order->number . '?m=' . $first->id)->assertOk();   // jump link target renders
    }

    public function test_mark_unread_read_and_read_all(): void
    {
        [$admin, , $order, $first, $second] = $this->thread();
        $this->actingAs($admin);
        $inbox = app(\App\Services\Chat\Inbox::class);

        $this->assertSame(0, $inbox->unreadCount());                       // the reply read the whole thread
        $this->postJson("/admin/inbox/messages/{$second->id}/unread")->assertOk()->assertJson(['unread' => 1]);
        $this->assertSame(1, $inbox->unreadCount());
        $this->postJson("/admin/inbox/messages/{$first->id}/unread")->assertOk()->assertJson(['unread' => 2]);
        $this->postJson("/admin/inbox/messages/{$first->id}/read")->assertOk()->assertJson(['unread' => 1]);
        $this->post('/admin/inbox/read-all')->assertRedirect();
        $this->assertSame(0, $inbox->unreadCount());

        $teamMessage = OrderMessage::where('user_id', $admin->id)->first();
        $this->postJson("/admin/inbox/messages/{$teamMessage->id}/unread")->assertStatus(422);
    }

    public function test_inbox_is_admin_only(): void
    {
        $this->actingAs($this->customer())->get('/admin/inbox')->assertRedirect();
        $this->post('/admin/inbox/read-all')->assertRedirect();
    }

    public function test_admin_can_delete_any_order_and_its_files(): void
    {
        Storage::fake(config('portal.uploads.disk'));
        [$admin, , $order] = $this->thread();
        Storage::disk(config('portal.uploads.disk'))->put('orders/' . $order->id . '/a.txt', 'x');
        $this->actingAs($admin);

        $this->delete('/admin/orders/' . $order->number)->assertRedirect(route('admin.orders.index'));
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('order_messages', 0);
        Storage::disk(config('portal.uploads.disk'))->assertMissing('orders/' . $order->id . '/a.txt');
    }

    public function test_admin_can_delete_a_customer_with_everything(): void
    {
        [$admin, $customer, $order] = $this->thread();
        $this->actingAs($admin);

        $this->delete('/admin/customers/' . $customer->id)->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->delete('/admin/customers/' . $admin->id)->assertNotFound();   // admins cannot be removed here
    }

    public function test_customers_cannot_delete(): void
    {
        [, $customer, $order] = $this->thread();
        $this->actingAs($customer)->delete('/admin/orders/' . $order->number)->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_whatsapp_button_is_on_the_site_with_the_right_number(): void
    {
        $this->get('/')->assertOk()->assertSee('wa.me/18157716318', false)->assertSee('header-wa', false)->assertSee('header-phone', false)->assertDontSee('wa-float', false);
    }
}
