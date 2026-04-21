<?php

namespace Tests\Feature\Components\Api;

use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ComponentInstanceFilesTest extends TestCase
{
    public function testComponentInstanceApiAcceptsFileUpload(): void
    {
        $instance = ComponentInstance::factory()->create();
        $user = User::factory()->superuser()->create();

        $this->actingAsForApi($user)
            ->post(route('api.files.store', [
                'object_type' => 'component-instances',
                'id' => $instance->id,
            ]), [
                'file' => [UploadedFile::fake()->create('part-photo.jpg', 100)],
            ])
            ->assertOk();
    }
}
