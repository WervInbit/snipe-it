<?php

namespace Tests\Unit\Support;

use App\Support\RouteLink;
use Tests\TestCase;

class RouteLinkTest extends TestCase
{
    public function testRouteLinksEscapeTheirLabels(): void
    {
        $link = RouteLink::to('hardware.show', '<script>alert(1)</script>', 123);

        $this->assertStringContainsString(e(route('hardware.show', 123)), $link);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $link);
        $this->assertStringNotContainsString('<script>', $link);
    }
}
