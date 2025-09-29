<?php

namespace Tests\Feature;

use App\Http\Controllers\TestRunController;
use App\Models\Asset;
use App\Models\AssetAttributeOverride;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\User;
use App\Services\ModelAttributes\EffectiveAttributeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttributeTestRunGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_tests_from_attribute_definitions_with_overrides(): void
    {
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $category = Category::factory()->create();

        $definition = AttributeDefinition::create([
            'key' => 'storage_capacity',
            'label' => 'Storage Capacity',
            'datatype' => AttributeDefinition::DATATYPE_BOOL,
            'unit' => null,
            'required_for_category' => true,
            'needs_test' => true,
            'allow_custom_values' => false,
            'allow_asset_override' => true,
        ]);
        $definition->categories()->sync([$category->id]);

        $model = AssetModel::factory()->create([
            'category_id' => $category->id,
        ]);

        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'value' => '1',
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        AssetAttributeOverride::create([
            'asset_id' => $asset->id,
            'attribute_definition_id' => $definition->id,
            'value' => '0',
        ]);

        $controller = app(TestRunController::class);
        $request = Request::create('/hardware/'.$asset->id.'/tests', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = $controller->store($request, $asset, app(EffectiveAttributeResolver::class));

        $run = TestRun::first();
        $this->assertNotNull($run, 'Test run was not created');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame(route('test-results.edit', [$asset->id, $run->id]), $response->getTargetUrl());

        $result = $run->results()->first();
        $this->assertNotNull($result, 'Test result was not created');
        $this->assertSame($definition->id, $result->attribute_definition_id);
        $this->assertSame(TestResult::STATUS_NVT, $result->status);
        $this->assertSame('0', $result->expected_value);
        $this->assertNull($result->expected_raw_value);
    }
}
