<?php

namespace Tests\Feature\AssetModels\Api;

use App\Models\AssetModel;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SelectModelNumbersTest extends TestCase
{
    public function testSelectlistOmitsDeprecatedModelNumbers(): void
    {
        $model = AssetModel::factory()->create();

        $active = $model->modelNumbers()->create([
            'code' => 'ACTIVE-001',
            'label' => 'Active Preset',
        ]);

        $model->modelNumbers()->create([
            'code' => 'OLD-001',
            'label' => 'Deprecated Preset',
        ])->deprecate();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.models.selectlist'))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('results', 1)
                ->has('results.0', fn (AssertableJson $entry) => $entry
                    ->where('id', $model->id.':'.$active->id)
                    ->where('model_id', $model->id)
                    ->where('model_number_id', $active->id)
                    ->where('is_deprecated', false)
                    ->etc()
                )
                ->etc()
            );
    }

    public function testSelectlistCanIncludeDeprecatedWhenRequested(): void
    {
        $model = AssetModel::factory()->create();

        $active = $model->modelNumbers()->create([
            'code' => 'ACTIVE-002',
            'label' => 'Active Preset',
        ]);

        $deprecated = $model->modelNumbers()->create([
            'code' => 'OLD-002',
            'label' => 'Deprecated Preset',
        ]);
        $deprecated->deprecate();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.models.selectlist', ['include_deprecated' => 1]))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('results')
                ->has('results.0', fn (AssertableJson $entry) => $entry
                    ->where('id', $model->id.':'.$active->id)
                    ->where('model_id', $model->id)
                    ->where('model_number_id', $active->id)
                    ->where('is_deprecated', false)
                    ->etc()
                )
                ->has('results.1', fn (AssertableJson $entry) => $entry
                    ->where('id', $model->id.':'.$deprecated->id)
                    ->where('model_id', $model->id)
                    ->where('model_number_id', $deprecated->id)
                    ->where('is_deprecated', true)
                    ->etc()
                )
                ->etc()
            );
    }
}
