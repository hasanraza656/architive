<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeoQuickWinsTest extends TestCase
{
    use RefreshDatabase;

    public function test_llms_txt_exists_and_links_only_to_real_pages(): void
    {
        $file = public_path('llms.txt');
        $this->assertFileExists($file);
        $text = file_get_contents($file);
        $this->assertStringStartsWith('# Architive', $text);

        preg_match_all('~\]\(https://architive\.net(/[^)]*)\)~', $text, $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $path) {
            if (str_starts_with($path, '/blog/') && $path !== '/blog/') {
                continue; // articles live in the database, checked on the live site after deploy
            }
            $res = $this->app->handle(Request::create($path));
            $this->assertSame(200, $res->getStatusCode(), $path);
        }
    }

    public function test_website_schema_has_a_search_action_for_the_blog(): void
    {
        $html = $this->app->handle(Request::create('/'))->getContent();
        $this->assertStringContainsString('"@type":"SearchAction"', $html);
        $this->assertStringContainsString('/blog/?q={search_term_string}', $html);
    }

    public function test_organization_schema_lists_social_profiles_in_same_as(): void
    {
        $html = $this->app->handle(Request::create('/'))->getContent();
        $this->assertStringContainsString('"sameAs":[', $html);
        $this->assertStringContainsString('https://www.linkedin.com/company/architive', $html);
    }

    public function test_client_login_links_are_nofollow(): void
    {
        $html = $this->app->handle(Request::create('/'))->getContent();
        preg_match_all('~<a[^>]+href="[^"]*/account/login[^"]*"[^>]*>~', $html, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $tag) {
            if (str_contains($tag, 'class="client-link') || str_contains($tag, 'mobile-menu__mail')) {
                $this->assertStringContainsString('rel="nofollow"', $tag);
            }
        }
    }
}
