<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WorkflowEvidenceWebServerConfigTest extends TestCase
{
    public function test_all_bundled_webserver_configs_block_direct_legacy_evidence_access(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'docker/nginx/default.conf',
            'docker/nginx.local.conf',
            'docker/nginx.conf',
        ] as $relativePath) {
            $config = file_get_contents($root.'/'.$relativePath);
            $this->assertStringContainsString(
                'location ^~ /uploads/test_images/',
                $config,
                $relativePath
            );
        }

        foreach ([
            'docker/000-default.conf',
            'docker/000-default-2.4.conf',
            'docker/001-default-ssl.conf',
            'ansible/ubuntu/apachevirtualhost.conf.j2',
        ] as $relativePath) {
            $config = file_get_contents($root.'/'.$relativePath);
            $this->assertStringContainsString(
                '/public/uploads/test_images',
                $config,
                $relativePath
            );
            $this->assertStringContainsString('denied', $config, $relativePath);
        }

        foreach (['.htaccess', 'public/.htaccess'] as $relativePath) {
            $config = file_get_contents($root.'/'.$relativePath);
            $this->assertStringContainsString(
                'uploads/test_images',
                $config,
                $relativePath
            );
            $this->assertStringContainsString('[F,L,NC]', $config, $relativePath);
        }
    }
}
