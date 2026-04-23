<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\ComponentDefinition;
use App\Models\ComponentDefinitionAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComponentDefinitionAttributeManager
{
    public function __construct(private readonly AttributeValueService $valueService)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     */
    public function sync(ComponentDefinition $componentDefinition, array $payload): void
    {
        $rows = collect($payload)
            ->map(fn ($row) => is_array($row) ? $row : [])
            ->values();

        $definitionIds = $rows
            ->pluck('attribute_definition_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->values();

        if ($definitionIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'attribute_contributions' => [__('Each attribute can only be contributed once per component definition.')],
            ]);
        }

        $definitions = AttributeDefinition::query()
            ->current()
            ->with('options')
            ->whereIn('id', $definitionIds->all())
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($componentDefinition, $rows, $definitions): void {
            $retainedIds = [];

            foreach ($rows as $index => $row) {
                $definitionId = isset($row['attribute_definition_id']) && $row['attribute_definition_id'] !== ''
                    ? (int) $row['attribute_definition_id']
                    : null;
                $attributeSearch = trim((string) ($row['attribute_search'] ?? ''));
                $value = $row['value'] ?? null;
                $resolvesToSpec = filter_var($row['resolves_to_spec'] ?? false, FILTER_VALIDATE_BOOL);

                if (!$definitionId) {
                    if ($attributeSearch !== '' || ($value !== null && $value !== '') || $resolvesToSpec) {
                        throw ValidationException::withMessages([
                            'attribute_contributions.' . $index . '.attribute_definition_id' => [__('Select a valid attribute.')],
                        ]);
                    }

                    continue;
                }

                /** @var AttributeDefinition|null $definition */
                $definition = $definitions->get($definitionId);

                if (!$definition) {
                    throw ValidationException::withMessages([
                        'attribute_contributions.' . $index . '.attribute_definition_id' => [__('Select a valid attribute.')],
                    ]);
                }

                if ($value === null || $value === '') {
                    throw ValidationException::withMessages([
                        'attribute_contributions.' . $index . '.value' => [__('Enter a value for :label.', [
                            'label' => $definition->label,
                        ])],
                    ]);
                }

                if ($resolvesToSpec && !$definition->isNumericDatatype()) {
                    throw ValidationException::withMessages([
                        'attribute_contributions.' . $index . '.resolves_to_spec' => [__('Only numeric attributes can replace calculated specification values right now.')],
                    ]);
                }

                try {
                    $normalized = $this->valueService->validateAndNormalize($definition, $value, 'attribute_contributions');
                } catch (ValidationException $exception) {
                    $messages = collect($exception->errors())
                        ->flatMap(fn ($rowMessages) => $rowMessages)
                        ->filter()
                        ->values()
                        ->all();

                    throw ValidationException::withMessages([
                        'attribute_contributions.' . $index . '.value' => $messages !== [] ? $messages : [__('Enter a valid value for :label.', [
                            'label' => $definition->label,
                        ])],
                    ]);
                }

                ComponentDefinitionAttribute::query()->updateOrCreate(
                    [
                        'component_definition_id' => $componentDefinition->id,
                        'attribute_definition_id' => $definition->id,
                    ],
                    [
                        'value' => $normalized->value,
                        'raw_value' => $normalized->rawValue,
                        'attribute_option_id' => $normalized->attributeOptionId,
                        'resolves_to_spec' => $definition->isNumericDatatype() ? $resolvesToSpec : false,
                        'sort_order' => $index,
                    ]
                );

                $retainedIds[] = $definition->id;
            }

            $query = ComponentDefinitionAttribute::query()
                ->where('component_definition_id', $componentDefinition->id);

            if ($retainedIds !== []) {
                $query->whereNotIn('attribute_definition_id', $retainedIds);
            }

            $query->delete();
        });
    }
}
