<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreAssetWithMinimalDataTest extends TestCase
{
    #[Test]
    public function asset_can_be_created_with_minimal_data()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('hardware.store'), [])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(1, Asset::count());
        $asset = Asset::first();
        $this->assertEquals('ASSET-0001', $asset->asset_tag);
        $this->assertNull($asset->model_id);
        $this->assertNull($asset->status_id);
        $this->assertFalse($asset->is_sellable);
    }
}
