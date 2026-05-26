<?php

namespace App\Services\ModelAttributes;

use App\Models\AttributeDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentInstanceAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComponentInstanceAttributeManager
{
    public function __construct(private readonly AttributeValueService $valueService)
    {
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    public function sync(ComponentInstance $component, array $payload): void
    {
        $rows = $this->normalizePayload($payload);

        $definitionIds = $rows
            ->pluck('attribute_definition_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->values();
        $definitionKeys = $rows
            ->pluck('attribute_key')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->values();

        $definitions = $definitionIds->isEmpty() && $definitionKeys->isEmpty()
            ? collect()
            : AttributeDefinition::query()
                ->current()
                ->with('options')
                ->where(function ($query) use ($definitionIds, $definitionKeys): void {
                    if ($definitionIds->isNotEmpty()) {
                        $query->whereIn('id', $definitionIds->all());
                    }

                    if ($definitionKeys->isNotEmpty()) {
                        $method = $definitionIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('key', $definitionKeys->all());
                    }
                })
                ->get();

        $definitionsById = $definitions->keyBy('id');
        $definitionsByKey = $definitions->keyBy('key');

        $component->loadMissing('componentDefinition.attributeContributions');
        $definitionContributions = $component->componentDefinition?->attributeContributions
            ?->keyBy('attribute_definition_id')
            ?? collect();

        DB::transaction(function () use ($component, $rows, $definitionsById, $definitionsByKey, $definitionContributions): void {
            $retainedIds = [];

            foreach ($rows as $index => $row) {
                $definitionId = isset($row['attribute_definition_id']) && $row['attribute_definition_id'] !== ''
                    ? (int) $row['attribute_definition_id']
                    : null;
                $definitionKey = trim((string) ($row['attribute_key'] ?? ''));
                $attributeSearch = trim((string) ($row['attribute_search'] ?? ''));
                $value = $row['value'] ?? null;
                $hasResolveFlag = array_key_exists('resolves_to_spec', $row);
                $resolvesToSpec = $hasResolveFlag
                    ? filter_var($row['resolves_to_spec'], FILTER_VALIDATE_BOOL)
                    : false;

                if (!$definitionId && $definitionKey === '') {
                    if ($attributeSearch !== '' || $this->hasValue($value) || $resolvesToSpec) {
                        throw ValidationException::withMessages([
                            'instance_attributes.' . $index . '.attribute_definition_id' => [__('Select a valid attribute.')],
                        ]);
                    }

                    continue;
                }

                /** @var AttributeDefinition|null $definition */
                $definition = $definitionId
                    ? $definitionsById->get($definitionId)
                    : $definitionsByKey->get($definitionKey);

                if (!$definition) {
                    throw ValidationException::withMessages([
                        'instance_attributes.' . $index . '.attribute_definition_id' => [__('Select a valid attribute.')],
                    ]);
                }

                if (in_array((int) $definition->id, $retainedIds, true)) {
                    throw ValidationException::withMessages([
                        'instance_attributes' => [__('Each attribute can only be set once per component instance.')],
                    ]);
                }

                if (!$this->hasValue($value)) {
                    throw ValidationException::withMessages([
                        'instance_attributes.' . $index . '.value' => [__('Enter a value for :label.', [
                            'label' => $definition->label,
                        ])],
                    ]);
                }

                if (!$hasResolveFlag) {
                    $resolvesToSpec = (bool) ($definitionContributions->get($definition->id)?->resolves_to_spec ?? false);
                }

                if ($resolvesToSpec && !$definition->isNumericDatatype()) {
                    throw ValidationException::withMessages([
                        'instance_attributes.' . $index . '.resolves_to_spec' => [__('Only numeric attributes can replace calculated specification values right now.')],
                    ]);
                }

                try {
                    $normalized = $this->valueService->validateAndNormalize($definition, $value, 'instance_attributes');
                } catch (ValidationException $exception) {
                    $messages = collect($exception->errors())
                        ->flatMap(fn ($rowMessages) => $rowMessages)
                        ->filter()
                        ->values()
                        ->all();

                    throw ValidationException::withMessages([
                        'instance_attributes.' . $index . '.value' => $messages !== [] ? $messages : [__('Enter a valid value for :label.', [
                            'label' => $definition->label,
                        ])],
                    ]);
                }

                ComponentInstanceAttribute::query()->updateOrCreate(
                    [
                        'component_instance_id' => $component->id,
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

                $retainedIds[] = (int) $definition->id;
            }

            $query = ComponentInstanceAttribute::query()
                ->where('component_instance_id', $component->id);

            if ($retainedIds !== []) {
                $query->whereNotIn('attribute_definition_id', $retainedIds);
            }

            $query->delete();
        });
    }

    /**
     * @param array<int|string, mixed> $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizePayload(array $payload): Collection
    {
        $rows = collect($payload);

        if ($rows->every(fn ($row) => is_array($row))) {
            return $rows
                ->map(fn ($row) => is_array($row) ? $row : [])
                ->values();
        }

        return $rows
            ->map(function ($value, $key): array {
                $row = [
                    'value' => $value,
                ];

                if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                    $row['attribute_definition_id'] = (int) $key;
                } else {
                    $row['attribute_key'] = (string) $key;
                }

                return $row;
            })
            ->values();
    }

    private function hasValue($value): bool
    {
        return $value !== null && $value !== '';
    }
}
