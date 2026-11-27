<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrialAndBriefPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'free-trial' => ['/free-trial/', 'Free 3-Day Trial: CAD, BIM and 3D Rendering | Architive', 'Is the trial really free?'],
            'what-we-need' => ['/what-we-need/', 'What to Send for CAD, BIM and 3D Rendering | Architive', 'What if I only have a sketch?'],
        ];
    }

    /** @dataProvider pages */
    public function test_page_renders_with_title_faq_and_is_in_sitemap(string $path, string $title, string $faq): void
    {
        $res = $this->app->handle(Request::create($path));
        $this->assertSame(200, $res->getStatusCode());
        $html = $res->getContent();
        $this->assertStringContainsString('<title>' . $title . '</title>', $html);
        $this->assertStringContainsString($faq, $html);
        $this->assertStringContainsString('FAQPage', $html);
        $this->assertStringNotContainsString('&lt;a href', $html);
        $map = $this->app->handle(Request::create('/sitemap.xml'))->getContent();
        $this->assertStringContainsString($path, $map);
    }
}
