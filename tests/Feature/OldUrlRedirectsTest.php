<?php

namespace Tests\Feature;

use Tests\TestCase;

class OldUrlRedirectsTest extends TestCase
{
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

    public function test_redirect_targets_are_not_themselves_redirected_or_missing(): void
    {
        foreach (array_unique(array_values(config('redirects'))) as $to) {
            $this->assertStringStartsWith('/', $to);
            $this->assertStringEndsWith('/', $to);
        }
    }
}
