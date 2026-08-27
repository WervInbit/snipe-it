<?php

namespace Tests\Feature\Categories\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class IndexCategoriesTest extends TestCase
{

    public function testViewingCategoryIndexRequiresPermission()
    {
        $this->actingAsForApi(User::factory()->create())
            ->getJson(route('api.categories.index'))
            ->assertForbidden();
    }

    public function testCategoryIndexReturnsExpectedSearchResults()
    {
        Category::factory()->count(10)->create();
        Category::factory()->count(1)->forAssets()->create(['name' => 'My Test Category']);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(
                route('api.categories.index', [
                    'search' => 'My Test Category',
                    'sort' => 'name',
                    'order' => 'asc',
                    'offset' => '0',
                    'limit' => '20',
                ]))
            ->assertOk()
            ->assertJsonStructure([
                'total',
                'rows',
            ])
            ->assertJson([
                'total' => 1,
            ]);

    }


    public function testCategoryIndexReturnsExpectedCategories()
    {
        Category::factory()->count(3)->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(
                route('api.categories.index', [
                    'category_type' => 'asset',
                    'sort' => 'id',
                    'order' => 'asc',
                    'offset' => '0',
                    'limit' => '20',
                ]))
            ->assertOk()
            ->assertJsonStructure([
                'total',
                'rows',
            ])
            ->assertJson([
                'total' => 3,
            ]);

    }

}
