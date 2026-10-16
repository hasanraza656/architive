<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OldUrlRedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_old_address_redirects_permanently_to_its_target(): void
    {
        foreach (config('redirects') as $from => $to) {
            foreach (['/' . $from, '/' . $from . '/'] as $path) {
                $this->get($path)
                    ->assertStatus(301)
                    ->assertRedirect($to);
            }
        }
    }

    public function test_redirect_targets_are_written_as_final_addresses(): void
    {
        foreach (array_unique(array_values(config('redirects'))) as $to) {
            $this->assertStringStartsWith('/', $to);
            $this->assertStringEndsWith('/', $to);
        }
    }

    public function test_page_targets_exist_and_answer_directly(): void
    {
        // Blog targets need articles in the database, so only the fixed pages are checked here.
        foreach (array_unique(array_values(config('redirects'))) as $to) {
            if (str_starts_with($to, '/blog/')) {
                continue;
            }
            $res = $this->app->handle(Request::create($to));
            $this->assertSame(200, $res->getStatusCode(), $to);
        }
    }
}
