<?php

namespace Tests\Feature\Settings;

use App\Http\Controllers\Admin\ModelNumberImageController;
use App\Models\AssetModel;
use App\Models\ModelNumberImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelNumberImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_admin_can_upload_model_number_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $request = Request::create(
            route('model_numbers.images.store', $modelNumber),
            'POST',
            [
                'caption' => 'Front view',
                'sort_order' => 2,
            ],
            [],
            [
                'image' => UploadedFile::fake()->image('front.jpg'),
            ],
            [
                'HTTP_REFERER' => route('settings.model_numbers.edit', $modelNumber),
            ]
        );

        $request->setLaravelSession($this->app['session.store']);
        $request->session()->start();

        $response = app(ModelNumberImageController::class)->store($request, $modelNumber);
        $this->assertTrue($response->isRedirect());

        $image = ModelNumberImage::query()->where('model_number_id', $modelNumber->id)->first();
        $this->assertNotNull($image);
        $this->assertSame('Front view', $image->caption);
        $this->assertSame(2, (int) $image->sort_order);
        Storage::disk('public')->assertExists($image->file_path);
    }

    public function test_admin_can_reorder_model_number_image(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $image = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/existing.jpg',
            'caption' => 'Old caption',
            'sort_order' => 5,
        ]);

        $request = Request::create(
            route('model_numbers.images.update', [$modelNumber, $image]),
            'PUT',
            [
                'caption' => 'Updated caption',
                'sort_order' => 1,
            ],
            [],
            [],
            [
                'HTTP_REFERER' => route('settings.model_numbers.edit', $modelNumber),
            ]
        );

        $request->setLaravelSession($this->app['session.store']);
        $request->session()->start();
        $response = app(ModelNumberImageController::class)->update($request, $modelNumber, $image);
        $this->assertTrue($response->isRedirect());

        $this->assertDatabaseHas('model_number_images', [
            'id' => $image->id,
            'caption' => 'Updated caption',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_delete_model_number_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $path = 'model_numbers/'.$modelNumber->id.'/delete-me.jpg';
        Storage::disk('public')->put($path, 'fake-image-data');

        $image = $modelNumber->images()->create([
            'file_path' => $path,
            'caption' => 'Delete me',
            'sort_order' => 0,
        ]);

        $request = Request::create(
            route('model_numbers.images.destroy', [$modelNumber, $image]),
            'DELETE',
            [],
            [],
            [],
            [
                'HTTP_REFERER' => route('settings.model_numbers.edit', $modelNumber),
            ]
        );

        $request->setLaravelSession($this->app['session.store']);
        $request->session()->start();
        $response = app(ModelNumberImageController::class)->destroy($modelNumber, $image);
        $this->assertTrue($response->isRedirect());

        $this->assertDatabaseMissing('model_number_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
