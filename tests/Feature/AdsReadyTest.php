<?php

namespace Tests\Feature;

use App\Mail\ContactEnquiry;
use App\Mail\Portal\RequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdsReadyTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $path)
    {
        return $this->app->handle(Request::create($path));
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.recaptcha.site_key'   => 'site-key',
            'services.recaptcha.secret_key' => 'secret-key',
            'site.admin_email'              => 'admin@example.com',
        ]);
    }

    public static function landingPages(): array
    {
        return [['3d-rendering', 'Renders Your Clients'], ['3d-floor-plans', 'Floor Plans Buyers Understand'], ['cad-drafting', 'Drawing Sets Done']];
    }

    /** @dataProvider landingPages */
    public function test_landing_page_is_noindex_focused_and_posts_to_the_thank_you_page(string $slug, string $h1): void
    {
        $res = $this->page("/lp/{$slug}/");
        $html = $res->getContent();

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
        $this->assertStringContainsString($h1, $html);
        $this->assertStringContainsString('Free 3-day trial', $html);
        $this->assertStringContainsString('data-thanks="/thank-you/"', $html);
        $this->assertStringContainsString('name="lp" value="' . $slug . '"', $html);
        $this->assertStringNotContainsString('class="main-nav', $html);          // no menu to leak visitors
        $this->assertStringNotContainsString('id="enquiryModal"', $html);
        $this->assertStringNotContainsString("/lp/{$slug}/", $this->page('/sitemap.xml')->getContent());
    }

    public function test_unknown_landing_page_is_404_and_thank_you_page_is_noindex(): void
    {
        $this->assertSame(404, $this->page('/lp/nope/')->getStatusCode());

        $html = $this->page('/thank-you/?ref=ARC-1001')->getContent();
        $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $html);
        $this->assertStringContainsString('ARC-1001', $html);
        $this->assertStringNotContainsString('&lt;script', $this->page('/thank-you/?ref=%3Cscript%3E')->getContent());
    }

    public function test_ads_tag_and_ads_consent_are_emitted_when_configured(): void
    {
        config(['services.analytics.enabled' => true, 'services.analytics.ads_id' => 'AW-123', 'services.analytics.ads_lead_label' => 'Lead1']);
        $html = $this->page('/lp/3d-rendering/')->getContent();

        $this->assertStringContainsString("gtag('config', 'AW-123'", $html);
        $this->assertStringContainsString('lead:"Lead1"', str_replace(' ', '', $html));
        $this->assertStringContainsString("ad_storage: 'granted'", $html);          // outside the GDPR region list
        $this->assertStringContainsString("'contact_click'", $html);

        config(['services.analytics.ads_id' => null]);
        $this->assertStringNotContainsString("gtag('config', 'AW-", $this->page('/')->getContent());
    }

    public function test_trial_enquiry_reports_its_ad_source_and_goes_to_the_thank_you_page_without_js(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $attribution = json_encode([
            'first' => ['utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'us-floor-plans', 'utm_term' => '3d floor plan', 'gclid' => 'abc', 'landing' => '/lp/3d-floor-plans/', 'at' => '2026-10-27'],
            'last'  => null,
        ]);

        $this->post('/contact/send', [
            'name' => 'Pat Buyer', 'email' => 'pat@example.com', 'service' => 'visualization', 'topic' => '3D Floor Plan (free trial)',
            'message' => 'Four apartment plans from the attached PDFs please.', 'consent' => '1', 'lp' => '3d-floor-plans',
            'attribution' => $attribution, 'g-recaptcha-response' => 'ok',
        ])->assertRedirect('/thank-you/?ref=ARC-1001');

        Mail::assertSent(ContactEnquiry::class, function (ContactEnquiry $m) {
            $line = $m->meta['source']['First'] ?? '';

            return str_contains($line, 'google / cpc') && str_contains($line, 'keyword "3d floor plan"')
                && str_contains($line, 'Google Ads click') && ! array_key_exists('attribution', $m->enquiry)
                && str_contains($m->render(), 'Lead source');
        });
        Mail::assertSent(RequestReceived::class, fn ($m) => str_contains($m->render(), 'We confirm your free trial'));
        $this->assertDatabaseHas('orders', ['number' => 'ARC-1001', 'title' => 'Free trial: ' . ContactEnquiry::SERVICES['visualization'] . ' request']);
    }

    public function test_garbage_attribution_is_ignored(): void
    {
        Mail::fake();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $this->postJson('/contact/send', [
            'name' => 'Sam', 'email' => 'sam@example.com', 'message' => 'Hello there, a quick question.', 'consent' => '1',
            'attribution' => '{not json', 'g-recaptcha-response' => 'ok',
        ])->assertOk();

        Mail::assertSent(ContactEnquiry::class, fn (ContactEnquiry $m) => $m->meta['source'] === []);
    }
}
