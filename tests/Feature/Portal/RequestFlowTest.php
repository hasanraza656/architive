<?php

namespace Tests\Feature\Portal;

use App\Enums\OrderStatus;
use App\Mail\ContactEnquiry;
use App\Mail\Portal\InvoiceSent;
use App\Mail\Portal\PaymentReceivedAdmin;
use App\Mail\Portal\PaymentReceivedCustomer;
use App\Mail\Portal\RequestReceived;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\InvoicePdf;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RequestFlowTest extends PortalTestCase
{
    private function website(array $over = [], array $files = [])
    {
        config(['services.recaptcha.site_key' => 'k', 'services.recaptcha.secret_key' => 's', 'site.admin_email' => 'alerts@example.test']);
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        return $this->post('/contact/send', $over + [
            'name' => 'Pat Visitor', 'email' => 'pat@example.test', 'company' => 'Visitor Studio', 'service' => 'visualization', 'audience' => 'firm',
            'message' => 'We need exterior renders of a two storey house.', 'consent' => '1', 'g-recaptcha-response' => 'tok', 'files' => $files,
        ], ['Accept' => 'application/json']);
    }

    public function test_website_form_creates_account_request_and_both_emails(): void
    {
        Mail::fake();
        Storage::fake('uploads');

        $res = $this->website([], [UploadedFile::fake()->create('plans.pdf', 120)])->assertOk()->assertJsonStructure(['ok', 'message', 'request', 'portal_url']);

        $customer = User::where('email', 'pat@example.test')->firstOrFail();
        $this->assertSame('Pat', $customer->first_name);
        $this->assertSame('Visitor', $customer->last_name);

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::Request, $order->status);
        $this->assertSame('website', $order->source);
        $this->assertSame($res->json('request'), $order->number);
        $this->assertSame(1, $order->messages()->count());                       // the brief is the first chat message
        $this->assertSame(1, $order->files()->count());
        Storage::disk('uploads')->assertExists($order->files()->first()->path);

        Mail::assertSent(ContactEnquiry::class, fn ($m) => $m->hasTo('alerts@example.test') && $m->meta['order']->is($order));
        Mail::assertSent(RequestReceived::class, fn ($m) => $m->hasTo('pat@example.test'));
    }

    public function test_a_returning_visitor_reuses_the_account_and_gets_a_second_request(): void
    {
        Mail::fake();
        $this->website()->assertOk();
        $this->website()->assertOk();

        $this->assertSame(1, User::where('email', 'pat@example.test')->count());
        $this->assertSame(2, Order::count());
    }

    public function test_attachments_are_validated_on_the_public_form(): void
    {
        Mail::fake();

        $this->website([], [UploadedFile::fake()->create('evil.php', 1)])->assertStatus(422)->assertJsonValidationErrors('files.0');
        $this->website([], [UploadedFile::fake()->create('huge.zip', 20000)])->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    public function test_the_visitor_can_sign_in_and_see_their_request_and_chat(): void
    {
        Mail::fake();
        $this->website()->assertOk();
        $order = Order::firstOrFail();
        $customer = $order->customer;

        $this->actingAs($customer)->get('/account')->assertOk()->assertSee('Your requests')->assertSee('We are reviewing');
        $this->actingAs($customer)->get($order->customerUrl())->assertOk()->assertSee('We have your request');
        $this->actingAs($customer)->postJson("/portal/orders/{$order->number}/messages", ['body' => 'One more detail'])->assertCreated();
    }

    public function test_customer_can_create_a_request_from_the_portal(): void
    {
        Mail::fake();
        config(['site.admin_email' => 'alerts@example.test']);
        $customer = $this->customer();

        $this->actingAs($customer)->get('/account/requests/new')->assertOk()->assertSee('Start a');
        $this->actingAs($customer)->post('/account/requests', ['service' => 'bim', 'message' => 'Scan to BIM for a small office building.'])
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('portal', $order->source);
        $this->assertSame(OrderStatus::Request, $order->status);
        Mail::assertSent(ContactEnquiry::class, fn ($m) => $m->hasTo('alerts@example.test'));

        $this->actingAs($customer)->post('/account/requests', ['message' => 'x'])->assertSessionHasErrors(['service', 'message']);
    }

    public function test_custom_offer_flow_from_request_to_paid_order(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->website()->assertOk();
        $order = Order::firstOrFail();
        $customer = $order->customer;
        Mail::fake();   // forget the request e-mails

        // the admin prices the request: it must be savable without becoming visible/payable yet
        $this->actingAs($admin)->put("/admin/orders/{$order->number}", [
            'customer_id' => $customer->id, 'title' => 'Exterior renders', 'action' => 'draft',
            'items' => [['description' => '3 exterior renders', 'quantity' => 1, 'unit_price' => '600']],
        ])->assertRedirect();
        $this->assertSame(OrderStatus::Request, $order->fresh()->status);
        $this->assertSame(60000, $order->fresh()->total_cents);

        // sending it posts an offer card in the chat and e-mails the customer
        $this->actingAs($admin)->put("/admin/orders/{$order->number}", [
            'customer_id' => $customer->id, 'title' => 'Exterior renders', 'action' => 'send',
            'items' => [['description' => '3 exterior renders', 'quantity' => 1, 'unit_price' => '600']],
        ])->assertRedirect();
        $order->refresh();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(1, $order->messages()->where('kind', 'offer')->count());
        Mail::assertSent(InvoiceSent::class, fn ($m) => $m->hasTo('pat@example.test'));

        // the customer sees the live offer in the chat feed and can pay it
        $feed = $this->actingAs($customer)->getJson("/portal/orders/{$order->number}/messages")->assertOk()->json();
        $this->assertTrue($feed['offer']['can_pay']);
        $this->assertSame('$600.00', $feed['offer']['total']);
        $this->assertSame('offer', collect($feed['messages'])->firstWhere('kind', 'offer')['kind']);

        // payment starts the order (webhook-free path used here), and mails carry the PDF
        app(OrderWorkflow::class)->markPaid($order, 'cs_1', 'pi_1');
        $this->assertSame(OrderStatus::Active, $order->fresh()->status);
        Mail::assertSent(PaymentReceivedCustomer::class, function ($m) {
            $att = $m->attachments();
            return count($att) === 1 && str_starts_with($m->render() ? 'ok' : '', 'ok');
        });
        Mail::assertSent(PaymentReceivedAdmin::class);
        $feed = $this->actingAs($customer)->getJson("/portal/orders/{$order->number}/messages")->json();
        $this->assertFalse($feed['offer']['can_pay']);
        $this->assertSame('active', $feed['offer']['status']);
    }

    public function test_offer_cards_never_trigger_extra_new_message_emails(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->website()->assertOk();
        $order = Order::firstOrFail();
        $order->items()->create(['description' => 'x', 'quantity' => 1, 'unit_price_cents' => 50000, 'position' => 0]);
        $order->update(['total_cents' => 50000, 'subtotal_cents' => 50000]);
        Mail::fake();

        app(OrderWorkflow::class)->send($order->fresh(), $admin);
        Mail::assertSent(InvoiceSent::class, 1);
        Mail::assertNotSent(\App\Mail\Portal\NewChatMessages::class);
    }

    public function test_invoice_pdf_renders_with_whatsapp_and_is_access_controlled(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $customer = $this->customer();
        $other = $this->customer('other@example.test');
        $order = $this->order($customer, $admin);
        app(OrderWorkflow::class)->send($order, $admin);

        $pdf = app(InvoicePdf::class)->render($order->fresh());
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertStringContainsString('Invoice-' . $order->number . '.pdf', app(InvoicePdf::class)->filename($order));

        $this->actingAs($customer)->get("/portal/orders/{$order->number}/invoice")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($admin)->get("/portal/orders/{$order->number}/invoice")->assertOk();
        $this->actingAs($other)->get("/portal/orders/{$order->number}/invoice")->assertForbidden();

        $this->actingAs($customer)->get($order->customerUrl())->assertSee(config('site.phone_display'));
    }

    public function test_requests_without_an_offer_have_no_invoice_and_can_be_closed(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->website()->assertOk();
        $order = Order::firstOrFail();

        $this->actingAs($admin)->get("/portal/orders/{$order->number}/invoice")->assertNotFound();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('New requests waiting');
        $this->actingAs($admin)->get('/admin/orders?status=request')->assertOk()->assertSee('No offer yet');

        $this->actingAs($admin)->post("/admin/orders/{$order->number}/cancel", ['reason' => 'Out of scope'])->assertRedirect();
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_footer_links_to_the_customer_portal_and_forms_accept_files(): void
    {
        $this->get('/')->assertOk()->assertSee('Customer Portal')->assertSee('name="files[]"', false)->assertSee(config('site.phone_display'));
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class)->get('/contact/')->assertOk()->assertSee('name="files[]"', false)->assertSee('enctype="multipart/form-data"', false);
    }
}
