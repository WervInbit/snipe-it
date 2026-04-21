<?php

namespace Tests\Feature\WorkOrders\Portal;

use App\Models\Company;
use App\Models\ComponentEvent;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderTask;
use Tests\TestCase;

class PortalWorkOrdersTest extends TestCase
{
    public function testPortalUserSeesOnlyVisibleWorkOrdersAndCustomerSafeFields(): void
    {
        $company = Company::factory()->create();
        $portalUser = User::factory()->for($company)->viewPortal()->create();
        $visibleWorkOrder = WorkOrder::factory()->for($company)->create([
            'title' => 'Visible Work Order',
            'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_BASIC,
        ]);
        $hiddenWorkOrder = WorkOrder::factory()->create(['title' => 'Hidden Work Order']);

        WorkOrderTask::factory()->for($visibleWorkOrder)->create([
            'title' => 'Customer Visible Task',
            'work_order_asset_id' => null,
            'customer_visible' => true,
            'notes_customer' => 'Ready for update.',
            'notes_internal' => 'Internal only note.',
        ]);
        WorkOrderTask::factory()->for($visibleWorkOrder)->create([
            'title' => 'Hidden Task',
            'work_order_asset_id' => null,
            'customer_visible' => false,
            'notes_customer' => 'Should not show.',
        ]);

        ComponentEvent::query()->create([
            'component_instance_id' => ComponentInstance::factory()->create()->id,
            'event_type' => 'installed',
            'related_work_order_id' => $visibleWorkOrder->id,
            'created_at' => now(),
        ]);

        $this->actingAs($portalUser)
            ->get(route('account.work-orders.index'))
            ->assertOk()
            ->assertSee('Visible Work Order')
            ->assertDontSee('Hidden Work Order');

        $this->actingAs($portalUser)
            ->get(route('account.work-orders.show', $visibleWorkOrder))
            ->assertOk()
            ->assertSee('Customer Visible Task')
            ->assertSee('Ready for update.')
            ->assertDontSee('Hidden Task')
            ->assertDontSee('Internal only note.')
            ->assertDontSee('Component Activity');
    }

    public function testFullVisibilityShowsComponentActivityForExplicitPortalAccess(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $portalUser = User::factory()->for($companyB)->viewPortal()->create();
        $workOrder = WorkOrder::factory()->for($companyA)->create([
            'title' => 'Explicit Portal Access',
            'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_FULL,
        ]);
        $task = WorkOrderTask::factory()->for($workOrder)->create([
            'title' => 'Customer Task',
            'work_order_asset_id' => null,
            'customer_visible' => true,
        ]);

        $workOrder->visibleUsers()->attach($portalUser->id, ['granted_by' => null]);

        ComponentEvent::query()->create([
            'component_instance_id' => ComponentInstance::factory()->create()->id,
            'event_type' => 'installed',
            'related_work_order_id' => $workOrder->id,
            'related_work_order_task_id' => $task->id,
            'created_at' => now(),
        ]);

        $this->actingAs($portalUser)
            ->get(route('account.work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Component Activity')
            ->assertSee('Customer Task');
    }

    public function testCompanyMatchedPortalAccessWorksWithoutExplicitVisibleUsers(): void
    {
        $company = Company::factory()->create();
        $portalUser = User::factory()->for($company)->viewPortal()->create();
        $workOrder = WorkOrder::factory()->for($company)->create([
            'title' => 'Company Visible Work Order',
            'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_FULL,
        ]);

        $this->actingAs($portalUser)
            ->get(route('account.work-orders.index'))
            ->assertOk()
            ->assertSee('Company Visible Work Order');

        $this->actingAs($portalUser)
            ->get(route('account.work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee($workOrder->work_order_number)
            ->assertSee('Company Visible Work Order');
    }
}
