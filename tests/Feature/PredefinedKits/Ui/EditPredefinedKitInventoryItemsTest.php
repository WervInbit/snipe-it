<?php

namespace Tests\Feature\PredefinedKits\Ui;

use App\Models\Accessory;
use App\Models\AssetModel;
use App\Models\Consumable;
use App\Models\PredefinedKit;
use App\Models\User;
use Tests\TestCase;

class EditPredefinedKitInventoryItemsTest extends TestCase
{
    public function testAttachedModelEditPageRendersWithAWorkingUpdateForm(): void
    {
        $kit = PredefinedKit::factory()->create();
        $model = AssetModel::factory()->create();
        $kit->models()->attach($model->id, ['quantity' => 2]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('kits.models.edit', [
                'kit' => $kit,
                'model_id' => $model,
            ]))
            ->assertOk()
            ->assertSee(route('kits.models.update', [
                'kit' => $kit,
                'model_id' => $model,
            ]), false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function testAttachedAccessoryEditPageRendersWithAWorkingUpdateForm(): void
    {
        $kit = PredefinedKit::factory()->create();
        $accessory = Accessory::factory()->create();
        $kit->accessories()->attach($accessory->id, ['quantity' => 2]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('kits.accessories.edit', [
                'kit' => $kit,
                'accessory_id' => $accessory,
            ]))
            ->assertOk()
            ->assertSee(route('kits.accessories.update', [
                'kit' => $kit,
                'accessory_id' => $accessory,
            ]), false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function testAttachedConsumableEditPageRendersWithAWorkingUpdateForm(): void
    {
        $kit = PredefinedKit::factory()->create();
        $consumable = Consumable::factory()->create();
        $kit->consumables()->attach($consumable->id, ['quantity' => 2]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('kits.consumables.edit', [
                'kit' => $kit,
                'consumable_id' => $consumable,
            ]))
            ->assertOk()
            ->assertSee(route('kits.consumables.update', [
                'kit' => $kit,
                'consumable_id' => $consumable,
            ]), false)
            ->assertSee('name="_method" value="PUT"', false);
    }
}
