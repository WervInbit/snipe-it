<?php

namespace Tests\Feature\WorkOrders\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAsset;
use App\Models\WorkOrderTask;
use Tests\TestCase;

class WorkOrderAssetsAndTasksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testLinkingRealAssetCapturesSnapshots(): void
    {
        $user = User::factory()->viewWorkOrders()->updateWorkOrders()->create();
        $workOrder = WorkOrder::factory()->create();
        $asset = Asset::factory()->create([
            'asset_tag' => 'INBIT-A1001',
            'serial' => 'SER-12345',
        ]);

        $this->actingAs($user)
            ->post(route('work-orders.assets.store', $workOrder), [
                'asset_id' => $asset->id,
                'customer_label' => 'Customer Laptop',
                'status' => 'pending',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('work_order_assets', [
            'work_order_id' => $workOrder->id,
            'asset_id' => $asset->id,
            'asset_tag_snapshot' => 'INBIT-A1001',
            'serial_snapshot' => 'SER-12345',
        ]);
    }

    public function testTasksCanBeCreatedAndUpdated(): void
    {
        $user = User::factory()->viewWorkOrders()->updateWorkOrders()->create();
        $workOrder = WorkOrder::factory()->create();
        $device = WorkOrderAsset::factory()->for($workOrder)->create();
        $assignee = User::factory()->create();

        $this->actingAs($user)
            ->post(route('work-orders.tasks.store', $workOrder), [
                'work_order_asset_id' => $device->id,
                'task_type' => 'repair',
                'title' => 'Replace SSD',
                'description' => 'Install tested replacement drive.',
                'status' => WorkOrderTask::STATUS_PENDING,
                'assigned_to' => $assignee->id,
                'customer_visible' => '1',
                'customer_status_label' => 'Queued',
                'notes_internal' => 'Use 512GB stock item.',
                'notes_customer' => 'Storage upgrade planned.',
            ])
            ->assertRedirect();

        $task = WorkOrderTask::query()->where('title', 'Replace SSD')->firstOrFail();

        $this->actingAs($user)
            ->put(route('work-orders.tasks.update', [$workOrder, $task]), [
                'work_order_asset_id' => $device->id,
                'task_type' => 'repair',
                'title' => 'Replace SSD',
                'description' => 'Install tested replacement drive.',
                'status' => WorkOrderTask::STATUS_IN_PROGRESS,
                'assigned_to' => $assignee->id,
                'customer_visible' => '1',
                'customer_status_label' => 'In Progress',
                'notes_internal' => 'Image from clean template.',
                'notes_customer' => 'Upgrade underway.',
                'started_at' => '2026-04-17 10:00:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('work_order_tasks', [
            'id' => $task->id,
            'work_order_id' => $workOrder->id,
            'work_order_asset_id' => $device->id,
            'status' => WorkOrderTask::STATUS_IN_PROGRESS,
            'assigned_to' => $assignee->id,
            'customer_visible' => 1,
            'customer_status_label' => 'In Progress',
            'notes_customer' => 'Upgrade underway.',
        ]);
    }
}
