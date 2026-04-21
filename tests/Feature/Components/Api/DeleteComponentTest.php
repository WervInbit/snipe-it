<?php

namespace Tests\Feature\Components\Api;

use App\Models\Company;
use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\User;
use Tests\Concerns\TestsFullMultipleCompaniesSupport;
use Tests\Concerns\TestsPermissionsRequirement;
use Tests\TestCase;

class DeleteComponentTest extends TestCase implements TestsFullMultipleCompaniesSupport, TestsPermissionsRequirement
{
    public function testRequiresPermission(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAsForApi(User::factory()->create())
            ->deleteJson(route('api.components.destroy', $component))
            ->assertForbidden();

        $this->assertNotSoftDeleted($component);
    }

    public function testAdheresToFullMultipleCompaniesSupportScoping(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $componentA = ComponentInstance::factory()->create(['company_id' => $companyA->id]);
        $componentB = ComponentInstance::factory()->create(['company_id' => $companyB->id]);
        $componentC = ComponentInstance::factory()->create(['company_id' => $companyB->id]);

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->deleteComponents()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->deleteComponents()->make());

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($userInCompanyA)
            ->deleteJson(route('api.components.destroy', $componentB))
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($userInCompanyB)
            ->deleteJson(route('api.components.destroy', $componentA))
            ->assertStatusMessageIs('error');

        $this->actingAsForApi($superUser)
            ->deleteJson(route('api.components.destroy', $componentC))
            ->assertStatusMessageIs('success');

        $this->assertNotSoftDeleted($componentA);
        $this->assertNotSoftDeleted($componentB);
        $this->assertSoftDeleted($componentC);
    }

    public function testCanDeleteComponents(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAsForApi(User::factory()->deleteComponents()->create())
            ->deleteJson(route('api.components.destroy', $component))
            ->assertStatusMessageIs('success');

        $this->assertSoftDeleted($component);
    }

    public function testCannotDeleteComponentIfInstalled(): void
    {
        $component = ComponentInstance::factory()->installed(Asset::factory()->create()->id)->create();

        $this->actingAsForApi(User::factory()->deleteComponents()->create())
            ->deleteJson(route('api.components.destroy', $component))
            ->assertStatusMessageIs('error');
    }
}
