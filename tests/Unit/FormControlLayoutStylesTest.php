<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FormControlLayoutStylesTest extends TestCase
{
    public function test_bootstrap_checkbox_and_radio_labels_keep_custom_controls_in_normal_flow(): void
    {
        $basePath = realpath(__DIR__.'/../..');
        $styles = file_get_contents($basePath.'/resources/assets/less/overrides.less');

        $this->assertIsString($styles);
        $this->assertMatchesRegularExpression(
            '/\.checkbox\s*>\s*label,.*?label\.checkbox-inline.*?display:\s*inline-flex;.*?gap:\s*0\.6em;.*?padding-left:\s*0;/s',
            $styles,
        );
        $this->assertMatchesRegularExpression(
            '/\.checkbox\s*>\s*label\s*>\s*input\[type="checkbox"\],.*?label\.radio-inline\s*>\s*input\[type="radio"\].*?position:\s*static;.*?flex:\s*0\s+0\s+auto;.*?margin:\s*0;/s',
            $styles,
        );
    }
}
