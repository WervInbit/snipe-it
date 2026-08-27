<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestResult;
use App\Models\TestType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachPhotoToTestResultTest extends TestCase
{
    public function test_photo_can_be_attached_to_result(): void
    {
        Storage::fake(config('filesystems.default'));

        $asset = Asset::factory()->create();
        $type = TestType::factory()->create();
        $user = User::factory()->superuser()->create();
        $run = TestRun::factory()->for($asset)->for($user)->create();
        $result = TestResult::factory()->for($run)
            ->for($type, 'type')
            ->create(['status' => TestResult::STATUS_NVT]);

        $file = UploadedFile::fake()->image('damage.jpg');

        $response = $this->actingAs($user)->put(
            route('test-results.update', [$asset->id, $run->id]),
            [
                'status' => [$result->id => TestResult::STATUS_FAIL],
                'photo'  => [$result->id => $file],
            ]
        );

        $response->assertRedirect(route('test-runs.index', $asset->id));

        $result->refresh();
        $this->assertNotNull($result->photo_path);
        $this->assertStringStartsWith('private_uploads/workflow_evidence/results/'.$result->id.'/', $result->photo_path);
        Storage::disk(config('filesystems.default'))->assertExists($result->photo_path);
        $this->assertFalse(File::exists(public_path($result->photo_path)));
    }
}
