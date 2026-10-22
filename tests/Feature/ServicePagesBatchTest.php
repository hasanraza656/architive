<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ServicePagesBatchTest extends TestCase
{
    use RefreshDatabase;

    private function fetch(string $path)
    {
        return $this->app->handle(Request::create($path));
    }

    public static function pages(): array
    {
        return [
            'interior' => ['/interior-rendering-services/', '3D Interior Rendering Services | Architive', 'How much do interior rendering services cost?', 'Primary bedroom'],
            'floorplans' => ['/3d-floor-plan-rendering-services/', '3D Floor Plan Rendering Services | Architive', 'Can you convert a 2D floor plan into a 3D floor plan?', '3D floor plan, top view'],
            'rayon' => ['/rayon-cad-drafting-services/', 'Rayon CAD Drafting Services for Interior Designers | Architive', 'What is Rayon?', null],
        ];
    }

    /** @dataProvider pages */
    public function test_page_renders_with_title_faq_schema_and_own_gallery(string $path, string $title, string $faq, ?string $galleryItem): void
    {
        $res = $this->fetch($path);
        $html = $res->getContent();

        $this->assertSame(200, $res->getStatusCode(), $path);
        $this->assertStringContainsString("<title>{$title}</title>", $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString(e($faq), $html);
        $this->assertStringContainsString('/' . trim($path, '/') . '/', $this->fetch('/sitemap.xml')->getContent());
        if ($galleryItem) {
            $this->assertStringContainsString($galleryItem, $html);
            $this->assertStringNotContainsString('Wraparound porch residence', $html); // exteriors stay on their own page
        }
    }

    public function test_rayon_page_states_independence(): void
    {
        $this->assertStringContainsString('not affiliated with or endorsed by Rayon', $this->fetch('/rayon-cad-drafting-services/')->getContent());
    }

    public function test_old_indexed_wordpress_pages_redirect_to_the_new_pages(): void
    {
        $map = [
            '/interior-design-visualization/' => '/interior-rendering-services/',
            '/3d-floor-plans/' => '/3d-floor-plan-rendering-services/',
            '/team-details/' => '/team/',
            '/projects/' => '/collaborations/',
        ];
        foreach ($map as $old => $new) {
            $res = $this->fetch($old);
            $this->assertSame(301, $res->getStatusCode(), $old);
            $this->assertStringEndsWith($new, $res->headers->get('Location'), $old);
        }
    }
}
