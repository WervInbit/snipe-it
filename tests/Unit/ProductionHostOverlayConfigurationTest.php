<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductionHostOverlayConfigurationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = realpath(__DIR__.'/../..');
    }

    public function test_managed_dependencies_are_internal_durable_and_secret_backed(): void
    {
        $compose = file_get_contents(
            $this->basePath.'/docker-compose.production.dependencies.yml'
        );

        $this->assertStringContainsString(
            'mariadb:11.4.7@sha256:39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06',
            $compose,
        );
        $this->assertStringContainsString('redis@sha256:', $compose);
        $this->assertStringContainsString(
            'MARIADB_PASSWORD_FILE: /run/secrets/db_password',
            $compose,
        );
        $this->assertStringContainsString(
            'MARIADB_ROOT_PASSWORD_FILE: /run/secrets/db_root_password',
            $compose,
        );
        $this->assertStringContainsString('DB_HOST: db', $compose);
        $this->assertStringContainsString('REDIS_HOST: redis', $compose);
        $this->assertStringContainsString('condition: service_healthy', $compose);
        $this->assertStringContainsString('production_db_data:/var/lib/mysql', $compose);
        $this->assertStringContainsString('production_redis_data:/data', $compose);
        $this->assertStringContainsString('SNIPEIT_NETWORK_SUBNET', $compose);
        $this->assertStringContainsString('restart: unless-stopped', $compose);
        $this->assertStringNotContainsString('3306:3306', $compose);
        $this->assertStringNotContainsString('6379:6379', $compose);
        $this->assertStringNotContainsString('MARIADB_PASSWORD:', $compose);
        $this->assertStringNotContainsString('MARIADB_ROOT_PASSWORD:', $compose);
    }

    public function test_managed_edge_is_unprivileged_read_only_and_overwrites_proxy_headers(): void
    {
        $compose = file_get_contents($this->basePath.'/docker-compose.production.edge.yml');
        $nginx = file_get_contents($this->basePath.'/docker/production/edge-nginx.conf');

        $this->assertSame(2, substr_count(
            $compose,
            'image: ${SNIPEIT_WEB_IMAGE:?Set the web image repository}@${SNIPEIT_WEB_IMAGE_DIGEST:?Set the immutable web image sha256 digest}',
        ));
        $this->assertStringContainsString('EDGE_BIND_ADDRESS:-0.0.0.0', $compose);
        $this->assertStringContainsString('EDGE_HTTP_PORT:-80', $compose);
        $this->assertStringContainsString('EDGE_HTTPS_PORT:-443', $compose);
        $this->assertStringContainsString('TLS_CERTIFICATE_FILE:', $compose);
        $this->assertStringContainsString('TLS_PRIVATE_KEY_FILE:', $compose);
        $this->assertStringContainsString('chmod 0440 /output/tls.key', $compose);
        $this->assertStringContainsString('network_mode: none', $compose);
        $this->assertStringContainsString('SNIPEIT_NETWORK_SUBNET', $compose);
        $this->assertStringContainsString('condition: service_completed_successfully', $compose);
        $this->assertStringContainsString('read_only: true', $compose);
        $this->assertStringContainsString('no-new-privileges:true', $compose);
        $this->assertStringContainsString('production_edge_tls:', $compose);

        $this->assertStringContainsString('proxy_pass http://web:8080;', $nginx);
        $this->assertStringContainsString(
            'proxy_set_header X-Forwarded-For $remote_addr;',
            $nginx,
        );
        $this->assertStringContainsString(
            'proxy_set_header X-Forwarded-Proto https;',
            $nginx,
        );
        $this->assertStringContainsString('ssl_protocols TLSv1.2 TLSv1.3;', $nginx);
        $this->assertStringContainsString('Permissions-Policy "camera=(self)"', $nginx);
        $this->assertStringNotContainsString('$proxy_add_x_forwarded_for', $nginx);
        $this->assertStringNotContainsString('frigate', strtolower($nginx));
        $this->assertStringNotContainsString('10.10.', $nginx);
    }

    public function test_offline_registry_is_digest_pinned_and_loopback_only(): void
    {
        $compose = file_get_contents($this->basePath.'/docker-compose.production.registry.yml');

        $this->assertStringContainsString(
            'registry@sha256:6c5666b861f3505b116bb9aa9b25175e71210414bd010d92035ff64018f9457e',
            $compose,
        );
        $this->assertStringContainsString(
            '127.0.0.1:${SNIPEIT_REGISTRY_PORT:-5000}:5000',
            $compose,
        );
        $this->assertStringContainsString(
            'production_registry_data:/var/lib/registry',
            $compose,
        );
        $this->assertStringContainsString('read_only: true', $compose);
        $this->assertStringContainsString('no-new-privileges:true', $compose);
        $this->assertStringContainsString('restart: unless-stopped', $compose);
        $this->assertStringNotContainsString('0.0.0.0:', $compose);
    }

    public function test_environment_template_and_validator_cover_optional_host_profiles(): void
    {
        $example = file_get_contents($this->basePath.'/docker/production.env.example');
        $validator = file_get_contents(
            $this->basePath.'/scripts/production/validate-config.sh'
        );
        $runbook = file_get_contents($this->basePath.'/docs/production-deployment.md');

        foreach ([
            'COMPOSE_PROJECT_NAME=',
            'SNIPEIT_DB_VOLUME=',
            'SNIPEIT_REDIS_VOLUME=',
            'SNIPEIT_NETWORK_SUBNET=',
            'DB_ROOT_PASSWORD_FILE=',
            'EDGE_BIND_ADDRESS=',
            'EDGE_HTTP_PORT=',
            'EDGE_HTTPS_PORT=',
            'SNIPEIT_EDGE_TLS_VOLUME=',
            'TLS_CERTIFICATE_FILE=',
            'TLS_PRIVATE_KEY_FILE=',
            'SNIPEIT_REGISTRY_PORT=',
            'SNIPEIT_REGISTRY_VOLUME=',
        ] as $setting) {
            $this->assertStringContainsString($setting, $example);
        }

        $this->assertStringContainsString('--managed-dependencies', $validator);
        $this->assertStringContainsString('--edge', $validator);
        $this->assertStringContainsString('--local-registry', $validator);
        $this->assertStringContainsString('DOCKER_COMPOSE_BIN', $validator);
        $this->assertStringContainsString('docker compose version', $validator);
        $this->assertStringContainsString('command -v docker-compose', $validator);
        $this->assertStringContainsString('Docker Compose v2 is required.', $validator);
        $this->assertStringContainsString('config --quiet', $validator);
        $this->assertStringContainsString('sha256:0{64}', $validator);
        $this->assertStringContainsString('0.0.0.0/0', $validator);
        $this->assertStringContainsString(
            'APP_TRUSTED_PROXIES must include SNIPEIT_NETWORK_SUBNET',
            $validator,
        );
        $this->assertStringNotContainsString('source "$env_file"', $validator);
        $this->assertDoesNotMatchRegularExpression('/\beval\b/', $validator);

        $this->assertStringContainsString('## Choose one production layout', $runbook);
        $this->assertStringContainsString('Never combine', $runbook);
        $this->assertStringContainsString(
            '`docker-compose.rehearsal.yml` with a production deployment',
            $runbook,
        );
        $this->assertStringContainsString(
            'scripts/production/validate-config.sh',
            $runbook,
        );
        $this->assertStringContainsString(
            '### Optional loopback registry for offline transfer',
            $runbook,
        );
        $this->assertStringContainsString(
            '"${production_compose[@]}" run --rm app php artisan migrate --force',
            $runbook,
        );
        $this->assertStringContainsString(
            '"${production_compose[@]}" up -d --no-build --force-recreate',
            $runbook,
        );
    }
}
