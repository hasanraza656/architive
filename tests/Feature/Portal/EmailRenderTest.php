<?php

namespace Tests\Feature\Portal;

use App\Mail\Portal\DeliveryAccepted;
use App\Mail\Portal\InvoiceSent;
use App\Mail\Portal\LoginCodeMail;
use App\Mail\Portal\NewChatMessages;
use App\Mail\Portal\OrderCancelled;
use App\Mail\Portal\OrderDelivered;
use App\Mail\Portal\PaymentReceivedAdmin;
use App\Mail\Portal\PaymentReceivedCustomer;
use App\Mail\Portal\RevisionRequested;
use App\Services\Orders\OrderWorkflow;

/** Every portal e-mail must compile and render (a template error silently stops the e-mail from being sent). */
class EmailRenderTest extends PortalTestCase
{
    public function test_every_portal_email_renders_with_and_without_a_due_date(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();

        foreach ([null, now()->addDays(5)] as $due) {
            $order = $this->order($customer, $admin, ['due_at' => $due]);
            app(OrderWorkflow::class)->send($order, $admin);
            $order = $order->fresh(['customer', 'items']);
            $delivery = $order->deliveries()->create(['user_id' => $admin->id, 'note' => 'Done']);
            $message = $order->messages()->create(['user_id' => $admin->id, 'body' => 'Hello there']);
            $order->update(['cancel_reason' => 'Changed plans']);

            $mails = [
                new InvoiceSent($order),
                new PaymentReceivedCustomer($order),
                new PaymentReceivedAdmin($order),
                new OrderDelivered($order, $delivery->load('files')),
                new DeliveryAccepted($order),
                new RevisionRequested($order, 'Brighter please'),
                new OrderCancelled($order),
                new NewChatMessages($order, collect([$message->load('user', 'files')]), false),
                new NewChatMessages($order, collect([$message->load('user', 'files')]), true),
                new LoginCodeMail($customer, '123456', 10),
            ];

            foreach ($mails as $mail) {
                $html = $mail->render();
                $this->assertStringContainsString('Architive', $html, get_class($mail));
            }
        }
    }
}
