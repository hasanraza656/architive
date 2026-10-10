<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Mail\ContactEnquiry;
use App\Mail\Portal\RequestReceived;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Notifications\PortalMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Order requests (leads). A website form or the portal's "New request" page creates an order in the
 * "request" stage with the customer's brief as the first chat message. The admin discusses it in that chat and
 * later sends a custom offer, which turns the same record into a payable order (see OrderWorkflow::send).
 */
class RequestService
{
    public function __construct(private FileStorage $files, private PortalMailer $mailer)
    {
    }

    /**
     * The customer account behind a website form: reuse it by e-mail, or create it instantly (no password,
     * they sign in with an e-mailed code). Returns null when the e-mail belongs to an admin (no ticket then).
     */
    public function customerFor(string $name, string $email): ?User
    {
        $email = mb_strtolower(trim($email));
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing->isCustomer() ? $existing : null;
        }

        [$first, $last] = $this->splitName($name);

        return User::create([
            'role' => User::ROLE_CUSTOMER, 'first_name' => $first, 'last_name' => $last, 'email' => $email,
            'is_active' => true, 'profile_completed_at' => now(),
        ]);
    }

    /**
     * @param  array{service?:?string,audience?:?string,company?:?string,deadline?:?string,message:string,links?:?string,nda?:mixed,topic?:?string}  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function create(User $customer, array $data, array $files = [], string $source = 'website'): Order
    {
        $label = ContactEnquiry::SERVICES[$data['service'] ?? ''] ?? null;

        $order = DB::transaction(function () use ($customer, $data, $files, $source, $label) {
            $order = Order::create([
                'customer_id' => $customer->id,
                'title' => $label ? $label . ' request' : 'New project request',
                'status' => OrderStatus::Request,
                'source' => $source,
                'service' => $data['service'] ?? null,
                'audience' => $data['audience'] ?? null,
                'company' => $data['company'] ?? null,
                'brief' => $data['message'],
                'requested_deadline' => ! empty($data['deadline']) ? $data['deadline'] : null,
                'currency' => config('portal.currency'),
            ]);
            $order->update(['number' => config('portal.order_prefix') . (config('portal.order_number_start') + $order->id)]);
            $order->events()->create(['type' => 'requested', 'message' => 'Request received', 'user_id' => $customer->id]);

            // the brief becomes the first message of the conversation, with any attached files
            $text = trim($data['message']);
            if (! empty($data['links'])) {
                $text .= "\n\nLinks: " . trim($data['links']);
            }
            if (! empty($data['nda'])) {
                $text .= "\n\nPlease send a mutual NDA before I share detailed files.";
            }
            $message = OrderMessage::create(['order_id' => $order->id, 'user_id' => $customer->id, 'body' => $text]);
            $this->files->storeMany($files, $order, $customer, ['message_id' => $message->id]);

            return $order;
        });

        return $order->fresh(['customer', 'files']);
    }

    /** E-mail the customer a receipt for their request ("what happens next" + a link to follow it). */
    public function notifyCustomer(Order $order): bool
    {
        return $this->mailer->send($order->customer->email, new RequestReceived($order));
    }

    /** E-mail the admin inbox (ADMIN_EMAIL) about the new request, with a button straight into the portal. */
    public function notifyAdmin(Order $order, array $extra = [], array $meta = []): bool
    {
        $enquiry = [
            'name' => $order->customer->name, 'email' => $order->customer->email, 'company' => $order->company,
            'audience' => $order->audience, 'service' => $order->service,
            'deadline' => $order->requested_deadline?->toDateString(), 'message' => $order->brief,
        ] + $extra;

        return $this->mailer->toAdmin(new ContactEnquiry($enquiry, $meta + ['order' => $order, 'files' => $order->files->count()]));
    }

    /** @return array{0:string,1:?string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];
        $first = Str::limit($parts[0] ?? '', 80, '') ?: 'Customer';

        return [$first, isset($parts[1]) ? Str::limit($parts[1], 80, '') : null];
    }
}
