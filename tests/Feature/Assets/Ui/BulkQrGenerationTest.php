<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class BulkQrGenerationTest extends TestCase
{
    public function testCanGenerateBatchQrPdf()
    {
        $assets = Asset::factory()->count(3)->create();
        $ids = $assets->pluck('id')->toArray();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->post('/hardware/bulkedit', [
                'ids' => $ids,
                'bulk_actions' => 'qr',
            ])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}

