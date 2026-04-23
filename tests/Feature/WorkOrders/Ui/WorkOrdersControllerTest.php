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

    public function testInternalWorkOrderIndexAndShowAreCompanyScopedUnderFmcs(): void
    {
        $this->settings->enableMultipleFullCompanySupport();

        $companyA = Company::factory()->create(['name' => 'Scoped Company']);
        $companyB = Company::factory()->create(['name' => 'Other Company']);
        $allowedContact = User::factory()->for($companyA)->create([
            'first_name' => 'Allowed',
            'last_name' => 'Contact',
        ]);
        $blockedContact = User::factory()->for($companyB)->create([
            'first_name' => 'Blocked',
            'last_name' => 'Contact',
        ]);
        $allowedAsset = Asset::factory()->for($companyA)->create(['asset_tag' => 'SCOPED-ASSET']);
        $blockedAsset = Asset::factory()->for($companyB)->create(['asset_tag' => 'BLOCKED-ASSET']);
        $user = User::factory()
            ->for($companyA)
            ->viewWorkOrders()
            ->createWorkOrders()
            ->updateWorkOrders()
            ->manageWorkOrderVisibility()
            ->create();
        $allowedWorkOrder = WorkOrder::factory()->for($companyA)->create([
            'title' => 'Scoped Work Order',
        ]);
        $blockedWorkOrder = WorkOrder::factory()->for($companyB)->create([
            'title' => 'Blocked Work Order',
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertSee('Scoped Work Order')
            ->assertDontSee('Blocked Work Order');

        $this->actingAs($user)
            ->get(route('work-orders.show', $blockedWorkOrder))
            ->assertRedirect(route('work-orders.index'));

        $this->actingAs($user)
            ->get(route('work-orders.show', $allowedWorkOrder))
            ->assertOk()
            ->assertSee('Scoped Company')
            ->assertDontSee('Other Company')
            ->assertSee('SCOPED-ASSET')
            ->assertDontSee('BLOCKED-ASSET')
            ->assertSee($allowedContact->present()->fullName())
            ->assertDontSee($blockedContact->present()->fullName());
    }

    public function testWorkOrderStoreRejectsOutOfScopeVisibleUsersUnderFmcs(): void
    {
        $this->settings->enableMultipleFullCompanySupport();

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $allowedContact = User::factory()->for($companyA)->create();
        $blockedVisibleUser = User::factory()->for($companyB)->create();
        $user = User::factory()
            ->for($companyA)
            ->viewWorkOrders()
            ->createWorkOrders()
            ->manageWorkOrderVisibility()
            ->create();

        $this->actingAs($user)
            ->from(route('work-orders.create'))
            ->post(route('work-orders.store'), [
                'title' => 'FMCS Scoped Work Order',
                'company_id' => $companyB->id,
                'primary_contact_user_id' => $allowedContact->id,
                'status' => WorkOrder::STATUS_DRAFT,
                'visibility_profile' => WorkOrder::VISIBILITY_PROFILE_FULL,
                'visible_user_ids' => [$blockedVisibleUser->id],
            ])
            ->assertRedirect(route('work-orders.create'))
            ->assertSessionHasErrors('visible_user_ids');

        $this->assertDatabaseMissing('work_orders', [
            'title' => 'FMCS Scoped Work Order',
        ]);
    }
}
