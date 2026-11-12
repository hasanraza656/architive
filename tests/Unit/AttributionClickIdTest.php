<?php

namespace Tests\Unit;

use App\Support\Attribution;
use PHPUnit\Framework\TestCase;

class AttributionClickIdTest extends TestCase
{
    public function test_gclid_is_listed_for_offline_import(): void
    {
        $lines = Attribution::lines(json_encode(['last' => ['utm_source' => 'google', 'gclid' => 'Cj0KCQiAabc123_defGHI-456'], 'first' => []]));
        $this->assertSame('gclid Cj0KCQiAabc123_defGHI-456', $lines['Google click ID']);
    }

    public function test_unsafe_click_id_is_ignored(): void
    {
        $lines = Attribution::lines(json_encode(['last' => ['gclid' => '<script>alert(1)</script>']]));
        $this->assertArrayNotHasKey('Google click ID', $lines);
    }
}
