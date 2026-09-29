<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreAssetWithMinimalDataTest extends TestCase
{
    #[Test]
    public function assetCanBeCreatedWithMinimalData()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('hardware.store'), [])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('qr_pdf')
            ->assertSessionMissing('qr_png');

        $this->assertEquals(1, Asset::count());
        $asset = Asset::first();
        $response->assertRedirect(route('hardware.created', $asset));
        $this->actingAs($admin)
            ->get(route('hardware.created', $asset))
            ->assertOk()
            ->assertSee('data-testid="asset-creation-result"', false)
            ->assertSee(route('hardware.show', $asset))
            ->assertSee(route('hardware.qr-label.download', [
                'asset' => $asset,
                'template' => config('qr_templates.default'),
            ]), false)
            ->assertSee(trans('general.print_qr'));

        $this->actingAs($admin)
            ->get(route('hardware.qr-label.download', [
                'asset' => $asset,
                'template' => config('qr_templates.default'),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertMatchesRegularExpression('/^INBIT-[A-Z]{2}\d{4}$/', $asset->asset_tag);
        $this->assertNull($asset->model_id);
        $this->assertNotNull($asset->status_id);
        $this->assertTrue((bool) $asset->assetstatus->default_label);
        $this->assertFalse($asset->is_sellable);
    }
}
