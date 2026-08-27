<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class CloneAssetTest extends TestCase
{
    public function testPermissionRequiredToCreateAssetModel()
    {
        $asset = Asset::factory()->create();
        $this->actingAs(User::factory()->create())
            ->get(route('clone/hardware', $asset))
            ->assertForbidden();
    }

    public function testPageCanBeAccessed(): void
    {
        $asset = Asset::factory()->create();
        $response = $this->actingAs(User::factory()->createAssets()->create())
            ->get(route('clone/hardware', $asset));
        $response->assertStatus(200);
    }

    public function testCloneFormKeepsModelButOmitsRetiredCreateNameField()
    {
        $asset_to_clone = Asset::factory()->create(['name'=>'Asset to clone']);
        $this->actingAs(User::factory()->createAssets()->create())
            ->get(route('clone/hardware', $asset_to_clone))
            ->assertOk()
            ->assertSee(
                '<input type="hidden" name="model_id" id="model_id_hidden" value="'.$asset_to_clone->model_id.'">',
                false
            )
            ->assertDontSee('name="name"', false)
            ->assertDontSee('Asset to clone', false);
    }
}
