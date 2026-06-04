<?php

namespace Tests\Feature\Assets\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReadyForSaleWarningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testWarningShownWhenFailedTestsExist(): void
    {
        $readyForSale = Statuslabel::factory()->rtd()->create([
            'name' => 'Ready for Sale ' . Str::uuid(),
            'default_label' => 0,
        ]);
        $originalStatus = Statuslabel::factory()->pending()->create([
            'name' => 'Pending ' . Str::uuid(),
            'default_label' => 0,
        ]);
        $asset = Asset::factory()->create(['status_id' => $originalStatus->id]);

        $run = TestRun::factory()->create(['asset_id' => $asset->id]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'status' => TestResult::STATUS_FAIL,
            'workflow_item_id' => TestType::factory()->create(['name' => 'Battery'])->id,
        ]);

        $user = User::factory()->superuser()->create();

        $this->actingAs($user)
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'status_id' => $readyForSale->id,
            ])
            ->assertRedirect(route('hardware.edit', $asset))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_failed_tests');

        $asset->refresh();
        $this->assertNotEquals($readyForSale->id, $asset->status_id);

        $this->actingAs($user)
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'status_id' => $readyForSale->id,
                'ack_failed_tests' => 1,
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertEquals($readyForSale->id, $asset->status_id);
    }

    public function testWarningShownWhenBlockingWorkflowProfileHasNoRun(): void
    {
        $readyForSale = Statuslabel::factory()->rtd()->create([
            'name' => 'Ready for Sale ' . Str::uuid(),
            'default_label' => 0,
        ]);
        $originalStatus = Statuslabel::factory()->pending()->create([
            'name' => 'Pending ' . Str::uuid(),
            'default_label' => 0,
        ]);
        $asset = Asset::factory()->create(['status_id' => $originalStatus->id]);
        $user = User::factory()->superuser()->create();

        $diagnosticItem = TestType::factory()->create(['name' => 'Diagnostic']);
        $photoItem = TestType::factory()->create(['name' => 'Sale Photos']);
        $diagnostics = WorkflowProfile::factory()->create([
            'name' => 'Diagnostics',
            'blocks_sale_readiness' => true,
        ]);
        $salePhotos = WorkflowProfile::factory()->create([
            'name' => 'Sale Photos',
            'blocks_sale_readiness' => true,
        ]);

        $diagnosticProfileItem = WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $diagnostics->id,
            'workflow_item_id' => $diagnosticItem->id,
            'is_required' => true,
        ]);
        WorkflowProfileItem::factory()->create([
            'workflow_profile_id' => $salePhotos->id,
            'workflow_item_id' => $photoItem->id,
            'is_required' => true,
        ]);

        $run = TestRun::factory()->create([
            'asset_id' => $asset->id,
            'workflow_profile_id' => $diagnostics->id,
        ]);
        TestResult::factory()->create([
            'workflow_run_id' => $run->id,
            'workflow_item_id' => $diagnosticItem->id,
            'workflow_profile_item_id' => $diagnosticProfileItem->id,
            'status' => TestResult::STATUS_PASS,
            'is_required' => true,
        ]);

        $asset->refreshTestCompletionFlag();
        $asset->refresh();
        $this->assertFalse($asset->tests_completed_ok);

        $this->actingAs($user)
            ->from(route('hardware.edit', $asset))
            ->put(route('hardware.update', $asset), [
                'asset_tags' => $asset->asset_tag,
                'status_id' => $readyForSale->id,
            ])
            ->assertRedirect(route('hardware.edit', $asset))
            ->assertSessionHas('warning')
            ->assertSessionHas('requires_ack_failed_tests')
            ->assertSessionHas('test_issue_details', function (array $details): bool {
                return str_contains(implode(' ', $details), 'Sale Photos');
            });

        $asset->refresh();
        $this->assertNotEquals($readyForSale->id, $asset->status_id);
    }
}
