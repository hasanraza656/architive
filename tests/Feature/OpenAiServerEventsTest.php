<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OpenAiServerEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.recaptcha.site_key' => 'k', 'services.recaptcha.secret_key' => 's', 'site.admin_email' => 'admin@example.com',
            'services.openai_pixel' => ['enabled' => true, 'id' => 'PIX123', 'debug' => false, 'api_key' => 'secret-capi-key', 'server_events' => true, 'server_whatsapp' => true],
            'app.url' => 'https://architive.net',
        ]);
        Mail::fake();
    }

    private function form(array $over = []): array
    {
        return array_merge([
            'name' => 'Test User', 'email' => 'Visitor@Example.com', 'service' => 'cad', 'message' => 'Please quote twelve sheets of redlines.',
            'consent' => '1', 'lp' => 'cad-drafting', 'g-recaptcha-response' => 'tok', 'ads_consent' => '1',
            'attribution' => json_encode(['first' => ['utm_source' => 'chatgpt', 'utm_medium' => 'paid', 'utm_campaign' => 'cad_support_us', 'utm_content' => 'ad_77', 'oppref' => 'opp_ABC123', 'junk' => 'x']]),
        ], $over);
    }

    private function fakes(): void
    {
        Http::fake([
            'www.google.com/*' => Http::response(['success' => true]),
            'bzr.openai.com/*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_successful_form_sends_one_server_lead_with_the_pixel_id_and_saves_the_campaign(): void
    {
        $this->fakes();
        $this->withHeader('Referer', 'https://architive.net/lp/cad-drafting/?utm_source=chatgpt&email=a@b.c')
            ->postJson('/contact/send', $this->form())->assertOk();

        $order = Order::latest('id')->first();
        $sent = [];
        Http::assertSent(function ($r) use (&$sent) {
            if (str_contains($r->url(), 'bzr.openai.com')) { $sent[] = $r; }
            return true;
        });
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('pid=PIX123', $sent[0]->url());
        $this->assertSame('Bearer secret-capi-key', $sent[0]->header('Authorization')[0]);
        $e = $sent[0]['events'][0];
        $this->assertSame('lead_' . $order->number, $e['id']);             // same id as the browser pixel: OpenAI keeps one
        $this->assertSame('lead_created', $e['type']);
        $this->assertSame('opp_ABC123', $e['oppref']);
        $this->assertSame(hash('sha256', 'visitor@example.com'), $e['user']['emails_sha256'][0]);
        $this->assertSame('https://architive.net/lp/cad-drafting/', $e['source_url']);   // no query string
        $this->assertIsInt($e['timestamp_ms']);

        $this->assertSame('chatgpt', $order->attribution['first']['utm_source']);
        $this->assertSame('ad_77', $order->attribution['first']['utm_content']);
        $this->assertArrayNotHasKey('junk', $order->attribution['first']);
    }

    public function test_no_server_event_without_ad_consent_or_without_a_key(): void
    {
        $this->fakes();
        $this->postJson('/contact/send', $this->form(['ads_consent' => '0']))->assertOk();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'bzr.openai.com'));

        config(['services.openai_pixel.api_key' => null]);
        $this->postJson('/contact/send', $this->form())->assertOk();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'bzr.openai.com'));
    }

    public function test_openai_failure_never_breaks_the_enquiry(): void
    {
        Http::fake(['www.google.com/*' => Http::response(['success' => true]), 'bzr.openai.com/*' => Http::response('boom', 500)]);
        $this->postJson('/contact/send', $this->form())->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, Order::count());
    }

    public function test_whatsapp_beacon_forwards_one_custom_event_and_ignores_repeats_and_bad_ids(): void
    {
        Http::fake(['bzr.openai.com/*' => Http::response(['ok' => true])]);
        $body = ['id' => 'wa_abcd1234xyz', 'at' => (int) (microtime(true) * 1000), 'path' => '/lp/cad-drafting/', 'oppref' => 'opp_ABC123'];

        $this->postJson('/ads/whatsapp', $body)->assertNoContent();
        $this->postJson('/ads/whatsapp', $body)->assertNoContent();                 // same tap again
        $this->postJson('/ads/whatsapp', ['id' => 'nonsense'] + $body)->assertNoContent();

        $this->assertCount(1, Http::recorded());
        Http::assertSent(function ($r) {
            $e = $r['events'][0];
            return $e['id'] === 'wa_abcd1234xyz' && $e['type'] === 'custom' && $e['custom_event_name'] === 'whatsapp'
                && $e['oppref'] === 'opp_ABC123' && $e['source_url'] === 'https://architive.net/lp/cad-drafting/';
        });
    }

    public function test_pages_carry_the_pixel_once_with_debug_off_and_the_whatsapp_hook(): void
    {
        $html = $this->withoutMiddleware(\App\Http\Middleware\EnsureTrailingSlash::class)->get('/lp/cad-drafting/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, "oaiq('init'"));
        $this->assertStringNotContainsString('debug: true', $html);
        $this->assertStringContainsString("custom_event_name: 'whatsapp'", $html);
        $this->assertStringContainsString('/ads/whatsapp', $html);
        $this->assertStringNotContainsString('secret-capi-key', $html);
    }
}
