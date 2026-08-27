<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckMaintenanceTest extends TestCase
{
    public function test_health_remains_available_while_application_routes_are_in_maintenance(): void
    {
        app()->maintenanceMode()->activate(['retry' => 60]);

        try {
            $this->get(route('health'))
                ->assertOk()
                ->assertExactJson(['status' => 'ok']);

            $this->get('/login')
                ->assertServiceUnavailable()
                ->assertHeader('Retry-After', '60');
        } finally {
            app()->maintenanceMode()->deactivate();
        }
    }
}
