<?php

namespace Tests\Feature;

use App\Support\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DraftCaseStudiesTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $path)
    {
        return $this->app->handle(Request::create($path));
    }

    public function test_drafts_open_by_link_but_stay_out_of_lists_sitemap_and_search(): void
    {
        $drafts = array_keys(array_filter(Content::collaborations(true), fn ($c) => ! empty($c['draft'])));
        $this->assertNotEmpty($drafts);

        $list = $this->page('/collaborations/')->getContent() . $this->page('/')->getContent() . $this->page('/sitemap.xml')->getContent() . $this->page('/sitemap/')->getContent();
        foreach ($drafts as $slug) {
            $res = $this->page("/collaborations/{$slug}/");
            $this->assertSame(200, $res->getStatusCode(), $slug);
            $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow">', $res->getContent());
            $this->assertStringContainsString('Draft for client review', $res->getContent());
            $this->assertStringNotContainsString("/collaborations/{$slug}/", $list, $slug);
        }

        $live = $this->page('/collaborations/uk-3d-floor-plans-smart-heating-cooling/')->getContent();
        $this->assertStringNotContainsString('Draft for client review', $live);
        foreach ($drafts as $slug) {
            $this->assertStringNotContainsString("/collaborations/{$slug}/", $live);   // prev/next never link a draft
        }
    }
}
