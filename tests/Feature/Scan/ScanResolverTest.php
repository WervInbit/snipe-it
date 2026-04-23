<?php

namespace Tests\Feature\Scan;

use App\Models\Asset;
use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanResolverTest extends TestCase
{
    use RefreshDatabase;

    public function testAssetTagScansContinueToUseAssetLookup(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create([
            'asset_tag' => 'SCAN-ASSET-001',
        ]);

        $this->actingAs($user)
            ->get(route('scan.resolve', ['code' => $asset->asset_tag]))
            ->assertRedirect(route('findbytag/hardware', ['any' => $asset->asset_tag]));
    }

    public function testComponentQrCodesResolveToComponentDetails(): void
    {
        $user = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create();

        $this->actingAs($user)
            ->get(route('scan.resolve', ['code' => 'CMP:'.$component->qr_uid]))
            ->assertRedirect(route('components.show', $component));
    }

    public function testUnknownComponentQrCodesRedirectBackToScanSafely(): void
    {
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('scan.resolve', ['code' => 'CMP:missing-component']))
            ->assertRedirect(route('scan'))
            ->assertSessionHas('error');
    }
}
