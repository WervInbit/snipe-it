<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\User;
use Tests\TestCase;

class AssetIndexTest extends TestCase
{
    public function testPageRenders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index'))
            ->assertOk();
    }

    public function testPageUsesResponsiveBulkToolbarMarkup(): void
    {
        $response = $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index'));

        $response->assertOk();
        $response->assertSee('bulk-edit-toolbar bulk-edit-toolbar--assets', false);
        $response->assertSee('bulk-edit-toolbar__form', false);
        $response->assertSee('bulk-edit-toolbar__select', false);
        $response->assertDontSee('min-width:400px', false);
        $response->assertDontSee('min-width: 350px !important;', false);
    }
}
