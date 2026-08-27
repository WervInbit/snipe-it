<?php

namespace Tests\Feature\Locations;

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class LocationCompanyBoundaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->settings->set([
            'full_multiple_companies_support' => 1,
            'scope_locations_fmcs' => 0,
        ]);
    }

    public function testApiCreateRejectsParentFromDifferentCompanyWhenLocationScopingIsOff(): void
    {
        [$parent, $childCompany] = $this->differentCompanyParent();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.locations.store'), [
                'name' => 'Cross-company child',
                'parent_id' => $parent->id,
                'company_id' => $childCompany->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->assertFalse(Location::where('name', 'Cross-company child')->exists());
    }

    public function testUiCreateRejectsParentFromDifferentCompanyWhenLocationScopingIsOff(): void
    {
        [$parent, $childCompany] = $this->differentCompanyParent();

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('locations.create'))
            ->post(route('locations.store'), [
                'name' => 'Cross-company child',
                'parent_id' => $parent->id,
                'company_id' => $childCompany->id,
            ])
            ->assertRedirect(route('locations.create'))
            ->assertSessionHas('error');

        $this->assertFalse(Location::where('name', 'Cross-company child')->exists());
    }

    public function testApiParentOnlyUpdateCannotCrossCompanyBoundary(): void
    {
        [$parent, $childCompany] = $this->differentCompanyParent();
        $child = Location::factory()->create([
            'name' => 'Existing child',
            'company_id' => $childCompany->id,
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.locations.update', $child), [
                'parent_id' => $parent->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error');

        $this->assertNull($child->refresh()->parent_id);
    }

    public function testUiUpdateCannotCrossCompanyBoundary(): void
    {
        [$parent, $childCompany] = $this->differentCompanyParent();
        $child = Location::factory()->create([
            'name' => 'Existing child',
            'company_id' => $childCompany->id,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('locations.edit', $child))
            ->put(route('locations.update', $child), [
                'name' => $child->name,
                'parent_id' => $parent->id,
                'company_id' => $childCompany->id,
            ])
            ->assertRedirect(route('locations.edit', $child))
            ->assertSessionHas('error');

        $this->assertNull($child->refresh()->parent_id);
    }

    public function testMatchingParentAndChildCompaniesRemainAllowed(): void
    {
        $company = Company::factory()->create();
        $parent = Location::factory()->create(['company_id' => $company->id]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.locations.store'), [
                'name' => 'Valid company child',
                'parent_id' => $parent->id,
                'company_id' => $company->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $this->assertTrue(Location::where('name', 'Valid company child')->exists());
    }

    private function differentCompanyParent(): array
    {
        $parentCompany = Company::factory()->create();
        $childCompany = Company::factory()->create();
        $parent = Location::factory()->create(['company_id' => $parentCompany->id]);

        return [$parent, $childCompany];
    }
}
