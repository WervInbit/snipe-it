<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReleaseVersionConfigurationTest extends TestCase
{
    public function test_development_version_does_not_impersonate_a_release_artifact(): void
    {
        $version = require __DIR__.'/../../config/version.php';

        $this->assertSame('v0.9.0-dev', $version['app_version']);
        $this->assertSame('dev', $version['prerelease_version']);
        $this->assertSame('unreleased', $version['build_version']);
        $this->assertSame('worktree', $version['hash_version']);
        $this->assertStringContainsString($version['app_version'], $version['full_app_version']);
        $this->assertStringContainsString('unreleased', $version['full_hash']);
    }
}
