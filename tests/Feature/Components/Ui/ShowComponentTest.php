<?php

namespace Tests\Feature\Components\Ui;

use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Tests\TestCase;

class ShowComponentTest extends TestCase
{
    public function testPageRenders(): void
    {
        $component = ComponentInstance::factory()->inTray(User::factory()->superuser()->create())->create([
            'installed_as' => 'DIMM A',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee(route('components.install.create', [$component, 'return_to' => route('components.show', $component)]), false)
            ->assertSeeText('Status: In Tray')
            ->assertSeeText('In Stock')
            ->assertSeeText('Needs Verification')
            ->assertSeeText('Defective')
            ->assertSeeText('Destruction Pending')
            ->assertDontSee('id="componentToTrayModal"', false)
            ->assertDontSee('id="component_install_asset_id"', false)
            ->assertDontSeeText('Installed As')
            ->assertDontSeeText('DIMM A')
            ->assertDontSee('name="storage_location_id"', false)
            ->assertDontSeeText('Save Storage Location')
            ->assertSee('name="notes"', false)
            ->assertSeeText('Save Note')
            ->assertSeeText('Upload photos or files for this component here.');
    }

    public function testInstalledComponentDetailPageShowsToTrayModal(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = \App\Models\Asset::factory()->create();
        $component = ComponentInstance::factory()->installed($asset->id)->create();

        $this->actingAs($user)
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee(route('components.remove_to_tray', $component), false)
            ->assertSee('id="componentToTrayModal"', false)
            ->assertSeeText('Status: Installed')
            ->assertSeeText('In Tray')
            ->assertSeeText('Confirm To Tray');
    }

    public function testHistoryShowsLinkedWorkOrderAndTask(): void
    {
        $component = ComponentInstance::factory()->create();
        $workOrder = WorkOrder::factory()->create();
        $task = WorkOrderTask::factory()->for($workOrder)->create([
            'title' => 'Portal Linked Task',
            'work_order_asset_id' => null,
        ]);

        ComponentEvent::query()->create([
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'related_work_order_id' => $workOrder->id,
            'related_work_order_task_id' => $task->id,
            'created_at' => now(),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSeeText('Status History')
            ->assertSee($workOrder->work_order_number)
            ->assertSee('Portal Linked Task')
            ->assertSee(route('work-orders.show', $workOrder), false);
    }

    public function testHistoryShowsLinkedFromAndToAssets(): void
    {
        $component = ComponentInstance::factory()->create();
        $fromAsset = Asset::factory()->create();
        $toAsset = Asset::factory()->create();

        ComponentEvent::query()->create([
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'from_asset_id' => $fromAsset->id,
            'to_asset_id' => $toAsset->id,
            'created_at' => now(),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('<a href="'.e(route('hardware.show', $fromAsset)).'">From asset: '.$fromAsset->present()->name().'</a>', false)
            ->assertSee('<a href="'.e(route('hardware.show', $toAsset)).'">To asset: '.$toAsset->present()->name().'</a>', false);
    }

    public function testComponentNotesCanBeUpdatedFromDetailPage(): void
    {
        $actor = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create([
            'notes' => null,
        ]);
        $token = 'note-test-token';

        $this->actingAs($actor)
            ->withSession(['_token' => $token])
            ->put(route('components.update', $component), [
                '_token' => $token,
                'notes' => 'Needs follow-up inspection.',
            ])
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success', 'Component note updated.');

        $this->assertDatabaseHas('component_instances', [
            'id' => $component->id,
            'notes' => 'Needs follow-up inspection.',
            'updated_by' => $actor->id,
        ]);
    }

    public function testLooseComponentDetailPageShowsStorageLocationEditor(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', $component))
            ->assertOk()
            ->assertSee('name="storage_location_id"', false)
            ->assertSeeText('Save Storage Location');
    }
}
