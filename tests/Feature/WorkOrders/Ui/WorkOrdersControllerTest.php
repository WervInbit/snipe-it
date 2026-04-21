<?php

namespace Tests\Feature\WorkOrders\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\Company;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Tests\TestCase;

class WorkOrdersControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testIndexRequiresWorkOrderViewPermission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('work-orders.index'))
            ->assertForbidden();
    }

    public function testCreateRouteRequiresCreatePermission(): void
    {
        $this->actingAs(User::factory()->viewWorkOrders()->create())
            ->get(route('work-orders.create'))
            ->assertForbidden();
    }

    public function testShowRouteRequiresViewPermission(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('work-orders.show', $workOrder))
            ->assertForbidden();
    }

    public function testEditAndUpdateRequireUpdatePermission(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $viewer = User::factory()->viewWorkOrders()->create();

        $this->actingAs($viewer)
            ->get(route('work-orders.edit', $workOrder))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->put(route('work-orders.update', $workOrder), [
                'title' => 'Blocked Update',
                'status' => WorkOrder::STATUS_DRAFT,
                'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_FULL,
            ])
            ->assertForbidden();
    }

    public function testAuthorizedUserCanCreateViewAndEditWorkOrdersWithoutLinkedAssets(): void
    {
        $user = User::factory()
            ->viewWorkOrders()
            ->createWorkOrders()
            ->updateWorkOrders()
            ->manageWorkOrderVisibility()
            ->create();
        $company = Company::factory()->create();
        $contact = User::factory()->create();
        $visibleUser = User::factory()->create();

        $this->actingAs($user)
            ->post(route('work-orders.store'), [
                'title' => 'Refurbish Intake',
                'description' => 'Initial customer intake for diagnostics.',
                'company_id' => $company->id,
                'primary_contact_user_id' => $contact->id,
                'status' => WorkOrder::STATUS_INTAKE,
                'priority' => WorkOrder::PRIORITY_HIGH,
                'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_CUSTOM,
                'portal_show_components' => '1',
                'portal_show_notes_customer' => '1',
                'visible_user_ids' => [$visibleUser->id],
                'intake_date' => '2026-04-17',
                'due_date' => '2026-04-20',
            ])
            ->assertRedirect();

        $workOrder = WorkOrder::query()->where('title', 'Refurbish Intake')->firstOrFail();

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'title' => 'Refurbish Intake',
            'company_id' => $company->id,
            'primary_contact_user_id' => $contact->id,
            'status' => WorkOrder::STATUS_INTAKE,
            'priority' => WorkOrder::PRIORITY_HIGH,
            'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_CUSTOM,
        ]);
        $this->assertDatabaseHas('work_order_user_access', [
            'work_order_id' => $workOrder->id,
            'user_id' => $visibleUser->id,
            'granted_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee($workOrder->work_order_number)
            ->assertSee('Refurbish Intake');

        $this->actingAs($user)
            ->put(route('work-orders.update', $workOrder), [
                'title' => 'Refurbish Intake Updated',
                'description' => 'Updated summary.',
                'company_id' => $company->id,
                'primary_contact_user_id' => $contact->id,
                'status' => WorkOrder::STATUS_IN_PROGRESS,
                'priority' => WorkOrder::PRIORITY_URGENT,
                'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_BASIC,
                'intake_date' => '2026-04-17',
                'due_date' => '2026-04-21',
                'visible_user_ids' => [$visibleUser->id],
            ])
            ->assertRedirect(route('work-orders.show', $workOrder));

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'title' => 'Refurbish Intake Updated',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'priority' => WorkOrder::PRIORITY_URGENT,
            'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_BASIC,
        ]);
        $this->assertCount(0, $workOrder->fresh()->assets);
    }

    public function testWorkOrderShowLinksComponentActivityBackToAssets(): void
    {
        $user = User::factory()->viewWorkOrders()->create();
        $workOrder = WorkOrder::factory()->create();
        $fromAsset = Asset::factory()->create();
        $toAsset = Asset::factory()->create();
        $task = WorkOrderTask::factory()->for($workOrder)->create([
            'work_order_asset_id' => null,
        ]);
        $component = ComponentInstance::factory()->create();

        ComponentEvent::query()->create([
            'component_instance_id' => $component->id,
            'event_type' => 'installed',
            'from_asset_id' => $fromAsset->id,
            'to_asset_id' => $toAsset->id,
            'related_work_order_id' => $workOrder->id,
            'related_work_order_task_id' => $task->id,
            'note' => 'Moved during repair',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee(route('hardware.show', $fromAsset), false)
            ->assertSee(route('hardware.show', $toAsset), false)
            ->assertSee('Moved during repair');
    }
}
