<?php

namespace Tests\Feature\Maintenances;

use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceReadOnlyContractTest extends TestCase
{
    public function testOnlyHistoricalReadRoutesAreRegistered(): void
    {
        $this->assertTrue(Route::has('maintenances.index'));
        $this->assertTrue(Route::has('maintenances.show'));
        $this->assertTrue(Route::has('api.maintenances.index'));
        $this->assertTrue(Route::has('api.maintenances.show'));
        $this->assertTrue(Route::has('ui.reports.maintenances'));
        $this->assertTrue(Route::has('reports/export/maintenances'));

        foreach ([
            'maintenances.create',
            'maintenances.store',
            'maintenances.edit',
            'maintenances.update',
            'maintenances.destroy',
            'api.maintenances.store',
            'api.maintenances.update',
            'api.maintenances.destroy',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), "{$routeName} must not be registered.");
        }
    }

    public function testWebAndApiMaintenanceMutationsAreUnavailable(): void
    {
        $maintenance = Maintenance::factory()->create(['name' => 'Historical maintenance']);
        $actor = User::factory()->superuser()->create();
        $payload = ['name' => 'Mutation must not persist'];

        $this->actingAs($actor)
            ->post('/maintenances', $payload)
            ->assertStatus(405);
        $this->actingAs($actor)
            ->put("/maintenances/{$maintenance->id}", $payload)
            ->assertStatus(405);
        $this->actingAs($actor)
            ->patch("/maintenances/{$maintenance->id}", $payload)
            ->assertStatus(405);
        $this->actingAs($actor)
            ->delete("/maintenances/{$maintenance->id}")
            ->assertStatus(405);
        $this->actingAs($actor)
            ->get('/maintenances/create')
            ->assertNotFound();
        $this->actingAs($actor)
            ->get("/maintenances/{$maintenance->id}/edit")
            ->assertNotFound();

        $this->actingAsForApi($actor)
            ->postJson('/api/v1/maintenances', $payload)
            ->assertStatus(405);
        $this->actingAsForApi($actor)
            ->putJson("/api/v1/maintenances/{$maintenance->id}", $payload)
            ->assertStatus(405);
        $this->actingAsForApi($actor)
            ->patchJson("/api/v1/maintenances/{$maintenance->id}", $payload)
            ->assertStatus(405);
        $this->actingAsForApi($actor)
            ->deleteJson("/api/v1/maintenances/{$maintenance->id}")
            ->assertStatus(405);
        $this->actingAs($actor, 'web')
            ->from('/hardware')
            ->post(route('hardware/bulkedit'), [
                'ids' => [$maintenance->asset_id],
                'bulk_actions' => 'maintenance',
            ])
            ->assertRedirect('/hardware')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('maintenances', [
            'id' => $maintenance->id,
            'name' => 'Historical maintenance',
            'deleted_at' => null,
        ]);
    }

    public function testPrivilegedUsersCannotBypassReadOnlyMaintenancePolicy(): void
    {
        $maintenance = Maintenance::factory()->create();

        foreach ([
            User::factory()->admin()->create(),
            User::factory()->superuser()->create(),
        ] as $privilegedUser) {
            foreach (['create', 'update', 'delete', 'manage', 'createFiles', 'deleteFiles'] as $ability) {
                $this->assertTrue(
                    Gate::forUser($privilegedUser)->denies($ability, $maintenance),
                    "{$ability} must be denied for privileged users."
                );
            }
        }
    }

    public function testHistoricalMaintenanceReadsStillRequireAssetViewPermission(): void
    {
        $maintenance = Maintenance::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $this->actingAs($unauthorizedUser)
            ->get(route('maintenances.index'))
            ->assertForbidden();
        $this->actingAs($unauthorizedUser)
            ->get(route('maintenances.show', $maintenance))
            ->assertForbidden();
        $this->actingAsForApi($unauthorizedUser)
            ->getJson(route('api.maintenances.index'))
            ->assertForbidden();
        $this->actingAsForApi($unauthorizedUser)
            ->getJson(route('api.maintenances.show', $maintenance))
            ->assertForbidden();
    }

    public function testHistoricalMaintenanceDataAndFilesRemainReadableButNotMutable(): void
    {
        Storage::fake(config('filesystems.default'));

        $maintenance = Maintenance::factory()->create(['name' => 'Historical maintenance']);
        $actor = User::factory()->superuser()->create();

        $this->actingAs($actor);
        $log = $maintenance->logUpload('historical-maintenance.txt', 'Imported history');
        Storage::put($log->uploads_file_path(), 'historical maintenance attachment');

        $this->get(route('maintenances.index'))->assertOk();
        $this->get(route('maintenances.show', $maintenance))
            ->assertOk()
            ->assertSee('Historical maintenance')
            ->assertDontSee('uploadFileModal');
        $this->get(route('hardware.show', $maintenance->asset))
            ->assertOk()
            ->assertDontSee('href="#maintenances"', false);
        $this->get(route('hardware.index'))
            ->assertOk()
            ->assertDontSee('value="maintenance"', false);

        $this->actingAsForApi($actor)
            ->getJson(route('api.maintenances.index'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.available_actions.update', false)
            ->assertJsonPath('rows.0.available_actions.delete', false);
        $this->actingAsForApi($actor)
            ->getJson(route('api.maintenances.show', $maintenance))
            ->assertOk()
            ->assertJsonPath('id', $maintenance->id)
            ->assertJsonPath('available_actions.update', false)
            ->assertJsonPath('available_actions.delete', false);

        $this->actingAsForApi($actor)
            ->getJson(route('api.files.index', [
                'object_type' => 'maintenances',
                'id' => $maintenance->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.available_actions.delete', false);

        $this->actingAsForApi($actor)
            ->get(route('api.files.show', [
                'object_type' => 'maintenances',
                'id' => $maintenance->id,
                'file_id' => $log->id,
            ]))
            ->assertOk();

        $this->actingAsForApi($actor)
            ->post('/api/v1/maintenances/'.$maintenance->id.'/files', [
                'file' => [],
            ])
            ->assertStatus(405);
        $this->actingAsForApi($actor)
            ->delete('/api/v1/maintenances/'.$maintenance->id.'/files/'.$log->id.'/delete')
            ->assertStatus(405);
        $this->actingAs($actor)
            ->post('/maintenances/'.$maintenance->id.'/files', [
                'file' => [],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('action_logs', [
            'id' => $log->id,
            'deleted_at' => null,
        ]);
        Storage::assertExists($log->uploads_file_path());
    }
}
