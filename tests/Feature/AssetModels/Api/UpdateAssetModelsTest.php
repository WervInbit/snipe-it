<?php

namespace Tests\Feature\AssetModels\Api;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class UpdateAssetModelsTest extends TestCase
{
    public function testRequiresPermissionToEditAssetModel()
    {
        $model = AssetModel::factory()->create();
        $this->actingAsForApi(User::factory()->create())
            ->patchJson(route('api.models.update', $model))
            ->assertForbidden();
    }

    public function testCreatePermissionDoesNotAllowEditingAssetModels()
    {
        $model = AssetModel::factory()->create();

        $this->actingAsForApi(User::factory()->create([
            'permissions' => json_encode(['models.create' => '1']),
        ]))
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Create-only update',
                'category_id' => $model->category_id,
            ])
            ->assertForbidden();

        $this->assertNotSame('Create-only update', $model->fresh()->name);
    }

    public function testCanUpdateAssetModelViaPatch()
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->actingAsForApi(User::factory()->create([
            'permissions' => json_encode(['models.edit' => '1']),
        ]))
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Test Model',
                'category_id' => Category::factory()->forAssets()->create()->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('success')
            ->assertStatus(200)
            ->json();

        $model->refresh();
        $this->assertEquals('Test Model', $model->name, 'Name was not updated');
        $this->assertSame($modelNumber->id, $model->primary_model_number_id);
        $this->assertSame($modelNumber->code, $model->model_number);
        $this->assertDatabaseHas('model_numbers', [
            'id' => $modelNumber->id,
            'code' => $modelNumber->code,
        ]);
    }

    public function testExplicitModelNumberUpdateKeepsPrimaryPresetInSync(): void
    {
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.models.update', $model), [
                'name' => $model->name,
                'category_id' => $model->category_id,
                'model_number' => 'UPDATED-PRIMARY',
            ])
            ->assertOk()
            ->assertStatusMessageIs('success');

        $model->refresh();
        $modelNumber->refresh();

        $this->assertSame('UPDATED-PRIMARY', $model->model_number);
        $this->assertSame($modelNumber->id, $model->primary_model_number_id);
        $this->assertSame('UPDATED-PRIMARY', $modelNumber->code);
    }

    public function testCannotUpdateAssetModelViaPatchWithAccessoryCategory()
    {
        $category = Category::factory()->forAccessories()->create();
        $model = AssetModel::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Test Model',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertStatus(200)
            ->json();

        $category->refresh();
        $this->assertNotEquals('Test Model', $model->name, 'Name was not updated');
        $this->assertNotEquals('category_id', $category->id, 'Category ID was not updated');
    }

    public function testCannotUpdateAssetModelViaPatchWithLicenseCategory()
    {
        $category = Category::factory()->forLicenses()->create();
        $model = AssetModel::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Test Model',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertStatus(200)
            ->json();

        $category->refresh();
        $this->assertNotEquals('Test Model', $model->name, 'Name was not updated');
        $this->assertNotEquals('category_id', $category->id, 'Category ID was not updated');
    }

    public function testCannotUpdateAssetModelViaPatchWithConsumableCategory()
    {
        $category = Category::factory()->forConsumables()->create();
        $model = AssetModel::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Test Model',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertStatus(200)
            ->json();

        $category->refresh();
        $this->assertNotEquals('Test Model', $model->name, 'Name was not updated');
        $this->assertNotEquals('category_id', $category->id, 'Category ID was not updated');
    }

    public function testCannotUpdateAssetModelViaPatchWithComponentCategory()
    {
        $category = Category::factory()->forComponents()->create();
        $model = AssetModel::factory()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(route('api.models.update', $model), [
                'name' => 'Test Model',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertStatusMessageIs('error')
            ->assertStatus(200)
            ->json();

        $category->refresh();
        $this->assertNotEquals('Test Model', $model->name, 'Name was not updated');
        $this->assertNotEquals('category_id', $category->id, 'Category ID was not updated');
    }
}
