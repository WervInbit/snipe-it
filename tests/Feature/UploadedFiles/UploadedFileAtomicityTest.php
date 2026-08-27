<?php

namespace Tests\Feature\UploadedFiles;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class UploadedFileAtomicityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
    }

    public function testApiUploadRollsBackStoredFilesWhenTheLogCannotBeCreated(): void
    {
        $asset = Asset::factory()->create();
        $user = $this->assetFileUser('assets.files.upload');
        $eventName = 'eloquent.creating: '.Actionlog::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated action-log failure.');
        });

        try {
            $this->actingAsForApi($user)
                ->post(route('api.files.store', ['object_type' => 'assets', 'id' => $asset->id]), [
                    'file' => [UploadedFile::fake()->create('proof.txt', 1, 'text/plain')],
                ])
                ->assertStatus(500)
                ->assertStatusMessageIs('error');
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseMissing('action_logs', [
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'action_type' => 'uploaded',
        ]);
        $this->assertSame([], Storage::allFiles());
    }

    public function testWebUploadReportsAnErrorAndCleansStorageWhenTheLogFails(): void
    {
        $asset = Asset::factory()->create();
        $user = $this->assetFileUser('assets.files.upload');
        $eventName = 'eloquent.creating: '.Actionlog::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated action-log failure.');
        });

        try {
            $this->actingAs($user)
                ->from(route('hardware.show', $asset))
                ->post(route('ui.files.store', ['object_type' => 'assets', 'id' => $asset->id]), [
                    'file' => [UploadedFile::fake()->create('proof.txt', 1, 'text/plain')],
                ])
                ->assertRedirect()
                ->assertSessionHas('error')
                ->assertSessionMissing('success');
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame([], Storage::allFiles());
    }

    public function testApiDeleteRestoresTheFileWhenTheLogDeleteFails(): void
    {
        $asset = Asset::factory()->create();
        $user = $this->assetFileUser('assets.files.manage');
        $this->actingAs($user);
        $log = $asset->logUpload('proof.txt', 'proof');
        Storage::put($log->uploads_file_path(), 'original contents');
        $eventName = 'eloquent.deleting: '.Actionlog::class;

        Event::listen($eventName, function () {
            throw new RuntimeException('Simulated action-log delete failure.');
        });

        try {
            $this->actingAsForApi($user)
                ->delete(route('api.files.destroy', [
                    'object_type' => 'assets',
                    'id' => $asset->id,
                    'file_id' => $log->id,
                ]))
                ->assertStatus(500)
                ->assertStatusMessageIs('error');
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseHas('action_logs', [
            'id' => $log->id,
            'deleted_at' => null,
        ]);
        Storage::assertExists($log->uploads_file_path());
        $this->assertSame('original contents', Storage::get($log->uploads_file_path()));
    }

    private function assetFileUser(string $permission): User
    {
        return User::factory()->create([
            'permissions' => json_encode([$permission => '1']),
        ]);
    }
}
