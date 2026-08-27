<?php

namespace Tests\Feature\Locations\Ui;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PrintLocationInventoryTest extends TestCase
{
    public function testLocationPrintPayloadsAreFilteredPerRelatedResourcePermission(): void
    {
        $company = Company::factory()->create(['name' => 'PRINT-LOCATION-COMPANY']);
        $manager = User::factory()->create([
            'first_name' => 'PRINT-LOCATION-MANAGER',
            'last_name' => '',
        ]);
        $location = Location::factory()->create([
            'name' => 'Printable location',
            'company_id' => $company->id,
            'manager_id' => $manager->id,
        ]);
        User::factory()->create([
            'first_name' => 'PRINT-LOCATION-USER',
            'last_name' => '',
            'company_id' => $company->id,
            'location_id' => $location->id,
        ]);
        Asset::factory()->assignedToLocation($location)->create([
            'name' => 'PRINT-LOCATION-ASSET',
            'asset_tag' => 'PRINT-LOCATION-ASSET-TAG',
            'location_id' => $location->id,
        ]);

        foreach ([
            'users' => ['users.view' => '1'],
            'assets' => ['assets.view' => '1'],
            'companies' => ['companies.view' => '1'],
        ] as $visibleResource => $additionalPermissions) {
            $actor = User::factory()->create([
                'permissions' => json_encode(array_merge(
                    ['locations.view' => '1'],
                    $additionalPermissions
                )),
            ]);

            foreach (['locations.print_assigned', 'locations.print_all_assigned'] as $route) {
                $response = $this->actingAs($actor)
                    ->get(route($route, $location))
                    ->assertOk();

                $this->assertLocationPrintShowsOnly($response, $visibleResource);
            }
        }
    }

    private function assertLocationPrintShowsOnly(
        TestResponse $response,
        string $visibleResource
    ): void {
        $tokens = [
            'users' => ['PRINT-LOCATION-USER', 'PRINT-LOCATION-MANAGER'],
            'assets' => ['PRINT-LOCATION-ASSET-TAG'],
            'companies' => ['PRINT-LOCATION-COMPANY'],
        ];

        foreach ($tokens as $resource => $resourceTokens) {
            foreach ($resourceTokens as $token) {
                if ($resource === $visibleResource) {
                    $response->assertSee($token);
                } else {
                    $response->assertDontSee($token);
                }
            }
        }

        $response
            ->assertViewHas('users', fn ($users) => ($users->count() > 0) === ($visibleResource === 'users'))
            ->assertViewHas('manager', fn ($manager) => ($manager !== null) === ($visibleResource === 'users'))
            ->assertViewHas('assets', fn ($assets) => ($assets->count() > 0) === ($visibleResource === 'assets'))
            ->assertViewHas('company', fn ($company) => ($company !== null) === ($visibleResource === 'companies'));
    }
}
