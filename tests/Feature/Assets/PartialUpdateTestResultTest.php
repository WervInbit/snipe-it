<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
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
                'photo' => true,
                'message' => trans('general.saved'),
            ])
            ->assertJsonStructure(['photo_url']);

        $result->refresh();
        $this->assertNotNull($result->photo_path);
        $this->assertTrue(File::exists(public_path($result->photo_path)));

        // Clean up the uploaded file to avoid leaking artefacts across tests.
        File::delete(public_path($result->photo_path));
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

        $result->refresh();
        $this->assertNotNull($result->photo_path);
        $photoPath = public_path($result->photo_path);
        $this->assertTrue(File::exists($photoPath));

        $response = $this->actingAs($user, 'web')->postJson(
            route('test-results.partial-update', [$asset->id, $run->id, $result->id]),
            ['remove_photo' => true]
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'photo' => false,
                'message' => trans('general.saved'),
            ]);

        $result->refresh();
        $this->assertNull($result->photo_path);
        $this->assertFalse(File::exists($photoPath));
    }
}
