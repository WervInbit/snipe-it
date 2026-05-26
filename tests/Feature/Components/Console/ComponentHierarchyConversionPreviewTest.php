<?php

namespace Tests\Feature\Components\Console;

use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use App\Models\ModelNumber;
use App\Models\ModelNumberComponentTemplate;
use App\Services\Components\ComponentHierarchyConversionPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ComponentHierarchyConversionPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function testPreviewSuggestsSubcomponentTemplatesFromFlatModelNumberEvidence(): void
    {
        [$parentDefinition, $childDefinition] = $this->createFlatParentChildEvidence();

        $report = app(ComponentHierarchyConversionPreviewService::class)->buildReport();

        $this->assertTrue($report['summary']['read_only']);
        $this->assertSame(1, $report['summary']['candidate_parent_definitions']);
        $this->assertSame(1, $report['summary']['candidate_child_definitions']);
        $this->assertSame(1, $report['summary']['suggested_subcomponent_templates']);
        $this->assertSame(1, $report['summary']['suggestions_with_overlap_warnings']);

        $suggestion = $report['suggested_templates'][0];
        $this->assertSame($parentDefinition->id, $suggestion['parent_definition_id']);
        $this->assertSame($childDefinition->id, $suggestion['child_definition_id']);
        $this->assertSame('Left USB-C Port Board', $suggestion['suggested_expected_name']);
        $this->assertSame(2, $suggestion['suggested_expected_qty']);
        $this->assertSame('high_review_required', $suggestion['confidence']);
        $this->assertSame('USB Port Count', $suggestion['overlap_warnings'][0]['attribute_label']);

        $this->assertDatabaseCount('component_definition_subcomponent_templates', 0);
    }

    public function testPreviewCommandEmitsJsonAndDoesNotCreateTemplates(): void
    {
        $this->createFlatParentChildEvidence();

        Artisan::call('component-hierarchy:preview-conversion', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertTrue($report['summary']['read_only']);
        $this->assertSame(1, $report['summary']['suggested_subcomponent_templates']);
        $this->assertSame('Motherboard Assembly', $report['suggested_templates'][0]['parent_name']);
        $this->assertSame('USB-C Port Board', $report['suggested_templates'][0]['child_name']);

        $this->assertDatabaseCount('component_definition_subcomponent_templates', 0);
    }

    public function testApplyCommandDefaultsToDryRunForSelectedPairs(): void
    {
        [$parentDefinition, $childDefinition] = $this->createFlatParentChildEvidence();

        $exitCode = Artisan::call('component-hierarchy:apply-conversion', [
            '--pair' => [$parentDefinition->id.':'.$childDefinition->id],
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Dry-run only: no database writes were performed.', Artisan::output());
        $this->assertDatabaseCount('component_definition_subcomponent_templates', 0);
    }

    public function testApplyCommandCreatesOnlySelectedTemplatesWithRollbackGuidance(): void
    {
        [$selectedParentDefinition, $selectedChildDefinition] = $this->createFlatParentChildEvidence();
        $this->createFlatParentChildEvidence();

        $exitCode = Artisan::call('component-hierarchy:apply-conversion', [
            '--pair' => [$selectedParentDefinition->id.':'.$selectedChildDefinition->id],
            '--apply' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Rollback tinker example:', Artisan::output());
        $this->assertDatabaseCount('component_definition_subcomponent_templates', 1);

        $template = ComponentDefinitionSubcomponentTemplate::firstOrFail();
        $this->assertSame($selectedParentDefinition->id, $template->parent_component_definition_id);
        $this->assertSame($selectedChildDefinition->id, $template->child_component_definition_id);
        $this->assertSame('Left USB-C Port Board', $template->expected_name);
        $this->assertSame(2, $template->expected_qty);
        $this->assertSame('component-hierarchy:apply-conversion', $template->metadata_json['created_by_command']);
    }

    public function testApplyCommandRejectsPairsThatAreNotCurrentPreviewSuggestions(): void
    {
        [$parentDefinition, $childDefinition] = $this->createFlatParentChildEvidence();

        ComponentDefinitionSubcomponentTemplate::create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $exitCode = Artisan::call('component-hierarchy:apply-conversion', [
            '--pair' => [$parentDefinition->id.':'.$childDefinition->id],
            '--apply' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('No current preview suggestion for this pair.', Artisan::output());
        $this->assertDatabaseCount('component_definition_subcomponent_templates', 1);
    }

    public function testPreviewReportsExistingOverlapWarningsWithoutSuggestingDuplicateTemplate(): void
    {
        [$parentDefinition, $childDefinition] = $this->createFlatParentChildEvidence();

        ComponentDefinitionSubcomponentTemplate::create([
            'parent_component_definition_id' => $parentDefinition->id,
            'child_component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $report = app(ComponentHierarchyConversionPreviewService::class)->buildReport();

        $this->assertSame(0, $report['summary']['suggested_subcomponent_templates']);
        $this->assertCount(1, $report['existing_overlap_warnings']);
        $this->assertSame('USB Port Count', $report['existing_overlap_warnings'][0]['attribute_label']);
    }

    /**
     * @return array{0: ComponentDefinition, 1: ComponentDefinition}
     */
    private function createFlatParentChildEvidence(): array
    {
        $usbPorts = AttributeDefinition::create([
            'key' => 'usb_port_count_'.uniqid(),
            'label' => 'USB Port Count',
            'datatype' => AttributeDefinition::DATATYPE_INT,
            'required_for_category' => false,
            'allow_custom_values' => true,
            'allow_asset_override' => true,
        ]);

        $parentDefinition = ComponentDefinition::factory()->create([
            'name' => 'Motherboard Assembly',
            'part_code' => 'MB-ASM',
            'placement_mode' => ComponentDefinition::PLACEMENT_ASSET_ONLY,
        ]);

        $childDefinition = ComponentDefinition::factory()->create([
            'name' => 'USB-C Port Board',
            'part_code' => 'USB-C-PORT',
            'placement_mode' => ComponentDefinition::PLACEMENT_SUBCOMPONENT_ONLY,
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $parentDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '4',
            'raw_value' => '4',
            'resolves_to_spec' => true,
        ]);

        ComponentDefinitionAttribute::create([
            'component_definition_id' => $childDefinition->id,
            'attribute_definition_id' => $usbPorts->id,
            'value' => '1',
            'raw_value' => '1',
            'resolves_to_spec' => true,
        ]);

        $modelNumber = ModelNumber::factory()->create([
            'code' => 'HP-450-G8',
            'label' => 'HP ProBook 450 G8',
        ]);

        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $parentDefinition->id,
            'expected_name' => 'Motherboard Assembly',
            'expected_qty' => 1,
            'sort_order' => 0,
        ]);

        ModelNumberComponentTemplate::factory()->create([
            'model_number_id' => $modelNumber->id,
            'component_definition_id' => $childDefinition->id,
            'expected_name' => 'Left USB-C Port Board',
            'expected_qty' => 2,
            'sort_order' => 1,
        ]);

        return [$parentDefinition, $childDefinition];
    }
}
