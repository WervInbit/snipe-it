<?php

namespace Tests\Unit\Importer;

use App\Importer\AssetImporter;
use App\Models\Statuslabel;
use Tests\TestCase;
use function Livewire\invade;

class AssetImportTest extends TestCase
{
    public function test_prefers_pending_status_over_deployable_status_for_safe_intake()
    {
        Statuslabel::query()->delete();

        $pendingStatusLabel = Statuslabel::factory()->pending()->create();
        Statuslabel::factory()->readyToDeploy()->create();

        $importer = new AssetImporter('assets.csv');

        $this->assertSame(
            $pendingStatusLabel->id,
            invade($importer)->defaultStatusLabelId
        );
    }

    public function test_uses_pending_status_when_it_is_the_only_safe_status()
    {
        Statuslabel::query()->delete();

        $statusLabel = Statuslabel::factory()->pending()->create();

        $importer = new AssetImporter('assets.csv');

        $this->assertSame(
            $statusLabel->id,
            invade($importer)->defaultStatusLabelId
        );
    }

    public function test_never_defaults_to_ready_for_sale_or_sold_lifecycle_statuses()
    {
        Statuslabel::query()->delete();

        Statuslabel::factory()->pending()->create([
            'name' => 'Ready for Sale',
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
        ]);
        Statuslabel::factory()->pending()->create([
            'name' => 'Sold',
            'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
        ]);
        $safeStatusLabel = Statuslabel::factory()->pending()->create([
            'name' => 'Pending Intake',
            'default_label' => 0,
        ]);

        $importer = new AssetImporter('assets.csv');

        $this->assertSame(
            $safeStatusLabel->id,
            invade($importer)->defaultStatusLabelId
        );
    }

    public function test_creates_safe_default_status_when_only_sale_lifecycle_statuses_exist()
    {
        Statuslabel::query()->delete();

        $readyForSale = Statuslabel::factory()->pending()->create([
            'name' => 'Ready for Sale',
            'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
        ]);
        $sold = Statuslabel::factory()->pending()->create([
            'name' => 'Sold',
            'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
        ]);

        $importer = new AssetImporter('assets.csv');
        $selectedStatus = Statuslabel::findOrFail(invade($importer)->defaultStatusLabelId);

        $this->assertNotContains($selectedStatus->id, [$readyForSale->id, $sold->id]);
        $this->assertSame('Default Status', $selectedStatus->name);
        $this->assertTrue((bool) $selectedStatus->pending);
        $this->assertNull($selectedStatus->lifecycle_stage);
    }

    public function test_creates_safe_default_status_label_if_one_does_not_exist()
    {
        Statuslabel::query()->delete();

        $this->assertEquals(0, Statuslabel::count());

        $importer = new AssetImporter('assets.csv');

        $this->assertEquals(1, Statuslabel::count());

        $this->assertSame(
            Statuslabel::first()->id,
            invade($importer)->defaultStatusLabelId
        );

        $this->assertTrue((bool) Statuslabel::first()->pending);
        $this->assertNull(Statuslabel::first()->lifecycle_stage);
    }
}
