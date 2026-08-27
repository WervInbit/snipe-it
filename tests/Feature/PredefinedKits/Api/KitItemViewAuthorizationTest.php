<?php

namespace Tests\Feature\PredefinedKits\Api;

use App\Models\Accessory;
use App\Models\AssetModel;
use App\Models\Consumable;
use App\Models\License;
use App\Models\PredefinedKit;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KitItemViewAuthorizationTest extends TestCase
{
    #[DataProvider('kitItemProvider')]
    public function test_attaching_an_item_requires_permission_to_view_the_target(
        string $kind,
        string $routeSegment,
        string $payloadKey,
        string $pivotTable,
        string $pivotColumn,
        string $viewPermission
    ): void {
        $kit = PredefinedKit::factory()->create();
        $item = $this->createKitItem($kind);
        $actor = $this->userWithPermissions(['kits.edit']);

        $this->actingAsForApi($actor)
            ->postJson(
                route("api.kits.{$routeSegment}.store", ['kit_id' => $kit->id]),
                [$payloadKey => $item->id, 'quantity' => 1]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing($pivotTable, [
            'kit_id' => $kit->id,
            $pivotColumn => $item->id,
        ]);
    }

    #[DataProvider('kitItemProvider')]
    public function test_updating_an_item_requires_permission_to_view_the_target(
        string $kind,
        string $routeSegment,
        string $payloadKey,
        string $pivotTable,
        string $pivotColumn,
        string $viewPermission
    ): void {
        $kit = PredefinedKit::factory()->create();
        $item = $this->createKitItem($kind);
        $actor = $this->userWithPermissions(['kits.edit']);

        $this->actingAsForApi($actor)
            ->putJson(
                route("api.kits.{$routeSegment}.update", [
                    'kit_id' => $kit->id,
                    $pivotColumn => $item->id,
                ]),
                ['quantity' => 5]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing($pivotTable, [
            'kit_id' => $kit->id,
            $pivotColumn => $item->id,
        ]);
    }

    #[DataProvider('kitItemProvider')]
    public function test_attaching_and_updating_an_item_succeeds_with_both_permissions(
        string $kind,
        string $routeSegment,
        string $payloadKey,
        string $pivotTable,
        string $pivotColumn,
        string $viewPermission
    ): void {
        $kit = PredefinedKit::factory()->create();
        $item = $this->createKitItem($kind);
        $actor = $this->userWithPermissions(['kits.edit', $viewPermission]);

        $this->actingAsForApi($actor)
            ->postJson(
                route("api.kits.{$routeSegment}.store", ['kit_id' => $kit->id]),
                [$payloadKey => $item->id, 'quantity' => 1]
            )
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->actingAsForApi($actor)
            ->putJson(
                route("api.kits.{$routeSegment}.update", [
                    'kit_id' => $kit->id,
                    $pivotColumn => $item->id,
                ]),
                ['quantity' => 5]
            )
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertDatabaseHas($pivotTable, [
            'kit_id' => $kit->id,
            $pivotColumn => $item->id,
            'quantity' => 5,
        ]);
    }

    public static function kitItemProvider(): array
    {
        return [
            'license' => ['license', 'licenses', 'license', 'kits_licenses', 'license_id', 'licenses.view'],
            'asset model' => ['model', 'models', 'model', 'kits_models', 'model_id', 'models.view'],
            'consumable' => ['consumable', 'consumables', 'consumable', 'kits_consumables', 'consumable_id', 'consumables.view'],
            'accessory' => ['accessory', 'accessories', 'accessory', 'kits_accessories', 'accessory_id', 'accessories.view'],
        ];
    }

    private function createKitItem(string $kind): Accessory|AssetModel|Consumable|License
    {
        return match ($kind) {
            'license' => License::factory()->create(),
            'model' => AssetModel::factory()->create(),
            'consumable' => Consumable::factory()->create(),
            'accessory' => Accessory::factory()->create(),
        };
    }

    /**
     * @param array<int, string> $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        return User::factory()->create([
            'permissions' => json_encode(array_fill_keys($permissions, '1')),
        ]);
    }
}
