<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\ComponentInstance;
use App\Models\Statuslabel;
use App\Models\User;
use Database\Seeders\ProductionStatusLabelSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatusLifecycleSemanticsTest extends TestCase
{
    public function test_generic_deployable_status_does_not_gain_sale_controls_from_its_type_or_name(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => false,
        ]);
        $ordinaryDeployable = Statuslabel::factory()->rtd()->create([
            'name' => 'Selling preparation ' . Str::uuid(),
            'lifecycle_stage' => null,
            'default_label' => 0,
        ]);

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $ordinaryDeployable->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertSame($ordinaryDeployable->id, $asset->fresh()->status_id);
    }

    public function test_renamed_ready_for_sale_status_still_requires_sale_permission_and_returns_json_error(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
        ]);
        $ready = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'Klaar voor overdracht',
            ['deployable' => 1]
        );

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $ready->id,
            ])
            ->assertForbidden()
            ->assertStatusMessageIs('error');

        $this->assertNotSame($ready->id, $asset->fresh()->status_id);
    }

    public function test_api_readiness_acknowledgement_failure_is_json_with_422_contract(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => false,
        ]);
        $ready = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'Verkoopgereed',
            ['deployable' => 1]
        );

        $this->actingAsForApi($this->saleEditor())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $ready->id,
            ])
            ->assertStatus(422)
            ->assertStatusMessageIs('error');

        $this->assertNotSame($ready->id, $asset->fresh()->status_id);
    }

    public function test_api_selling_status_requires_attached_component_issue_acknowledgement(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
        ]);
        ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Cracked API display',
            'condition_code' => ComponentInstance::CONDITION_BROKEN,
            'condition_status' => ComponentInstance::CONDITION_STATUS_DAMAGED,
        ]);
        $ready = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'API verkoopgereed',
            ['deployable' => 1]
        );
        $actor = $this->saleEditor();

        $this->actingAsForApi($actor)
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $ready->id,
                'ack_failed_tests' => true,
            ])
            ->assertUnprocessable()
            ->assertStatusMessageIs('error')
            ->assertJsonPath(
                'payload.component_issue_details.0',
                'Cracked API display - Damaged'
            );

        $this->assertNotSame($ready->id, $asset->fresh()->status_id);

        $this->actingAsForApi($actor)
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $ready->id,
                'ack_failed_tests' => true,
                'ack_component_issues' => true,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertSame($ready->id, $asset->fresh()->status_id);
    }

    public function test_api_non_deployable_status_clears_retired_assignment_state(): void
    {
        $assignedUser = User::factory()->create();
        $asset = Asset::factory()->assignedToUser($assignedUser)->create([
            'status_id' => $this->pendingStatus()->id,
            'accepted' => 'pending',
            'expected_checkin' => now()->addDay(),
        ]);
        $acceptance = CheckoutAcceptance::factory()->pending()->create([
            'checkoutable_type' => Asset::class,
            'checkoutable_id' => $asset->id,
            'assigned_to_id' => $assignedUser->id,
        ]);
        $retired = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'API onderdelen',
            ['archived' => 1]
        );

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $retired->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $asset->refresh();
        $this->assertSame($retired->id, $asset->status_id);
        $this->assertNull($asset->assigned_to);
        $this->assertNull($asset->assigned_type);
        $this->assertNull($asset->accepted);
        $this->assertNull($asset->expected_checkin);
        $this->assertSoftDeleted($acceptance);
    }

    public function test_renamed_sold_returned_and_broken_stages_keep_their_behavior(): void
    {
        $asset = Asset::factory()->create([
            'status_id' => $this->pendingStatus()->id,
            'tests_completed_ok' => true,
            'is_sellable' => true,
        ]);
        $sold = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_SOLD,
            'Uitgeleverd',
            ['archived' => 1]
        );

        $this->actingAsForApi($this->saleEditor())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $sold->id,
                'ack_failed_tests' => true,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $asset->refresh();
        $this->assertSame(1, (int) $asset->archived);
        $this->assertFalse((bool) $asset->is_sellable);

        $returned = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_RETURNED,
            'Terug van klant',
            ['pending' => 1]
        );

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $returned->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertSame(0, (int) $asset->fresh()->archived);

        $asset->is_sellable = true;
        $asset->save();
        $broken = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_BROKEN_PARTS,
            'Niet herstelbaar',
            []
        );

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->patchJson(route('api.assets.update', $asset), [
                'status_id' => $broken->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertFalse((bool) $asset->fresh()->is_sellable);
    }

    public function test_production_status_seeding_preserves_operator_renames_and_fields(): void
    {
        Statuslabel::withTrashed()
            ->where('lifecycle_stage', Statuslabel::LIFECYCLE_READY_FOR_SALE)
            ->get()
            ->each
            ->forceDelete();

        $ready = $this->lifecycleStatus(
            Statuslabel::LIFECYCLE_READY_FOR_SALE,
            'Door QA vrijgegeven',
            [
                'deployable' => 1,
                'notes' => 'Operator-owned wording',
                'color' => '#123456',
            ]
        );
        $operatorName = $ready->name;

        $this->seed(ProductionStatusLabelSeeder::class);

        $ready->refresh();
        $this->assertSame($operatorName, $ready->name);
        $this->assertSame('Operator-owned wording', $ready->notes);
        $this->assertSame('#123456', $ready->color);
        $this->assertSame(
            1,
            Statuslabel::withTrashed()
                ->where('lifecycle_stage', Statuslabel::LIFECYCLE_READY_FOR_SALE)
                ->count()
        );
    }

    public function test_lifecycle_migration_maps_known_legacy_aliases_and_is_idempotent(): void
    {
        $legacy = Statuslabel::factory()->archived()->create([
            'name' => 'Sold to Customer',
            'lifecycle_stage' => null,
            'default_label' => 0,
        ]);
        $migration = require database_path('migrations/2026_07_23_130000_add_lifecycle_stage_to_status_labels.php');

        $migration->up();
        $migration->up();

        $this->assertSame(Statuslabel::LIFECYCLE_SOLD, $legacy->fresh()->lifecycle_stage);
    }

    private function lifecycleStatus(string $stage, string $name, array $overrides): Statuslabel
    {
        return Statuslabel::factory()->create(array_merge([
            'name' => $name . ' ' . Str::uuid(),
            'lifecycle_stage' => $stage,
            'deployable' => 0,
            'pending' => 0,
            'archived' => 0,
            'default_label' => 0,
        ], $overrides));
    }

    private function pendingStatus(): Statuslabel
    {
        return Statuslabel::factory()->pending()->create([
            'name' => 'Pending ' . Str::uuid(),
            'default_label' => 0,
        ]);
    }

    private function saleEditor(): User
    {
        return User::factory()->create([
            'permissions' => json_encode([
                'assets.edit' => '1',
                'assets.sale_transition' => '1',
            ]),
        ]);
    }
}
