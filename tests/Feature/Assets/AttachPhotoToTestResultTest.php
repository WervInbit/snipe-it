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

class AttachPhotoToTestResultTest extends TestCase
{
    public function test_photo_can_be_attached_to_result(): void
    {
        $asset = Asset::factory()->create();
        $type = TestType::factory()->create();
        $user = User::factory()
            ->editAssets()
            ->appendPermission(['tests.execute' => '1'])
            ->create();
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
        $this->assertFileExists(public_path($result->photo_path));

        File::delete(public_path($result->photo_path));
    }
}
