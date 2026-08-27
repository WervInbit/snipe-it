<?php

namespace Tests\Feature\AssetModels\Api;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CreateAssetModelsTest extends TestCase
{


    public function testRequiresPermissionToCreateAssetModel()
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.models.store'))
            ->assertForbidden();
    }

    public function testEditPermissionDoesNotAllowCreatingAssetModels()
    {
        $this->actingAsForApi(User::factory()->create([
            'permissions' => json_encode(['models.edit' => '1']),
        ]))
            ->postJson(route('api.models.store'), [
                'name' => 'Edit-only model',
                'category_id' => Category::factory()->assetLaptopCategory()->create()->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('models', ['name' => 'Edit-only model']);
    }

    public function testCanCreateAssetModelWithAssetModelType()
    {
        $response = $this->actingAsForApi(User::factory()->create([
            'permissions' => json_encode(['models.create' => '1']),
        ]))
            ->postJson(route('api.models.store'), [
                'name' => 'Test AssetModel',
                'category_id' => Category::factory()->assetLaptopCategory()->create()->id
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->assertStatus(200)
            ->json();

        $this->assertTrue(AssetModel::where('name', 'Test AssetModel')->exists());

        $model = AssetModel::find($response['payload']['id']);
        $this->assertEquals('Test AssetModel', $model->name);
    }

    public function testCannotCreateAssetModelWithoutCategory()
    {
        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.models.store'), [
                'name' => 'Test AssetModel',
            ])
            ->assertStatus(200)
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['category_id']])
            ->json();

        $this->assertFalse(AssetModel::where('name', 'Test AssetModel')->exists());

    }

    public function testUniquenessAcrossModelNameAndModelNumber()
    {
        AssetModel::factory()->create(['name' => 'Test Model', 'model_number'=>'1234']);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.models.store'), [
                'name' => 'Test Model',
                'model_number' => '1234',
                'category_id' => Category::factory()->assetLaptopCategory()->create()->id
            ])
            ->assertStatus(200)
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['name', 'model_number']])
            ->json();

    }

    public function testUniquenessAcrossModelNameAndModelNumberWithBlankModelNumber()
    {
        AssetModel::factory()->create(['name' => 'Test Model']);

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->postJson(route('api.models.store'), [
                'name' => 'Test Model',
                'category_id' => Category::factory()->assetLaptopCategory()->create()->id
            ])
            ->assertStatus(200)
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertJsonStructure(['messages' => ['name']])
            ->json();

    }

}
