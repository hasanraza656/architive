<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /** The test client trims a trailing slash, so build the request by hand to keep the exact public URL. */
    private function page(string $path)
    {
        return $this->app->handle(Request::create($path));
    }

    public function test_ga4_tag_consent_defaults_and_notice_are_on_public_pages_when_enabled(): void
    {
        config(['services.analytics.enabled' => true, 'services.analytics.ga4_id' => 'G-36RQKB0CMB']);

        foreach (['/', '/services/', '/contact/', '/privacy-policy/', '/blog/'] as $path) {
            $res = $this->page($path);
            $this->assertSame(200, $res->getStatusCode(), $path . ' -> ' . $res->headers->get('Location'));
            $html = $res->getContent();
            $this->assertStringContainsString("gtag('config', 'G-36RQKB0CMB')", $html, $path);
            $this->assertStringContainsString("analytics_storage: 'denied'", $html, $path);
            $this->assertStringContainsString("'GB'", $html, $path);
            $this->assertStringContainsString('id="consent"', $html, $path);
            $this->assertStringContainsString('data-cookie-settings hidden', $html, $path);
        }
    }

    public function test_nothing_is_added_when_analytics_is_switched_off(): void
    {
        config(['services.analytics.enabled' => false]);

        $html = $this->page('/')->getContent();
        $this->assertStringNotContainsString('googletagmanager', $html);
        $this->assertStringNotContainsString("gtag('config'", $html);
        $this->assertStringNotContainsString('id="consent"', $html);
    }

    public function test_the_tag_never_loads_before_the_page_has_finished_loading(): void
    {
        config(['services.analytics.enabled' => true, 'services.analytics.ga4_id' => 'G-36RQKB0CMB']);

        $html = $this->page('/')->getContent();
        $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com', $html);
        $this->assertStringContainsString("addEventListener('load', load)", $html);
    }
}
