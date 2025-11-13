<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestResultPhoto;
use App\Models\TestType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PartialUpdateTestResultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeRun(): array
    {
        $asset = Asset::factory()->create();
        $user = User::factory()->refurbisher()->create();
        $run = TestRun::factory()
            ->for($asset)
            ->for($user)
            ->create([
                'finished_at' => now()->subDay(),
            ]);

        $type = TestType::factory()->create();
        $result = TestResult::factory()
            ->for($run)
            ->for($type, 'type')
            ->create(['status' => TestResult::STATUS_NVT]);

        return [$asset, $run, $result, $user];
    }

    public function test_status_can_be_updated_via_partial_endpoint(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $originalFinishedAt = $run->finished_at;

        $response = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['status' => TestResult::STATUS_PASS]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'status' => TestResult::STATUS_PASS,
                'message' => trans('general.saved'),
            ]);

        $result->refresh();
        $run->refresh();

        $this->assertSame(TestResult::STATUS_PASS, $result->status);
        $this->assertTrue($run->finished_at->gt($originalFinishedAt));
    }

    public function test_status_can_be_cleared_via_partial_endpoint(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $result->update(['status' => TestResult::STATUS_FAIL]);

        $response = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['status' => '']
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'status' => TestResult::STATUS_NVT,
                'message' => trans('general.saved'),
            ]);

        $result->refresh();
        $this->assertSame(TestResult::STATUS_NVT, $result->status);
    }

    public function test_note_updates_are_persisted(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();

        $note = 'Battery requires replacement cycle follow-up.';

        $response = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['note' => $note]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'note' => $note,
                'message' => trans('general.saved'),
            ]);

        $result->refresh();
        $this->assertSame($note, $result->note);
    }

    public function test_photo_can_be_uploaded_and_path_is_stored(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();

        $file = UploadedFile::fake()->image('diagnostic.jpg', 800, 600);

        $response = $this->actingAs($user, 'web')->post(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['photo' => $file],
            ['HTTP_ACCEPT' => 'application/json']
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'message' => trans('general.saved'),
            ])
            ->assertJsonStructure(['photo' => ['id', 'url'], 'photos']);

        $payload = $response->json();
        $photoId = $payload['photo']['id'];

        $result->refresh();
        $photoRecord = TestResultPhoto::where('test_result_id', $result->id)->first();
        $this->assertNotNull($photoRecord);
        $this->assertSame($photoId, $photoRecord->id);
        $this->assertTrue(File::exists(public_path($photoRecord->path)));

        // Clean up uploaded files
        File::delete(public_path($photoRecord->path));
        $photoRecord->delete();
    }

    public function test_photo_can_be_removed(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();
        $file = UploadedFile::fake()->image('camera-check.jpg', 640, 480);

        $uploadResponse = $this->actingAs($user, 'web')->post(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['photo' => $file],
            ['HTTP_ACCEPT' => 'application/json']
        );
        $uploadResponse->assertOk();
        $photoId = $uploadResponse->json('photo.id');

        $result->refresh();
        $photoRecord = $result->photos()->find($photoId);
        $this->assertNotNull($photoRecord);
        $photoPath = public_path($photoRecord->path);
        $this->assertTrue(File::exists($photoPath));

        $response = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['remove_photo_id' => $photoId]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'removed_photo_id' => $photoId,
                'message' => trans('general.saved'),
            ]);

        $result->refresh();
        $this->assertNull($result->photos()->find($photoId));
        $this->assertFalse(File::exists($photoPath));
    }

    public function test_multiple_photos_can_be_uploaded_and_removed_individually(): void
    {
        [$asset, $run, $result, $user] = $this->makeRun();

        $files = [
            UploadedFile::fake()->image('first.jpg', 400, 400),
            UploadedFile::fake()->image('second.jpg', 400, 400),
        ];

        $photoIds = [];
        foreach ($files as $file) {
            $uploadResponse = $this->actingAs($user, 'web')->post(
                route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
                ['photo' => $file],
                ['HTTP_ACCEPT' => 'application/json']
            );
            $uploadResponse->assertOk();
            $photoIds[] = $uploadResponse->json('photo.id');
        }

        $this->assertCount(2, $result->fresh()->photos);

        $firstPhoto = TestResultPhoto::find($photoIds[0]);
        $firstPath = public_path($firstPhoto->path);
        $this->assertTrue(File::exists($firstPath));

        $deleteResponse = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['remove_photo_id' => $photoIds[0]]
        );

        $deleteResponse->assertOk()->assertJsonFragment([
            'removed_photo_id' => $photoIds[0],
        ]);

        $result->refresh();
        $this->assertNull(TestResultPhoto::find($photoIds[0]));
        $remainingPhoto = TestResultPhoto::find($photoIds[1]);
        $this->assertNotNull($remainingPhoto);
        $this->assertTrue(File::exists(public_path($remainingPhoto->path)));
        $this->assertFalse(File::exists($firstPath));

        // Clean up remaining photo
        foreach (TestResultPhoto::where('test_result_id', $result->id)->get() as $photo) {
            File::delete(public_path($photo->path));
            $photo->delete();
        }
    }
}
