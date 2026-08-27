<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RequiredSchemaCompatibilityTest extends TestCase
{
    private const REQUIRED_LIFECYCLE_MIGRATION = '2026_07_23_130000_add_lifecycle_stage_to_status_labels';

    public function testApplicationFailsClosedWhenRequiredMigrationIsPending(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Schema::hasColumn('status_labels', 'lifecycle_stage'));
        $this->simulatePendingLifecycleMigration();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertServiceUnavailable()
            ->assertHeader('Retry-After', '60')
            ->assertSee('Application upgrade required: database migrations are incomplete.')
            ->assertSee('php artisan migrate --force');
    }

    public function testHealthCheckRemainsAvailableWhenRequiredMigrationIsPending(): void
    {
        User::factory()->create();

        $this->simulatePendingLifecycleMigration();

        $this->get(route('health'))
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function testApplicationFailsClosedWhenRequiredColumnIsMissing(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(
            DB::table('migrations')
                ->where('migration', self::REQUIRED_LIFECYCLE_MIGRATION)
                ->exists()
        );

        Schema::table('status_labels', function (Blueprint $table): void {
            $table->dropIndex(['lifecycle_stage']);
        });
        Schema::table('status_labels', function (Blueprint $table): void {
            $table->dropColumn('lifecycle_stage');
        });

        $this->actingAs($user)
            ->get(route('home'))
            ->assertServiceUnavailable()
            ->assertSee('Application upgrade required: database migrations are incomplete.');
    }

    private function simulatePendingLifecycleMigration(): void
    {
        $query = DB::table('migrations')
            ->where('migration', self::REQUIRED_LIFECYCLE_MIGRATION);

        $this->assertTrue($query->exists());
        $query->delete();
    }
}
