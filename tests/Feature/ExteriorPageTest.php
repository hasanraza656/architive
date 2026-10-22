<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExteriorPageTest extends TestCase
{
    use RefreshDatabase;

    private function fetch(string $path)
    {
        return $this->app->handle(Request::create($path));
    }

    public function test_exterior_page_renders_with_seo_and_faq_schema(): void
    {
        $res = $this->fetch('/exterior-rendering-services/');
        $html = $res->getContent();

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('<title>Exterior Rendering Services and 3D Visualization | Architive</title>', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('How much do exterior rendering services cost?', $html);
        // Exterior gallery only: no interior images, no filter buttons
        $this->assertStringContainsString('Wraparound porch residence', $html);
        $this->assertStringNotContainsString('Primary bedroom', $html);
        $this->assertStringNotContainsString('data-gfilter="all"', $html);
    }

    public function test_old_exterior_addresses_redirect_to_the_new_page(): void
    {
        foreach (['/exterior-visualizations/', '/exterior-design/'] as $old) {
            $res = $this->fetch($old);
            $this->assertSame(301, $res->getStatusCode(), $old);
            $this->assertStringEndsWith('/exterior-rendering-services/', $res->headers->get('Location'), $old);
        }
        $res = $this->fetch('/interior-visualizations/');
        $this->assertSame(301, $res->getStatusCode());
        $this->assertStringEndsWith('/interior-rendering-services/', $res->headers->get('Location'));
    }

    public function test_exterior_page_is_in_the_sitemap_and_linked_from_visualization(): void
    {
        $this->assertStringContainsString('/exterior-rendering-services/', $this->fetch('/sitemap.xml')->getContent());
        $this->assertStringContainsString('/exterior-rendering-services/', $this->fetch('/architectural-visualization-rendering/')->getContent());
    }
}
