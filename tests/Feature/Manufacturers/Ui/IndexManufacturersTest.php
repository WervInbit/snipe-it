<?php

namespace Tests\Feature\Manufacturers\Ui;

use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IndexManufacturersTest extends TestCase
{
    public function testPageRenders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('manufacturers.index'))
            ->assertOk();
    }

    public function test_empty_index_uses_normal_management_table_without_demo_seed_route(): void
    {
        Manufacturer::query()->delete();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('manufacturers.index'))
            ->assertOk()
            ->assertSee('manufacturersTable');

        $this->assertFalse(Route::has('manufacturers.seed'));
    }
}
