<?php

namespace Tests\Feature\Scan;

use App\Models\Asset;
use App\Models\Company;
use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanResolverTest extends TestCase
{
    use RefreshDatabase;

    public function testSharedOperationalLabelsAreTranslatedInSupportedLocales(): void
    {
        foreach ([
            'en-US' => [
                'general.actions' => 'Actions',
                'general.scan' => 'Scan',
                'general.source' => 'Source',
            ],
            'nl-NL' => [
                'general.actions' => 'Acties',
                'general.scan' => 'Scannen',
                'general.source' => 'Bron',
            ],
        ] as $locale => $labels) {
            $this->app->setLocale($locale);

            foreach ($labels as $key => $label) {
                $this->assertSame($label, trans($key));
            }
        }
    }

    public function testScanPageExposesCameraFeedbackRetryAndManualLookup(): void
    {
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('scan'))
            ->assertOk()
            ->assertSee('id="scan-status"', false)
            ->assertSee('data-state="starting"', false)
            ->assertSee('id="scan-refocus"', false)
            ->assertSee('id="scan-manual-code"', false)
            ->assertSee('name="code"', false)
            ->assertSee(route('scan.lookup'), false)
            ->assertSee(trans('general.scan_status_starting'))
            ->assertSee(trans('general.scan_status_no_code'))
            ->assertSee(trans('general.scan_manual_help'));
    }

    public function testScanPageFeedbackAndManualLookupAreLocalizedInDutch(): void
    {
        $this->app->setLocale('nl-NL');
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('scan'))
            ->assertOk()
            ->assertSee('Camera starten...')
            ->assertSee('Nog geen code gevonden.')
            ->assertSee('Voer assettag, componenttag of serienummer in');
    }

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

    public function testManualLookupPreservesAssetComponentAndSerialNavigation(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create([
            'asset_tag' => 'MANUAL-ASSET-001',
            'serial' => 'MANUAL-SERIAL-001',
        ]);
        $component = ComponentInstance::factory()->create([
            'component_tag' => 'INBIT-C-MANUAL',
        ]);

        $this->actingAs($user)
            ->get(route('scan.lookup', ['code' => $asset->asset_tag]))
            ->assertRedirect(route('findbytag/hardware', ['any' => $asset->asset_tag]));

        $this->actingAs($user)
            ->get(route('scan.lookup', ['code' => $asset->serial]))
            ->assertRedirect(route('hardware.show', $asset));

        $this->actingAs($user)
            ->get(route('scan.lookup', ['code' => $component->component_tag]))
            ->assertRedirect(route('components.show', $component));

        $this->actingAs($user)
            ->get(route('scan.lookup', ['code' => 'CMP:' . $component->qr_uid]))
            ->assertRedirect(route('components.show', $component));
    }

    public function testManualLookupRejectsBlankOrOversizedValues(): void
    {
        $user = User::factory()->superuser()->create();

        foreach (['   ', str_repeat('A', 256)] as $code) {
            $this->actingAs($user)
                ->get(route('scan.lookup', ['code' => $code]))
                ->assertRedirect(route('scan'))
                ->assertSessionHas('error', trans('general.scan_manual_required'));
        }
    }

    public function testManualLookupDoesNotResolveAssetsOutsideCompanyScope(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $user = User::factory()->for($companyA)->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'scanning' => '1',
            ]),
        ]);
        $outsideAsset = Asset::factory()->for($companyB)->create([
            'asset_tag' => 'OUTSIDE-ASSET-001',
            'serial' => 'OUTSIDE-SERIAL-001',
        ]);
        $this->settings->enableMultipleFullCompanySupport();

        foreach ([$outsideAsset->asset_tag, $outsideAsset->serial] as $code) {
            $this->actingAs($user)
                ->get(route('scan.lookup', ['code' => $code]))
                ->assertRedirect(route('hardware.index'))
                ->assertSessionHas('search', $code);
        }
    }

    public function testComponentQrCodesResolveToComponentDetails(): void
    {
        $user = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create();

        $this->actingAs($user)
            ->get(route('scan.resolve', ['code' => 'CMP:'.$component->qr_uid]))
            ->assertRedirect(route('components.show', $component));
    }

    public function testVisibleComponentTagsResolveToComponentDetails(): void
    {
        $user = User::factory()->superuser()->create();
        $component = ComponentInstance::factory()->create([
            'component_tag' => 'INBIT-C-ZZ1234',
        ]);

        $this->actingAs($user)
            ->get(route('scan.resolve', ['code' => $component->component_tag]))
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

    public function test_asset_destination_scan_appends_the_asset_only_to_a_safe_local_return_url(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'DESTINATION-001']);
        $returnTo = '/components/tray?filter=mine#pending';

        $this->actingAs($user)
            ->get(route('scan.resolve', [
                'code' => $asset->asset_tag,
                'mode' => 'asset_destination',
                'return_to' => $returnTo,
            ]))
            ->assertRedirect(
                '/components/tray?filter=mine&destination_asset_id='.$asset->id.'#pending'
            );
    }

    public function test_asset_destination_scan_rejects_external_and_prefix_lookalike_return_urls(): void
    {
        $user = User::factory()->superuser()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'DESTINATION-002']);

        foreach ([
            'https://example.invalid/steal',
            '//example.invalid/steal',
            url('/').'.example.invalid/steal',
            '/%2f%2fexample.invalid/steal',
            '/\\example.invalid/steal',
        ] as $returnTo) {
            $this->actingAs($user)
                ->get(route('scan.resolve', [
                    'code' => $asset->asset_tag,
                    'mode' => 'asset_destination',
                    'return_to' => $returnTo,
                ]))
                ->assertRedirect(route('hardware.show', $asset));
        }
    }

    public function test_unknown_destination_scan_never_redirects_to_an_external_return_url(): void
    {
        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->get(route('scan.resolve', [
                'code' => 'MISSING-DESTINATION',
                'mode' => 'asset_destination',
                'return_to' => 'https://example.invalid/steal',
            ]))
            ->assertRedirect(route('scan'))
            ->assertSessionHas('error');
    }
}
