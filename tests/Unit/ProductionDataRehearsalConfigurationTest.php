<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductionDataRehearsalConfigurationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__.'/../..');
    }

    public function test_rehearsal_compose_is_isolated_and_uses_external_secrets(): void
    {
        $compose = file_get_contents($this->basePath.'/docker-compose.rehearsal.yml');

        $this->assertStringContainsString(
            'mariadb:11.4.7@sha256:39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06',
            $compose
        );
        $this->assertStringContainsString('redis@sha256:', $compose);
        $this->assertStringContainsString('MARIADB_PASSWORD_FILE: /run/secrets/db_password', $compose);
        $this->assertStringContainsString('MARIADB_ROOT_PASSWORD_FILE: /run/secrets/db_root_password', $compose);
        $this->assertStringContainsString('--event-scheduler=OFF', $compose);
        $this->assertStringContainsString('DB_HOST: db', $compose);
        $this->assertStringContainsString('REDIS_HOST: redis', $compose);
        $this->assertStringContainsString(
            '${REHEARSAL_BIND_ADDRESS:-127.0.0.1}:${SNIPEIT_HTTPS_PORT:-18443}:8443',
            $compose
        );
        $this->assertStringContainsString('SNIPEIT_DB_VOLUME:', $compose);
        $this->assertStringContainsString('SNIPEIT_REDIS_VOLUME:', $compose);
        $this->assertStringContainsString('SNIPEIT_TLS_VOLUME:', $compose);
        $this->assertStringContainsString('edge_tls_init:', $compose);
        $this->assertSame(
            2,
            substr_count(
                $compose,
                'image: ${SNIPEIT_WEB_IMAGE:?Set the web image repository}@${SNIPEIT_WEB_IMAGE_DIGEST:?Set the immutable web image sha256 digest}',
            ),
        );
        $this->assertStringContainsString('condition: service_completed_successfully', $compose);
        $this->assertStringContainsString('chmod 0440', $compose);
        $this->assertStringContainsString('source: rehearsal_tls_data', $compose);
        $this->assertStringNotContainsString('MYSQL_PASSWORD:', $compose);
        $this->assertStringNotContainsString('MYSQL_ROOT_PASSWORD:', $compose);
        $this->assertStringNotContainsString('3306:3306', $compose);

        $proxy = file_get_contents($this->basePath.'/docker/rehearsal/nginx.conf');

        $this->assertStringContainsString('proxy_set_header Host $http_host;', $proxy);
        $this->assertStringContainsString('proxy_set_header X-Forwarded-Host $http_host;', $proxy);
        $this->assertStringNotContainsString('X-Forwarded-Port $server_port', $proxy);
    }

    public function test_runtime_preparation_rejects_non_rehearsal_targets(): void
    {
        $script = file_get_contents($this->basePath.'/scripts/rehearsal/prepare-runtime.ps1');

        $this->assertStringContainsString(
            "^snipeit-v1-data-rehearsal-[a-z0-9-]+$",
            $script
        );
        $this->assertStringContainsString(
            "^snipeit_rehearsal(?:_[a-z0-9_]+)?$",
            $script
        );
        $this->assertStringContainsString("'LDAP_INTEGRATION_ENABLED=false'", $script);
        $this->assertStringContainsString("'MAIL_ENABLED=false'", $script);
        $this->assertStringContainsString(
            "('PASSPORT_PRIVATE_KEY_FILE=' + (Convert-ToComposePath",
            $script
        );
        $this->assertStringContainsString(
            "('REHEARSAL_EXPORT_PATH=' + (Convert-ToComposePath",
            $script
        );
        $this->assertStringContainsString('[switch] $Force', $script);
        $this->assertStringContainsString(
            'The runtime path already contains managed files.',
            $script
        );
        $this->assertStringContainsString(
            '$existingManagedFiles.Count -gt 0 -and -not $Force',
            $script
        );
        $this->assertStringNotContainsString('Write-Host $appKey', $script);
        $this->assertStringNotContainsString('Write-Output $appKey', $script);
    }
}
