<?php

namespace Tests\Feature\Settings;

use App\Models\AssetModel;
use App\Models\ModelNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelNumberSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_admin_can_view_model_numbers_page(): void
    {
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $response = $this->get(route('settings.model_numbers.index'));

        $response->assertOk();
        $response->assertSeeText('Model Numbers');
    }

    public function test_admin_can_create_model_number_from_settings(): void
    {
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $model = AssetModel::factory()->withoutModelNumber()->create();

        $response = $this->from(route('settings.model_numbers.index'))->post(route('settings.model_numbers.store'), [
            'model_id' => $model->id,
            'code' => 'NX-101',
            'label' => 'Preset A',
            'make_primary' => '1',
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('model_numbers', [
            'model_id' => $model->id,
            'code' => 'NX-101',
            'label' => 'Preset A',
        ]);

        $this->assertNotNull($model->fresh()->primary_model_number_id);
    }

    public function test_admin_can_update_model_number_from_settings(): void
    {
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $model = AssetModel::factory()->create();
        $number = $model->modelNumbers()->create(['code' => 'OLD']);

        $response = $this->from(route('settings.model_numbers.index'))->put(route('settings.model_numbers.update', $number), [
            'code' => 'NEW-CODE',
            'label' => 'Updated Label',
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('model_numbers', [
            'id' => $number->id,
            'code' => 'NEW-CODE',
            'label' => 'Updated Label',
        ]);
    }

    public function test_admin_can_delete_unused_model_number_from_settings(): void
    {
        $user = User::factory()->superuser()->create();
        $this->actingAs($user);

        $model = AssetModel::factory()->create();
        $number = $model->modelNumbers()->create(['code' => 'DELETE-ME']);

        $response = $this->from(route('settings.model_numbers.index'))->delete(route('settings.model_numbers.destroy', $number));

        $response->assertRedirect(route('settings.model_numbers.index'))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('model_numbers', ['id' => $number->id]);
    }
}
