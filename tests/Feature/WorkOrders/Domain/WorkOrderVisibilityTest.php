<?php

namespace Tests\Feature\WorkOrders\Domain;

use App\Models\Company;
use App\Models\User;
use App\Models\WorkOrder;
use Tests\TestCase;

class WorkOrderVisibilityTest extends TestCase
{
    public function testExplicitPortalAccessGrantsVisibilityWithoutCompanyScope(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $portalUser = User::factory()->for($companyB)->create([
            'permissions' => json_encode(['portal.view' => '1']),
        ]);
        $workOrder = WorkOrder::factory()->for($companyA)->create();

        $workOrder->visibleUsers()->attach($portalUser->id, ['granted_by' => null]);

        $this->assertTrue($workOrder->fresh()->isVisibleTo($portalUser));
    }
}
