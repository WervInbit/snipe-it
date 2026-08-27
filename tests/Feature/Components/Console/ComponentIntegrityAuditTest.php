<?php

namespace Tests\Feature\Components\Console;

use App\Models\Asset;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ComponentExpectedSubcomponentState;
use App\Models\ComponentInstance;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComponentIntegrityAuditTest extends TestCase
{
    public function testCleanHierarchyExitsSuccessfullyWithoutWritingData(): void
    {
        $parentDefinition = ComponentDefinition::factory()->create();
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'expected_qty' => 2,
        ]);
        $parent = ComponentInstance::factory()->create([
            'component_definition_id' => $parentDefinition->id,
        ]);
        $child = ComponentInstance::factory()->asChildOf($parent)->create();
        $state = ComponentExpectedSubcomponentState::factory()->create([
            'component_instance_id' => $parent->id,
            'component_definition_subcomponent_template_id' => $template->id,
            'materialized_qty' => 1,
            'removed_qty' => 1,
        ]);
        $writes = [];

        DB::listen(function ($query) use (&$writes): void {
            if (preg_match('/^\s*(insert|update|delete|alter|drop|create|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $exitCode = Artisan::call('components:audit-integrity', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertTrue($report['read_only']);
        $this->assertTrue($report['ok']);
        $this->assertSame(0, $report['summary']['total_finding_rows']);
        $this->assertSame([], $writes);
        $this->assertDatabaseHas('component_instances', [
            'id' => $parent->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('component_instances', [
            'id' => $child->id,
            'parent_component_instance_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('component_expected_subcomponent_states', [
            'id' => $state->id,
            'materialized_qty' => 1,
            'removed_qty' => 1,
        ]);
    }

    public function testCorruptionExitsNonzeroAndReportsExactIdsAndCountsWithoutRepair(): void
    {
        $assetA = Asset::factory()->create();
        $assetB = Asset::factory()->create();

        $deletedParent = ComponentInstance::factory()->installed($assetA->id)->create();
        $orphanedChild = ComponentInstance::factory()->asChildOf($deletedParent)->create();
        $deletedParent->delete();

        $liveParent = ComponentInstance::factory()->installed($assetA->id)->create();
        $mismatchedChild = ComponentInstance::factory()->asChildOf($liveParent)->create();
        DB::table('component_instances')
            ->where('id', $mismatchedChild->id)
            ->update([
                'current_asset_id' => $assetB->id,
                'root_asset_id' => $assetB->id,
            ]);

        $parentDefinition = ComponentDefinition::factory()->create();
        $template = ComponentDefinitionSubcomponentTemplate::factory()->create([
            'parent_component_definition_id' => $parentDefinition->id,
            'expected_qty' => 2,
        ]);
        $expectedParent = ComponentInstance::factory()->create([
            'component_definition_id' => $parentDefinition->id,
        ]);
        $overflowState = ComponentExpectedSubcomponentState::factory()->create([
            'component_instance_id' => $expectedParent->id,
            'component_definition_subcomponent_template_id' => $template->id,
            'materialized_qty' => 2,
            'removed_qty' => 1,
        ]);
        $writes = [];

        DB::listen(function ($query) use (&$writes): void {
            if (preg_match('/^\s*(insert|update|delete|alter|drop|create|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $exitCode = Artisan::call('components:audit-integrity', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertTrue($report['read_only']);
        $this->assertFalse($report['ok']);
        $this->assertSame(1, $report['summary']['live_children_with_missing_or_deleted_parent']);
        $this->assertSame(1, $report['summary']['parent_child_asset_or_root_mismatches']);
        $this->assertSame(1, $report['summary']['attached_children_without_live_parent']);
        $this->assertSame(1, $report['summary']['impossible_expected_state_counters']);
        $this->assertSame(
            $orphanedChild->id,
            $report['findings']['orphaned_children'][0]['child_component_id']
        );
        $this->assertSame(
            $deletedParent->id,
            $report['findings']['orphaned_children'][0]['parent_component_id']
        );
        $this->assertSame('soft_deleted', $report['findings']['orphaned_children'][0]['parent_state']);
        $this->assertSame(
            $mismatchedChild->id,
            $report['findings']['placement_mismatches'][0]['child_component_id']
        );
        $this->assertSame(
            ['current_asset_id', 'root_asset_id'],
            $report['findings']['placement_mismatches'][0]['mismatch_fields']
        );
        $this->assertSame(
            $orphanedChild->id,
            $report['findings']['attached_children_without_live_parent'][0]['child_component_id']
        );
        $this->assertSame(
            $overflowState->id,
            $report['findings']['impossible_expected_states'][0]['expected_state_id']
        );
        $this->assertSame(
            $expectedParent->id,
            $report['findings']['impossible_expected_states'][0]['parent_component_id']
        );
        $this->assertSame(1, $report['findings']['impossible_expected_states'][0]['excess_qty']);

        $textExitCode = Artisan::call('components:audit-integrity');
        $textOutput = Artisan::output();
        $this->assertSame(1, $textExitCode);
        $this->assertStringContainsString('Read-only: this command never repairs or writes component data.', $textOutput);
        $this->assertStringContainsString((string) $orphanedChild->id, $textOutput);
        $this->assertStringContainsString((string) $mismatchedChild->id, $textOutput);
        $this->assertStringContainsString((string) $overflowState->id, $textOutput);
        $this->assertStringContainsString(
            'FAIL: component integrity findings require review before migration or release.',
            $textOutput
        );
        $this->assertSame([], $writes);

        $this->assertNotSoftDeleted($orphanedChild);
        $this->assertSame($deletedParent->id, $orphanedChild->fresh()->parent_component_instance_id);
        $this->assertSame($assetB->id, $mismatchedChild->fresh()->current_asset_id);
        $this->assertDatabaseHas('component_expected_subcomponent_states', [
            'id' => $overflowState->id,
            'materialized_qty' => 2,
            'removed_qty' => 1,
        ]);
    }
}
