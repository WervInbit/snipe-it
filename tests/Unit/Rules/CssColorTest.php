<?php

namespace Tests\Unit\Rules;

use App\Rules\CssColor;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CssColorTest extends TestCase
{
    #[DataProvider('validColors')]
    public function testValidColorsAreAccepted(string $color): void
    {
        $failed = false;

        (new CssColor)->validate('color', $color, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
        $this->assertSame($color, CssColor::sanitize($color, '#000000'));
    }

    #[DataProvider('invalidColors')]
    public function testUnsafeValuesAreRejectedAndSanitized(string $color): void
    {
        $failed = false;

        (new CssColor)->validate('color', $color, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
        $this->assertSame('#000000', CssColor::sanitize($color, '#000000'));
    }

    public static function validColors(): array
    {
        return [
            'short hex' => ['#fff'],
            'long hex' => ['#3c8dbc'],
            'rgb' => ['rgb(10,20,30)'],
            'rgba' => ['rgba(10,20,30,0.5)'],
            'hsl' => ['hsl(120,50%,50%)'],
        ];
    }

    public static function invalidColors(): array
    {
        return [
            'named color' => ['red'],
            'declaration injection' => ['#fff; background: url(https://example.invalid/steal)'],
            'expression' => ['expression(alert(1))'],
            'line break' => ["#fff\nbody { color: red; }"],
            'javascript' => ['javascript:alert(1)'],
        ];
    }
}
