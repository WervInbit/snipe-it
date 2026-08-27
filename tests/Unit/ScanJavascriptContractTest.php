<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ScanJavascriptContractTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $basePath = realpath(__DIR__ . '/../..');
        $this->source = file_get_contents($basePath . '/resources/js/scan/index.js');
    }

    public function testScannerUsesOneZxingOwnedStreamAndRetainsItsStopControls(): void
    {
        $this->assertStringContainsString('decodeFromConstraints', $this->source);
        $this->assertStringNotContainsString('decodeFromVideoDevice(', $this->source);
        $this->assertStringContainsString('scannerControls = controls', $this->source);
        $this->assertStringContainsString('controls.stop()', $this->source);
        $this->assertStringContainsString('scanSession', $this->source);
        $this->assertStringContainsString('startQueue', $this->source);
        $this->assertStringContainsString('request !== startRequest', $this->source);
        $this->assertStringContainsString('session !== scanSession', $this->source);
        $this->assertStringNotContainsString('reader.reset()', $this->source);
    }

    public function testCloseRangeTuningIsCapabilityGatedAndNeverForcesZoom(): void
    {
        $this->assertStringContainsString('track.getCapabilities()', $this->source);
        $this->assertStringContainsString("focusModes.includes('continuous')", $this->source);
        $this->assertStringContainsString("advanced: [{ focusMode: 'continuous' }]", $this->source);
        $this->assertStringNotContainsString('focusDistance:', $this->source);
        $this->assertStringNotContainsString('zoom:', $this->source);
        $this->assertStringNotContainsString('failBeforeFallback', $this->source);
    }

    public function testScannerHasExplicitFeedbackAndNavigationRecoveryStates(): void
    {
        $contracts = [
            "setStatus('starting'",
            "setStatus('scanning'",
            "setStatus('no-code'",
            "setStatus('retrying'",
            "setStatus('paused'",
            "setStatus('success'",
            'recoverFromNavigationFailure',
        ];

        foreach ($contracts as $contract) {
            $this->assertStringContainsString($contract, $this->source);
        }
    }

    public function testBothScannerViewsExposeTheSharedStateControls(): void
    {
        $basePath = realpath(__DIR__ . '/../..');
        $viewPaths = [
            '/resources/views/scan/index.blade.php',
            '/resources/views/components/asset-transfer.blade.php',
        ];

        foreach ($viewPaths as $viewPath) {
            $view = file_get_contents($basePath . $viewPath);

            $this->assertStringContainsString('id="scan-status"', $view);
            $this->assertStringContainsString('id="scan-refocus"', $view);
            $this->assertStringContainsString('scan_status_no_code', $view);
            $this->assertStringContainsString('scan_navigation_failed', $view);
        }
    }
}
