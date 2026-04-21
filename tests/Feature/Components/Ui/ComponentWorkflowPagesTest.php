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
            ->assertSee(route('components.show', $myTrayComponent), false);

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

        ComponentInstance::factory()->installed($asset->id)->create([
            'display_name' => 'Installed Browser Part',
        ]);

        $this->actingAs($user)
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('Install From Tray')
            ->assertSeeText('Install Existing')
            ->assertSeeText('Register Component')
            ->assertSeeText('Extract Component')
            ->assertSeeText('Remove To Tray')
            ->assertSeeText('Open');
    }
}
