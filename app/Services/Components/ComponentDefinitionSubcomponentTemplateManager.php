<?php

namespace App\Services\Components;

use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionSubcomponentTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComponentDefinitionSubcomponentTemplateManager
{
    /**
     * @param array<int, array<string, mixed>> $payload
     */
    public function sync(ComponentDefinition $componentDefinition, array $payload): void
    {
        if (!$componentDefinition->canBeInstalledOnAsset()) {
            throw ValidationException::withMessages([
                'placement_mode' => [__('Subcomponent-only definitions cannot define their own expected subcomponents.')],
            ]);
        }

        $rows = collect($payload)
            ->map(fn ($row) => is_array($row) ? $row : [])
            ->values();

        $childDefinitionIds = $rows
            ->pluck('child_component_definition_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $childDefinitions = ComponentDefinition::query()
            ->whereIn('id', $childDefinitionIds->all())
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($componentDefinition, $rows, $childDefinitions): void {
            $existingTemplates = $componentDefinition->subcomponentTemplates()->get()->keyBy('id');
            $retainedIds = [];

            foreach ($rows as $index => $row) {
                $templateId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
                $childDefinitionId = isset($row['child_component_definition_id']) && $row['child_component_definition_id'] !== ''
                    ? (int) $row['child_component_definition_id']
                    : null;
                $expectedName = trim((string) ($row['expected_name'] ?? ''));
                $notes = trim((string) ($row['notes'] ?? ''));
                $hasMeaningfulData = $childDefinitionId !== null || $expectedName !== '' || $notes !== '';

                if (!$hasMeaningfulData) {
                    continue;
                }

                if ($childDefinitionId === (int) $componentDefinition->id) {
                    throw ValidationException::withMessages([
                        'expected_subcomponents.' . $index . '.child_component_definition_id' => [__('A component definition cannot expect itself as a subcomponent.')],
                    ]);
                }

                $childDefinition = $childDefinitionId ? $childDefinitions->get($childDefinitionId) : null;

                if ($childDefinitionId && !$childDefinition) {
                    throw ValidationException::withMessages([
                        'expected_subcomponents.' . $index . '.child_component_definition_id' => [__('Select a valid component definition.')],
                    ]);
                }

                if ($childDefinition && !$childDefinition->canBeUsedAsSubcomponent()) {
                    throw ValidationException::withMessages([
                        'expected_subcomponents.' . $index . '.child_component_definition_id' => [__('This component definition is restricted to direct asset placement and cannot be used as a subcomponent.')],
                    ]);
                }

                if ($expectedName === '' && !$childDefinition) {
                    throw ValidationException::withMessages([
                        'expected_subcomponents.' . $index . '.expected_name' => [__('Enter an expected subcomponent name or select a component definition.')],
                    ]);
                }

                $template = $templateId ? $existingTemplates->get($templateId) : null;
                if ($templateId && !$template) {
                    throw ValidationException::withMessages([
                        'expected_subcomponents.' . $index . '.id' => [__('Select a valid expected subcomponent row.')],
                    ]);
                }

                if (!$template) {
                    $template = new ComponentDefinitionSubcomponentTemplate();
                    $template->parent_component_definition_id = $componentDefinition->id;
                }

                $template->fill([
                    'child_component_definition_id' => $childDefinition?->id,
                    'expected_name' => $expectedName !== '' ? $expectedName : $childDefinition->name,
                    'expected_qty' => max(1, (int) ($row['expected_qty'] ?? 1)),
                    'is_required' => filter_var($row['is_required'] ?? true, FILTER_VALIDATE_BOOL),
                    'sort_order' => $index,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
                $template->save();

                $retainedIds[] = $template->id;
            }

            $deleteQuery = ComponentDefinitionSubcomponentTemplate::query()
                ->where('parent_component_definition_id', $componentDefinition->id);

            if ($retainedIds !== []) {
                $deleteQuery->whereNotIn('id', $retainedIds);
            }

            $deleteQuery->delete();
        });
    }
}
