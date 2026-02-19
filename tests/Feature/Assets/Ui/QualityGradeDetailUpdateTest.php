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
}
