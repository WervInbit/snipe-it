<?php

namespace Tests\Feature\Models;

use App\Models\AssetModel;
use App\Http\Controllers\Admin\ModelNumberController;
use App\Models\ModelNumber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelNumberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_model_number_and_set_primary(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();

        $this->actingAs($user);

        $request = Request::create(route('models.numbers.store', $model), 'POST', [
            'code' => 'ALT-123',
            'label' => 'Alternate Preset',
            'make_primary' => '1',
        ]);

        $request->setLaravelSession($this->app['session.store']);
        $request->session()->start();
        $request->setUserResolver(fn () => $user);

        $response = app(ModelNumberController::class)->store($request, $model);

        $this->assertSame(route('models.show', $model), $response->getTargetUrl());

        $this->assertDatabaseHas('model_numbers', [
            'model_id' => $model->id,
            'code' => 'ALT-123',
            'label' => 'Alternate Preset',
        ]);

        $model = $model->fresh(['primaryModelNumber']);
        $modelNumberId = $model->modelNumbers()->where('code', 'ALT-123')->value('id');

        $this->assertNotNull($modelNumberId);
        $this->assertNotNull($model->primary_model_number_id);
        $this->assertSame($modelNumberId, $model->primary_model_number_id);
        $this->assertSame('ALT-123', $model->primaryModelNumber->code);
        $this->assertSame('ALT-123', $model->model_number);
        session()->forget('success');
    }

    public function test_admin_cannot_delete_primary_model_number(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $primary = $model->ensurePrimaryModelNumber();

        $this->actingAs($user);

        $response = app(ModelNumberController::class)->destroy($model, $primary);

        $this->assertSame(route('models.show', $model), $response->getTargetUrl());
        $this->assertTrue(session()->has('error'));
        session()->forget('error');

        $this->assertDatabaseHas('model_numbers', ['id' => $primary->id]);
    }

    public function test_admin_can_delete_unused_model_number(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $model->ensurePrimaryModelNumber();

        $secondary = ModelNumber::create([
            'model_id' => $model->id,
            'code' => 'SECONDARY',
            'label' => null,
        ]);

        $this->actingAs($user);

        $response = app(ModelNumberController::class)->destroy($model, $secondary);

        $this->assertSame(route('models.show', $model), $response->getTargetUrl());
        $this->assertTrue(session()->has('success'));
        session()->forget('success');

        $this->assertDatabaseMissing('model_numbers', ['id' => $secondary->id]);
    }
}
