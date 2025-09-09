<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Statuslabel;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusDropdownIncludesNewStatusesTest extends TestCase
{
    #[Test]
    public function new_statuses_show_in_asset_form()
    {
        $admin = User::factory()->admin()->create();
        Statuslabel::factory()->beingRefurbished()->create(['created_by' => $admin->id]);
        Statuslabel::factory()->brokenSpareParts()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('hardware.create'))
            ->assertSee('Being Refurbished')
            ->assertSee('Broken/Spare Parts');
    }
}
