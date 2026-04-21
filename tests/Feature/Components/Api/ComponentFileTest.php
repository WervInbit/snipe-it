<?php

namespace Tests\Feature\Components\Api;

use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ComponentFileTest extends TestCase
{
    public function testComponentApiAcceptsFileUpload(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->post(
                route('api.files.store', ['object_type' => 'component-instances', 'id' => $component->id]), [
                'file' => [UploadedFile::fake()->create("test.jpg", 100)]
                ]
            )
            ->assertOk();
    }

    public function testComponentApiListsFiles(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->getJson(
                route('api.files.index', ['object_type' => 'component-instances', 'id' => $component->id])
            )
            ->assertOk()
            ->assertJsonStructure(
                [
                'rows',
                'total',
                ]
            );
    }

    public function testComponentFailsIfInvalidTypePassedInUrl(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->getJson(
                route('api.files.index', ['object_type' => 'shibboleeeeeet', 'id' => $component->id])
            )
            ->assertStatus(404);
    }

    public function testComponentFailsIfInvalidIdPassedInUrl(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->getJson(
                route('api.files.index', ['object_type' => 'component-instances', 'id' => 100000])
            )
            ->assertOk()
            ->assertStatusMessageIs('error');
    }

    public function testComponentApiDownloadsFile(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->post(
                route('api.files.store', ['object_type' => 'component-instances', 'id' => $component->id]), [
                'file' => [UploadedFile::fake()->create("test.jpg", 100)],
                ]
            )
            ->assertOk()
            ->assertJsonStructure(
                [
                'status',
                'messages',
                ]
            );

        $this->actingAsForApi($user)
            ->post(
                route('api.files.store', ['object_type' => 'component-instances', 'id' => $component->id]), [
                'file' => [UploadedFile::fake()->create("test.jpg", 100)],
                'notes' => 'manual'
                ]
            )
            ->assertOk()
            ->assertJsonStructure(
                [
                'status',
                'messages',
                ]
            );

        $result = $this->actingAsForApi($user)
            ->getJson(
                route('api.files.index', ['object_type' => 'component-instances', 'id' => $component->id, 'order' => 'asc'])
            )
            ->assertOk()
            ->assertJsonStructure(
                [
                'total',
                'rows'=>[
                    '*' => [
                        'id',
                        'filename',
                        'url',
                        'created_by',
                        'created_at',
                        'deleted_at',
                        'note',
                        'available_actions'
                    ]
                ]
                ]
            )
            ->assertJsonPath('rows.0.note', null)
            ->assertJsonPath('rows.1.note', 'manual');

        $this->actingAsForApi($user)
            ->get(
                route(
                    'api.files.show', [
                    'object_type' => 'component-instances',
                    'id' => $component->id,
                    'file_id' => $result->decodeResponseJson()->json()["rows"][0]["id"],
                    ]
                )
            )
            ->assertOk();
    }

    public function testComponentApiDeletesFile(): void
    {
        $component = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->post(
                route('api.files.store', ['object_type' => 'component-instances', 'id' => $component->id]), [
                'file' => [UploadedFile::fake()->create("test.jpg", 100)]
                ]
            )
            ->assertOk();

        $result = $this->actingAsForApi($user)
            ->getJson(
                route('api.files.index', ['object_type' => 'component-instances', 'id' => $component->id])
            )
            ->assertOk();

        $this->actingAsForApi($user)
            ->delete(
                route(
                    'api.files.destroy', [
                    'object_type' => 'component-instances',
                    'id' => $component->id,
                    'file_id' => $result->decodeResponseJson()->json()["rows"][0]["id"],
                    ]
                )
            )
            ->assertOk()
            ->assertJsonStructure(
                [
                'status',
                'messages',
                ]
            );
    }
}
