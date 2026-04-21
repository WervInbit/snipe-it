<?php

namespace Tests\Feature\Components\Ui;

use App\Models\ComponentEvent;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Tests\TestCase;

class ShowComponentTest extends TestCase
{
    public function testPageRenders(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'CMP-TARGET-01']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.show', ComponentInstance::factory()->create()))
            ->assertOk()
            ->assertSee('value="'.$asset->id.'"', false)
            ->assertSee('CMP-TARGET-01');
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
            ->assertSee($workOrder->work_order_number)
            ->assertSee('Portal Linked Task')
            ->assertSee(route('work-orders.show', $workOrder), false);
    }
}
