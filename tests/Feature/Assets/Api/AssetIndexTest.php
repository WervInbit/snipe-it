<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AssetIndexTest extends TestCase
{
    public function testAssetApiIndexReturnsExpectedAssets()
    {
        Asset::factory()->count(3)->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(
                route('api.assets.index', [
                    'sort' => 'name',
                    'order' => 'asc',
                    'offset' => '0',
                    'limit' => '20',
                ]))
            ->assertOk()
            ->assertJsonStructure([
                'total',
                'rows',
            ])
            ->assertJson(fn(AssertableJson $json) => $json->has('rows', 3)->etc());
    }

    public function testAssetApiIndexClampsOversizedOffsets()
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(
                route('api.assets.index', [
                    'sort' => 'name',
                    'order' => 'asc',
                    'offset' => 500,
                    'limit' => 20,
                ]))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('total', 1)
                ->has('rows', 1)
                ->where('rows.0.id', $asset->id)
                ->etc());
    }

    public function testAssetApiIndexIncludesModelNumber()
    {
        $asset = Asset::factory()->create();
        $expectedModelNumber = $asset->displayModelNumber();
        $expectedModelNumberId = $asset->model_number_id;

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index'))
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) =>
                $json->has('rows.0', fn(AssertableJson $row) =>
                    $row->where('model_number', $expectedModelNumber)
                        ->where('model_number_id', $expectedModelNumberId)
                        ->etc()
                )->etc()
            );
    }

    public function testAssetApiIndexIncludesWorkflowStatus(): void
    {
        Asset::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index'))
            ->assertOk()
            ->assertJsonPath('rows.0.test_workflow_status', 'missing');
    }

    public function testAssetApiIndexAdheresToCompanyScoping()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();

        $assetA = Asset::factory()->for($companyA)->create();
        $assetB = Asset::factory()->for($companyB)->create();

        $superUser = $companyA->users()->save(User::factory()->superuser()->make());
        $userInCompanyA = $companyA->users()->save(User::factory()->viewAssets()->make());
        $userInCompanyB = $companyB->users()->save(User::factory()->viewAssets()->make());

        $this->settings->disableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.assets.index'))
            ->assertResponseContainsInRows($assetA, 'asset_tag')
            ->assertResponseContainsInRows($assetB, 'asset_tag');

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.assets.index'))
            ->assertResponseContainsInRows($assetA, 'asset_tag')
            ->assertResponseContainsInRows($assetB, 'asset_tag');

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.assets.index'))
            ->assertResponseContainsInRows($assetA, 'asset_tag')
            ->assertResponseContainsInRows($assetB, 'asset_tag');

        $this->settings->enableMultipleFullCompanySupport();

        $this->actingAsForApi($superUser)
            ->getJson(route('api.assets.index'))
            ->assertResponseContainsInRows($assetA, 'asset_tag')
            ->assertResponseContainsInRows($assetB, 'asset_tag');

        $this->actingAsForApi($userInCompanyA)
            ->getJson(route('api.assets.index'))
            ->assertResponseContainsInRows($assetA, 'asset_tag')
            ->assertResponseDoesNotContainInRows($assetB, 'asset_tag');

        $this->actingAsForApi($userInCompanyB)
            ->getJson(route('api.assets.index'))
            ->assertResponseDoesNotContainInRows($assetA, 'asset_tag')
            ->assertResponseContainsInRows($assetB, 'asset_tag');
    }

    public function testAssetApiIndexIgnoresInvalidStatusIdFilter()
    {
        $visibleAsset = Asset::factory()->create();
        $archivedAsset = Asset::factory()->create([
            'status_id' => Statuslabel::factory()->archived()->create()->id,
        ]);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.assets.index', ['status_id' => 999999]))
            ->assertOk()
            ->assertResponseContainsInRows($visibleAsset, 'asset_tag')
            ->assertResponseDoesNotContainInRows($archivedAsset, 'asset_tag');
    }
}
