<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Statuslabel;
use App\Models\User;
use Tests\TestCase;

class BatchMetadataEditTest extends TestCase
{
    public function test_batch_edit_form_loads()
    {
        $user = User::factory()->viewAssets()->editAssets()->create();
        $assets = Asset::factory()->count(2)->create();
        $ids = $assets->pluck('id')->toArray();

        $this->actingAs($user)->post('/hardware/bulkedit', [
            'ids' => $ids,
            'order' => 'asc',
            'bulk_actions' => 'batch-edit',
            'sort' => 'id',
        ])->assertStatus(200);
    }

    public function test_batch_edit_updates_fields()
    {
        $status1 = Statuslabel::factory()->create();
        $status2 = Statuslabel::factory()->create();
        $category1 = Category::factory()->assetDesktopCategory()->create();
        $category2 = Category::factory()->assetLaptopCategory()->create();
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        $assets = Asset::factory()->count(2)->create([
            'status_id' => $status1->id,
            'category_id' => $category1->id,
            'rtd_location_id' => $location1->id,
        ]);

        $ids = $assets->pluck('id')->toArray();

        $this->actingAs(User::factory()->editAssets()->create())
            ->post(route('hardware/bulksave'), [
                'ids' => $ids,
                'status_id' => $status2->id,
                'category_id' => $category2->id,
                'rtd_location_id' => $location2->id,
                'update_real_loc' => '1',
            ])->assertRedirect();

        $assets->each(function (Asset $asset) use ($status2, $category2, $location2) {
            $asset->refresh();
            $this->assertEquals($status2->id, $asset->status_id);
            $this->assertEquals($category2->id, $asset->category_id);
            $this->assertEquals($location2->id, $asset->rtd_location_id);
            $this->assertEquals($location2->id, $asset->location_id);
        });
    }

    public function test_batch_edit_rolls_back_on_failure()
    {
        $deployable = Statuslabel::factory()->create(['deployable' => 1]);
        $nonDeployable = Statuslabel::factory()->archived()->create();
        $assets = Asset::factory()->count(2)->create(['status_id' => $deployable->id]);
        $user = User::factory()->create();
        $assets[0]->assigned_to = $user->id;
        $assets[0]->save();

        $ids = $assets->pluck('id')->toArray();

        $this->actingAs(User::factory()->editAssets()->create())
            ->post(route('hardware/bulksave'), [
                'ids' => $ids,
                'status_id' => $nonDeployable->id,
            ])
            ->assertSessionHas('bulk_asset_errors');

        $assets->each(function (Asset $asset) use ($deployable) {
            $asset->refresh();
            $this->assertEquals($deployable->id, $asset->status_id);
        });
    }
}
