<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class ShowAssetTest extends TestCase
{
    public function testPageForAssetWithMissingModelStillRenders()
    {
        $asset = Asset::factory()->create();

        $asset->model_id = null;
        $asset->forceSave();

        $asset->refresh();

        $this->assertNull($asset->fresh()->model_id, 'This test needs model_id to be null to be helpful.');

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk();
    }

    public function testAssignedAssetDetailPageHidesCheckoutUiAndShowsSingleQrLabelDownload(): void
    {
        $settings = Setting::getSettings();
        if ($settings) {
            Setting::unguarded(fn () => $settings->update(['qr_formats' => 'png,pdf,qr']));
            Setting::$_cache = null;
        }

        $asset = Asset::factory()->assignedToUser()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertDontSee(trans('admin/hardware/form.checkedout_to'));
        $response->assertDontSee(trans('admin/hardware/table.checkout_date'));
        $response->assertDontSee(trans('general.deployed'));
        $response->assertDontSee(trans('general.checkin_and_delete'));
        $response->assertDontSee(trans('general.print_pdf'));
        $response->assertDontSee('Download QR code');
        $response->assertDontSee(trans('general.download_png'));
        $response->assertSee(trans('general.download_qr_label'));
    }

    public function testDetailPageRendersQrPanelBelowPrimaryActionButtons(): void
    {
        $settings = Setting::getSettings();
        if ($settings) {
            Setting::unguarded(fn () => $settings->update(['qr_formats' => 'png,pdf,qr']));
            Setting::$_cache = null;
        }

        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('data-testid="asset-qr-action-panel"', false);
        $response->assertSeeInOrder([
            trans('admin/hardware/general.edit'),
            trans('tests.run_test_button'),
            trans('general.add_note'),
            trans('admin/hardware/general.clone'),
            trans('general.delete'),
            trans('general.print_qr'),
        ], false);
    }

    public function testDetailPageUsesClipboardTestsIconAndTranslatedStatusHistory(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertDontSee('fa-vial');
        $response->assertSee('fa-solid fa-clipboard-check');
        $response->assertDontSee('general.status_history');
        $response->assertSee(trans('general.status_history'));
    }

    public function testDetailPageUploadTabIsNotFloatedRight(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee(trans('button.upload'));
        $response->assertDontSee('<li class="pull-right">', false);
    }

    public function testDetailPageShowsRunTestButtonLinkingToTestsTab(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee(trans('tests.run_test_button'));
        $response->assertSee('href="#tests"', false);
        $response->assertSee('aria-controls="tests"', false);
    }

    public function testDetailPageRendersResponsiveTestsStartRunActions(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('data-testid="hardware-tests-tab-actions"', false);
        $response->assertSee('data-testid="hardware-tests-tab-fab"', false);
        $response->assertSee(route('test-runs.store', $asset), false);
        $response->assertDontSee('class="mb-3 text-right"', false);
    }

    public function testDetailPageTestsTabUsesSingleColumnRunList(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('<div class="col-md-12">', false);
        $response->assertDontSee('<div class="col-md-6 col-sm-12">', false);
    }

    public function testDetailPageRendersFoldableLatestTestsAttentionBlock(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('data-testid="asset-tests-attention"', false);
        $response->assertSee('asset-tests-attention__chevron', false);
        $response->assertSee(trans('tests.click_to_unfold'));
        $response->assertSee('aria-expanded="false"', false);
    }

    public function testTestsIndexUsesStructuredResultRows(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('test-runs.index', $asset));

        $response->assertOk();
        $response->assertSee('test-result-label', false);
        $response->assertSee('test-result-status', false);
        $response->assertSee('test-result-note', false);
    }

    public function testDetailPageRendersSeparateStatusAndQualityRows(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('id="asset-status-row"', false);
        $response->assertSee('id="asset-quality-row"', false);
    }
}
