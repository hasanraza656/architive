<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_https_responses_carry_hsts(): void
    {
        $res = $this->get('https://localhost');
        $res->assertOk();
        $this->assertSame('max-age=31536000', $res->headers->get('Strict-Transport-Security'));
    }

    public function test_plain_http_responses_do_not_carry_hsts(): void
    {
        $this->get('/')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_htaccess_sends_www_to_the_plain_domain(): void
    {
        $rules = file_get_contents(public_path('.htaccess'));
        $this->assertStringContainsString('RewriteCond %{HTTP_HOST} ^www\.architive\.net$ [NC]', $rules);
        $this->assertStringContainsString('RewriteRule ^(.*)$ https://architive.net/$1 [R=301,L]', $rules);
        // the www rule must come before Laravel's front-controller rule or it never runs
        $this->assertLessThan(strpos($rules, 'RewriteRule ^ index.php'), strpos($rules, 'www\.architive'));
    }
}
