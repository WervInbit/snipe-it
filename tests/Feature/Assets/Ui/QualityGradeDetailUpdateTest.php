<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Statuslabel;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QualityGradeDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function testDetailStatusFormUpdatesQualityGrade(): void
    {
        $status = Statuslabel::factory()->pending()->create(['name' => 'Stand-by']);
        $asset = Asset::factory()->create(['status_id' => $status->id]);
        $user = User::factory()->superuser()->create();
        $this->assertTrue(Schema::hasColumn('assets', 'quality_grade'));
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $asset->status_id,
                'quality_grade' => Asset::QUALITY_GRADE_B,
                'status_change_note' => 'Quality set by dedicated grading team.',
            ])
            ->assertStatus(302);

        $asset->refresh();

        $this->assertSame(Asset::QUALITY_GRADE_B, $asset->quality_grade);
    }

    public function testDetailStatusFormRejectsInvalidQualityGrade(): void
    {
        $status = Statuslabel::factory()->pending()->create(['name' => 'Stand-by']);
        $asset = Asset::factory()->create(['status_id' => $status->id]);
        $user = User::factory()->superuser()->create();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->from(route('hardware.show', $asset))
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $asset->status_id,
                'quality_grade' => 'invalid-grade',
            ])
            ->assertStatus(302);

        $asset->refresh();

        $this->assertNull($asset->quality_grade);
    }

    public function testQualityGradeRequiresDedicatedPermission(): void
    {
        $status = Statuslabel::factory()->pending()->create(['name' => 'Stand-by']);
        $asset = Asset::factory()->create([
            'status_id' => $status->id,
            'quality_grade' => Asset::QUALITY_GRADE_B,
        ]);
        $user = User::factory()->editAssets()->viewAssets()->create();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $asset->status_id,
                'quality_grade' => Asset::QUALITY_GRADE_A,
            ])
            ->assertForbidden();

        $this->assertSame(Asset::QUALITY_GRADE_B, $asset->fresh()->quality_grade);
    }

    public function testDedicatedQualityPermissionCanEditWithoutGeneralAssetEdit(): void
    {
        $status = Statuslabel::factory()->pending()->create(['name' => 'Stand-by']);
        $asset = Asset::factory()->create(['status_id' => $status->id]);
        $user = User::factory()->viewAssets()->create([
            'permissions' => json_encode([
                'assets.view' => '1',
                'assets.quality_grade.update' => '1',
            ]),
        ]);
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($user)
            ->patch(route('hardware.status.update', $asset), [
                'status_id' => $asset->status_id,
                'quality_grade' => Asset::QUALITY_GRADE_A,
            ])
            ->assertRedirect(route('hardware.show', $asset));

        $this->assertSame(Asset::QUALITY_GRADE_A, $asset->fresh()->quality_grade);
    }
}
