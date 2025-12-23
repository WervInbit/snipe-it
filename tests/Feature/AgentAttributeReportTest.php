<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\ModelNumberAttribute;
use App\Models\TestResult;
use App\Models\TestRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentAttributeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_report_creates_attribute_based_results(): void
    {
        config([
            'agent.api_token' => 'test-token',
            'agent.allowed_ips' => [],
            'agent.user_id' => null,
        ]);

        $category = Category::factory()->create();

        $definition = AttributeDefinition::create([
            'key' => 'ram_capacity',
            'label' => 'RAM Capacity',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'unit' => 'GB',
            'required_for_category' => true,
            'allow_custom_values' => false,
            'allow_asset_override' => false,
        ]);
        $definition->categories()->sync([$category->id]);

        $testType = \App\Models\TestType::factory()->create([
            'attribute_definition_id' => $definition->id,
            'slug' => 'ram-capacity-test',
        ]);

        $model = AssetModel::factory()->create([
            'category_id' => $category->id,
        ]);

        $modelNumber = $model->ensurePrimaryModelNumber();

        ModelNumberAttribute::create([
            'model_number_id' => $modelNumber->id,
            'attribute_definition_id' => $definition->id,
            'value' => '16',
            'raw_value' => '16 GB',
        ]);

        $asset = Asset::factory()->create([
            'model_id' => $model->id,
            'model_number_id' => $modelNumber->id,
        ]);

        $payload = [
            'type' => 'test_results',
            'asset_tag' => (string) $asset->asset_tag,
            'results' => [
                [
                    'test_slug' => $testType->slug,
                    'status' => TestResult::STATUS_PASS,
                    'note' => 'Diagnostics passed',
                ],
            ],
        ];

        $response = $this->postJson(route('api.agent.reports.store'), $payload, [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Test results recorded']);

        $run = TestRun::first();
        $this->assertNotNull($run, 'Test run was not created');

        $result = $run->results()->first();
        $this->assertNotNull($result, 'Test result was not created');
        $this->assertSame($definition->id, $result->attribute_definition_id);
        $this->assertSame(TestResult::STATUS_PASS, $result->status);
        $this->assertSame('16', $result->expected_value);
        $this->assertSame('16 GB', $result->expected_raw_value);
        $this->assertSame('Diagnostics passed', $result->note);
    }
}
