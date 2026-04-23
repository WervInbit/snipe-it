<?php

namespace Tests\Feature\Models;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AssetModel;
use App\Models\ComponentDefinition;
use App\Models\ModelNumberComponentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelNumberComponentTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testAdminCanCreateUpdateAndDeleteExpectedComponentTemplates(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $firstDefinition = ComponentDefinition::factory()->create([
            'name' => 'Battery Pack',
            'is_active' => true,
        ]);
        $secondDefinition = ComponentDefinition::factory()->create([
            'name' => 'Battery Module',
            'is_active' => true,
        ]);
        $specAnchor = route('models.numbers.spec.edit', [$model, $modelNumber]).'#expected-components';

        $this->actingAs($user)
            ->post(route('models.numbers.components.store', [$model, $modelNumber]), [
                'component_definition_id' => $firstDefinition->id,
                'expected_qty' => 1,
            ])
            ->assertRedirect($specAnchor);

        $template = ModelNumberComponentTemplate::query()->where('model_number_id', $modelNumber->id)->firstOrFail();

        $this->assertDatabaseHas('model_number_component_templates', [
            'id' => $template->id,
            'component_definition_id' => $firstDefinition->id,
            'expected_name' => 'Battery Pack',
            'slot_name' => null,
            'expected_qty' => 1,
            'is_required' => 1,
            'notes' => null,
        ]);

        $this->actingAs($user)
            ->put(route('models.numbers.components.update', [$model, $modelNumber, $template]), [
                'component_definition_id' => $secondDefinition->id,
                'expected_qty' => 2,
            ])
            ->assertRedirect($specAnchor);

        $this->assertDatabaseHas('model_number_component_templates', [
            'id' => $template->id,
            'component_definition_id' => $secondDefinition->id,
            'expected_name' => 'Battery Module',
            'expected_qty' => 2,
            'is_required' => 1,
            'slot_name' => null,
            'notes' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('models.numbers.components.destroy', [$model, $modelNumber, $template]))
            ->assertRedirect($specAnchor);

        $this->assertDatabaseMissing('model_number_component_templates', [
            'id' => $template->id,
        ]);
    }

    public function testAdminCanReorderExpectedComponentTemplates(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $specAnchor = route('models.numbers.spec.edit', [$model, $modelNumber]).'#expected-components';
        $first = ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'expected_name' => 'First Component',
            'sort_order' => 0,
        ]);
        $second = ModelNumberComponentTemplate::factory()->for($modelNumber)->create([
            'expected_name' => 'Second Component',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('models.numbers.components.reorder', [$model, $modelNumber]), [
                'template_id' => $second->id,
                'direction' => 'up',
            ])
            ->assertRedirect($specAnchor);

        $first->refresh();
        $second->refresh();

        $this->assertSame(1, $first->sort_order);
        $this->assertSame(0, $second->sort_order);

        $this->actingAs($user)
            ->get(route('models.numbers.components.index', [$model, $modelNumber]))
            ->assertRedirect($specAnchor);
    }
}
