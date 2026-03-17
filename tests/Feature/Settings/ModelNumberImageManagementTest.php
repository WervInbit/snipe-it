<?php

namespace Tests\Feature\Settings;

use App\Models\AssetModel;
use App\Models\ModelNumberImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelNumberImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_model_number_edit_page_renders_integrated_image_manager(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('settings.model_numbers.edit', $modelNumber));

        $response
            ->assertOk()
            ->assertSee("document.addEventListener('pointermove', handlePointerMove, { passive: false });", false)
            ->assertSee('Image changes are saved together with the model number.')
            ->assertDontSee('Save Order')
            ->assertDontSee('Upload Image');
    }

    public function test_admin_can_upload_model_number_image_via_main_save(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'new_image' => [
                'caption' => 'Front view',
                'image' => UploadedFile::fake()->image('front.jpg'),
            ],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $image = ModelNumberImage::query()->where('model_number_id', $modelNumber->id)->first();
        $this->assertNotNull($image);
        $this->assertSame('Front view', $image->caption);
        $this->assertSame(0, (int) $image->sort_order);
        Storage::disk('public')->assertExists($image->file_path);
    }

    public function test_admin_can_reorder_and_update_captions_via_main_save(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $firstImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $secondImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/second.jpg',
            'caption' => 'Second',
            'sort_order' => 1,
        ]);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $firstImage->id => [
                    'caption' => 'Front updated',
                    'delete' => 0,
                ],
                $secondImage->id => [
                    'caption' => 'Back updated',
                    'delete' => 0,
                ],
            ],
            'image_order' => [$secondImage->id, $firstImage->id],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $this->assertDatabaseHas('model_number_images', [
            'id' => $secondImage->id,
            'caption' => 'Back updated',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('model_number_images', [
            'id' => $firstImage->id,
            'caption' => 'Front updated',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_remove_existing_image_via_main_save(): void
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

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $image->id => [
                    'caption' => 'Delete me',
                    'delete' => 1,
                ],
            ],
            'image_order' => [],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $this->assertDatabaseMissing('model_number_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_replace_existing_image_via_main_save(): void
    {
        Storage::fake('public');

        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $oldPath = 'model_numbers/'.$modelNumber->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'fake-image-data');

        $image = $modelNumber->images()->create([
            'file_path' => $oldPath,
            'caption' => 'Original',
            'sort_order' => 0,
        ]);

        $response = $this->put(route('settings.model_numbers.update', $modelNumber), [
            'code' => $modelNumber->code,
            'label' => $modelNumber->label,
            'status' => 'active',
            'existing_images' => [
                $image->id => [
                    'caption' => 'Updated',
                    'delete' => 0,
                    'image' => UploadedFile::fake()->image('replacement.jpg'),
                ],
            ],
            'image_order' => [$image->id],
        ]);

        $response->assertRedirect(route('settings.model_numbers.index'));

        $image->refresh();
        $this->assertSame('Updated', $image->caption);
        $this->assertNotSame($oldPath, $image->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($image->file_path);
    }

    public function test_admin_cannot_submit_partial_existing_image_payload_via_main_save(): void
    {
        $user = User::factory()->superuser()->create();
        $model = AssetModel::factory()->create();
        $modelNumber = $model->ensurePrimaryModelNumber();
        $this->actingAs($user);

        $firstImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/first.jpg',
            'caption' => 'First',
            'sort_order' => 0,
        ]);

        $secondImage = $modelNumber->images()->create([
            'file_path' => 'model_numbers/'.$modelNumber->id.'/second.jpg',
            'caption' => 'Second',
            'sort_order' => 1,
        ]);

        $response = $this->from(route('settings.model_numbers.edit', $modelNumber))
            ->put(route('settings.model_numbers.update', $modelNumber), [
                'code' => $modelNumber->code,
                'label' => $modelNumber->label,
                'status' => 'active',
                'existing_images' => [
                    $firstImage->id => [
                        'caption' => 'Only one submitted',
                        'delete' => 0,
                    ],
                ],
                'image_order' => [$firstImage->id],
            ]);

        $response
            ->assertRedirect(route('settings.model_numbers.edit', $modelNumber))
            ->assertSessionHasErrors('existing_images');

        $this->assertDatabaseHas('model_number_images', [
            'id' => $firstImage->id,
            'sort_order' => 0,
            'caption' => 'First',
        ]);
        $this->assertDatabaseHas('model_number_images', [
            'id' => $secondImage->id,
            'sort_order' => 1,
            'caption' => 'Second',
        ]);
    }
}
