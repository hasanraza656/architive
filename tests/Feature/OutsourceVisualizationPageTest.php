<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OutsourceVisualizationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_seo_basics_and_is_in_sitemap(): void
    {
        $res = $this->app->handle(Request::create('/outsource-architectural-visualization/'));
        $this->assertSame(200, $res->getStatusCode());
        $html = $res->getContent();
        $this->assertStringContainsString('<title>Outsource Architectural Visualization | Architive</title>', $html);
        $this->assertStringContainsString('Outsource Your Renders', $html);
        $this->assertStringContainsString('FAQPage', $html);
        $map = $this->app->handle(Request::create('/sitemap.xml'))->getContent();
        $this->assertStringContainsString('/outsource-architectural-visualization/', $map);
    }
}
