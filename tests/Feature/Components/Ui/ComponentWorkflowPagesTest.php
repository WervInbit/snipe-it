<?php

namespace Tests\Feature\Components\Ui;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentWorkflowPagesTest extends TestCase
{
    use RefreshDatabase;

    public function testTrayPageShowsOnlyCurrentUserTransferItemsAndNavBadge(): void
    {
        $user = User::factory()->superuser()->create();
        $otherUser = User::factory()->superuser()->create();

        $myTrayComponent = ComponentInstance::factory()->inTray($user)->create([
            'display_name' => 'My Tray Part',
        ]);

        ComponentInstance::factory()->inTray($otherUser)->create([
            'display_name' => 'Other Tray Part',
        ]);

        $this->actingAs($user)
            ->get(route('components.tray'))
            ->assertOk()
            ->assertSeeText('My Tray Part')
            ->assertDontSeeText('Other Tray Part')
            ->assertSee(route('components.show', $myTrayComponent), false)
            ->assertSee(route('components.install.create', [$myTrayComponent, 'return_to' => route('components.tray')]), false)
            ->assertSeeText('To Storage')
            ->assertDontSeeText('Move To Stock');

        $this->actingAs($user)
            ->get(route('components.index'))
            ->assertOk()
            ->assertSeeText('My Tray');
    }

    public function testAssetComponentTabShowsOperationalActions(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $definition = ComponentDefinition::factory()->create(['name' => 'Expected RAM']);
        $modelNumber = ModelNumber::factory()->create();
        $asset->forceFill(['model_number_id' => $modelNumber->id])->save();

        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
            'expected_name' => 'Expected RAM',
            'slot_name' => 'DIMM A',
        ]);

        $installedComponent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Installed Browser Part',
        ]);

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('Add / Install Component')
            ->assertSeeText('Current Components')
            ->assertDontSeeText('Installed As')
            ->assertSeeText('To Tray')
            ->assertSeeText('To Storage')
            ->assertSee('id="assetComponentStorageModal"', false)
            ->assertSee(route('hardware.components.storage.store', [$asset, $installedComponent]), false)
            ->assertSeeText('Move To Other Device')
            ->assertSeeText('Open');
    }

    public function testAssetAddPageShowsDefinitionCustomToggleForNewComponents(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        ComponentDefinition::factory()->create(['name' => '16GB DDR4']);
        $trayComponent = ComponentInstance::factory()->inTray($user)->create([
            'display_name' => 'Tray DIMM',
        ]);
        $stockComponent = ComponentInstance::factory()->create([
            'status' => ComponentInstance::STATUS_IN_STOCK,
            'current_asset_id' => null,
            'display_name' => 'Stock DIMM',
        ]);

        $this->actingAs($user)
            ->get(route('hardware.components.add', $asset))
            ->assertOk()
            ->assertSeeText('Install')
            ->assertDontSeeText('From Tray')
            ->assertDontSeeText('From Storage')
            ->assertSeeText('Search tray or storage components')
            ->assertSeeText('[Tray] ' . $trayComponent->component_tag . ' - Tray DIMM')
            ->assertSeeText('[Storage] ' . $stockComponent->component_tag . ' - Stock DIMM')
            ->assertDontSee('name="installed_as"', false)
            ->assertSeeText('Use Component Definition')
            ->assertSeeText('Custom Component')
            ->assertSee('name="creation_mode"', false)
            ->assertSee('data-component-mode-choice', false)
            ->assertSeeText('Show New Component Form');
    }

    public function testInstallTransferAndStorageWorkflowScreensHideLegacyFields(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create();
        $targetAsset = Asset::factory()->create();
        $component = ComponentInstance::factory()->inTray($user)->create([
            'display_name' => 'Tray DIMM',
            'installed_as' => 'DIMM A',
        ]);
        $installedComponent = ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Installed DIMM',
            'installed_as' => 'DIMM B',
        ]);
        $definition = ComponentDefinition::factory()->create(['name' => 'Expected RAM']);
        $modelNumber = ModelNumber::factory()->create();
        $asset->forceFill(['model_number_id' => $modelNumber->id])->save();
        $template = ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $definition->id,
            'expected_name' => 'Expected RAM',
        ]);

        $this->actingAs($user)
            ->get(route('components.install.create', [$component, 'return_to' => route('components.tray')]))
            ->assertOk()
            ->assertDontSee('name="installed_as"', false)
            ->assertDontSeeText('Installed As');

        $this->actingAs($user)
            ->get(route('components.move_to_stock.create', [$component, 'return_to' => route('components.tray')]))
            ->assertOk()
            ->assertDontSee('name="storage_location_id"', false)
            ->assertDontSee('name="verification_location_id"', false)
            ->assertDontSeeText('Stock Location')
            ->assertDontSeeText('Verification Location');

        $this->actingAs($user)
            ->get(route('hardware.components.transfer.create', [$asset, $installedComponent, 'destination_asset_id' => $targetAsset->id]))
            ->assertOk()
            ->assertDontSee('name="installed_as"', false)
            ->assertDontSeeText('Installed As');

        $this->actingAs($user)
            ->get(route('hardware.components.storage.create', [$asset, $installedComponent]))
            ->assertOk()
            ->assertDontSee('name="storage_location_id"', false)
            ->assertDontSee('name="verification_location_id"', false)
            ->assertDontSeeText('Stock Location')
            ->assertDontSeeText('Verification Location');

        $this->actingAs($user)
            ->get(route('hardware.components.expected.transfer.create', [$asset, $template, 'destination_asset_id' => $targetAsset->id]))
            ->assertOk()
            ->assertDontSee('name="installed_as"', false)
            ->assertDontSeeText('Installed As');

        $this->actingAs($user)
            ->get(route('hardware.components.expected.storage.create', [$asset, $template]))
            ->assertOk()
            ->assertDontSee('name="storage_location_id"', false)
            ->assertDontSee('name="verification_location_id"', false)
            ->assertDontSeeText('Stock Location')
            ->assertDontSeeText('Verification Location');
    }
}
