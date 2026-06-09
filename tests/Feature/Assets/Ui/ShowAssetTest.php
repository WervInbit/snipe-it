<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\ModelNumberAttribute;
use App\Models\Setting;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
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
        $response->assertDontSee('name="checkout_at"', false);
        $response->assertDontSee('id="checkout_at"', false);
        $response->assertDontSee(trans('general.checkin_and_delete'));
        $response->assertDontSee(trans('general.print_pdf'));
        $response->assertDontSee('Download QR code');
        $response->assertDontSee(trans('general.download_png'));
        $response->assertSee(trans('general.download_qr_label'));
    }

    public function testDetailPageQrPanelUsesConstrainedPrinterControls(): void
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
        $response->assertSee('asset-printer-picker', false);
        $response->assertSee('box-sizing: border-box;', false);
        $response->assertSee('width: 100%;', false);
    }

    public function testDetailPageKeepsCalculatedSpecMetadataInBlockLayout(): void
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('.spec-detail-meta {', false);
        $response->assertSee('display: block !important;', false);
        $response->assertSee('word-break: normal;', false);
        $response->assertSee('.spec-source-indicator {', false);
        $response->assertSee('display: inline-flex !important;', false);
    }

    public function testDetailPageDisplaysUnitsForNumericSpecificationValues(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $asset = Asset::factory()->for($model, 'model')->create([
            'model_number_id' => $modelNumber->id,
        ]);
        $storage = AttributeDefinition::create([
            'key' => 'storage_capacity_gb',
            'label' => 'Storage Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'unit' => 'GB',
        ]);
        $screen = AttributeDefinition::create([
            'key' => 'display_size_inches',
            'label' => 'Screen Size',
            'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
            'unit' => 'in',
        ]);

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $storage->id,
            'value' => '256',
            'raw_value' => '256',
            'display_order' => 0,
        ]);
        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $screen->id,
            'value' => '15.6',
            'raw_value' => '15.6',
            'display_order' => 1,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSeeText('Storage Capacity')
            ->assertSeeText('256 GB')
            ->assertSeeText('Screen Size')
            ->assertSeeText('15.6"');
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
            trans('tests.run_test_button'),
            trans('admin/hardware/general.edit'),
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
        $response->assertSee('data-testid="hardware-run-test-button"', false);
        $response->assertSee('hardware-run-test-button', false);
    }

    public function testDetailPageRendersResponsiveTestsStartRunActions(): void
    {
        $asset = Asset::factory()->create();
        $diagnostics = WorkflowProfile::factory()->create([
            'name' => 'Standard Diagnostics',
            'is_default' => true,
            'display_order' => 0,
        ]);
        $shipping = WorkflowProfile::factory()->create([
            'name' => 'Shipping Laptop',
            'is_default' => false,
            'display_order' => 1,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $diagnostics->id,
            'workflow_item_id' => TestType::factory()->create()->id,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $shipping->id,
            'workflow_item_id' => TestType::factory()->create()->id,
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.show', $asset));

        $response->assertOk();
        $response->assertSee('data-testid="hardware-tests-tab-actions"', false);
        $response->assertSee(trans('tests.tests_workflows'));
        $response->assertSee('data-testid="hardware-tests-start-form"', false);
        $response->assertSee('data-testid="hardware-tests-workflow-profile"', false);
        $response->assertSee('name="workflow_profile_id"', false);
        $response->assertSee('Standard Diagnostics');
        $response->assertSee('Shipping Laptop');
        $response->assertSee(trans('tests.view_all_workflows'));
        $response->assertSee('data-testid="hardware-tests-tab-fab"', false);
        $response->assertSee('data-testid="hardware-tests-tab-fab-label"', false);
        $response->assertSee(route('test-runs.store', $asset), false);
        $response->assertDontSee('data-testid="hardware-tests-start-form-desktop"', false);
        $response->assertDontSee('data-testid="hardware-tests-tab-fab-form"', false);
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
        $response->assertSee('data-testid="hardware-tests-run-list"', false);
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
        $user = User::factory()->create();
        $profile = WorkflowProfile::factory()->create([
            'name' => 'Standard Diagnostics',
            'is_default' => true,
        ]);
        $item = TestType::factory()->create([
            'name' => 'Keyboard',
            'slug' => 'keyboard',
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $profile->id,
            'workflow_item_id' => $item->id,
        ]);
        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'workflow_profile_id' => $profile->id,
            'profile_name_snapshot' => $profile->name,
            'profile_slug_snapshot' => $profile->slug,
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $item->id,
            'status' => TestResult::STATUS_PASS,
            'note' => 'Keyboard responds',
        ]);

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('test-runs.index', $asset));

        $response->assertOk();
        $response->assertSee('test-result-label', false);
        $response->assertSee('test-result-status', false);
        $response->assertSee('test-result-note', false);
        $response->assertSee('data-testid="test-run-row"', false);
        $response->assertSee('data-testid="test-run-toggle"', false);
        $response->assertSee('data-testid="test-run-details"', false);
        $response->assertSee('test-run-row__actions', false);
        $response->assertSee('data-testid="tests-index-start-run-form"', false);
        $response->assertSee('class="mb-3"', false);
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
