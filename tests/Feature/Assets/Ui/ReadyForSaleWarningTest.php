<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class ReadyForSaleWarningTest extends TestCase
{
    public function testWarningShownWhenFailedTestsExist(): void
    {
        $readyForSale = Statuslabel::factory()->rtd()->create(['name' => 'Ready for Sale']);
        $originalStatus = Statuslabel::factory()->pending()->create();
        $asset = Asset::factory()->create(['status_id' => $originalStatus->id]);

        $run = TestRun::factory()->create(['asset_id' => $asset->id]);
        TestResult::factory()->create([
            'test_run_id' => $run->id,
            'status' => TestResult::STATUS_FAIL,
            'test_type_id' => TestType::factory()->create(['name' => 'Battery'])->id,
        ]);

        $user = User::factory()->viewAssets()->editAssets()->create();

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
}
