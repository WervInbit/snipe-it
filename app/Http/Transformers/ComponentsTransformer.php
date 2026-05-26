<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\ComponentInstance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class ComponentsTransformer
{
    public function transformComponents(Collection $components, $total)
    {
        $array = [];
        foreach ($components as $component) {
            $array[] = $this->transformComponent($component);
        }

        return (new DatatablesTransformer())->transformDatatables($array, $total);
    }

    public function transformComponent(ComponentInstance $component): array
    {
        $currentLocation = $component->storageLocation
            ? $component->storageLocation->name
            : ($component->currentAsset ? $component->currentAsset->present()->name() : null);

        return [
            'id' => (int) $component->id,
            'component_tag' => e($component->component_tag),
            'name' => e($component->display_name),
            'display_name' => e($component->display_name),
            'serial' => $component->serial ? e($component->serial) : null,
            'status' => e($component->status),
            'status_label' => e(ComponentInstance::statusLabel($component->status) ?? $component->status),
            'lifecycle_status' => e($component->effectiveLifecycleStatus()),
            'lifecycle_status_label' => e(ComponentInstance::lifecycleStatusLabel($component->effectiveLifecycleStatus()) ?? $component->effectiveLifecycleStatus()),
            'condition_code' => e($component->condition_code),
            'condition_status' => e($component->effectiveConditionStatus()),
            'condition_status_label' => e(ComponentInstance::conditionStatusLabel($component->effectiveConditionStatus()) ?? $component->effectiveConditionStatus()),
            'installed_as' => $component->installed_as ? e($component->installed_as) : null,
            'definition' => $component->componentDefinition ? [
                'id' => (int) $component->componentDefinition->id,
                'name' => e($component->componentDefinition->name),
            ] : null,
            'category' => $component->componentDefinition?->category ? [
                'id' => (int) $component->componentDefinition->category->id,
                'name' => e($component->componentDefinition->category->name),
            ] : null,
            'manufacturer' => $component->componentDefinition?->manufacturer ? [
                'id' => (int) $component->componentDefinition->manufacturer->id,
                'name' => e($component->componentDefinition->manufacturer->name),
            ] : null,
            'source_type' => e(ComponentInstance::sourceTypeLabel($component->source_type) ?? $component->source_type),
            'source_asset' => $component->sourceAsset ? [
                'id' => (int) $component->sourceAsset->id,
                'name' => e($component->sourceAsset->present()->name()),
            ] : null,
            'current_asset' => $component->currentAsset ? [
                'id' => (int) $component->currentAsset->id,
                'name' => e($component->currentAsset->present()->name()),
            ] : null,
            'storage_location' => $component->storageLocation ? [
                'id' => (int) $component->storageLocation->id,
                'name' => e($component->storageLocation->name),
            ] : null,
            'current_location' => $currentLocation ? e($currentLocation) : null,
            'held_by' => $component->heldBy ? [
                'id' => (int) $component->heldBy->id,
                'name' => e($component->heldBy->present()->fullName()),
            ] : null,
            'company' => $component->company ? [
                'id' => (int) $component->company->id,
                'name' => e($component->company->name),
            ] : null,
            'supplier' => $component->supplier ? [
                'id' => (int) $component->supplier->id,
                'name' => e($component->supplier->name),
            ] : null,
            'purchase_cost' => Helper::formatCurrencyOutput($component->purchase_cost),
            'received_at' => Helper::getFormattedDateObject($component->received_at, 'datetime'),
            'notes' => $component->notes ? Helper::parseEscapedMarkedownInline($component->notes) : null,
            'instance_attributes' => $component->relationLoaded('instanceAttributes')
                ? $component->instanceAttributes->map(fn ($attribute) => [
                    'id' => (int) $attribute->id,
                    'attribute_definition_id' => (int) $attribute->attribute_definition_id,
                    'key' => $attribute->definition ? e($attribute->definition->key) : null,
                    'label' => $attribute->definition ? e($attribute->definition->label) : null,
                    'datatype' => $attribute->definition ? e($attribute->definition->datatype) : null,
                    'unit' => $attribute->definition?->unit ? e($attribute->definition->unit) : null,
                    'value' => $attribute->value !== null ? e($attribute->value) : null,
                    'raw_value' => $attribute->raw_value !== null ? e($attribute->raw_value) : null,
                    'option' => $attribute->option ? [
                        'id' => (int) $attribute->option->id,
                        'value' => e($attribute->option->value),
                        'label' => e($attribute->option->label),
                    ] : null,
                    'resolves_to_spec' => (bool) $attribute->resolves_to_spec,
                    'sort_order' => (int) $attribute->sort_order,
                ])->values()->all()
                : [],
            'created_by' => $component->createdBy ? [
                'id' => (int) $component->createdBy->id,
                'name' => e($component->createdBy->present()->fullName()),
            ] : null,
            'created_at' => Helper::getFormattedDateObject($component->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($component->updated_at, 'datetime'),
            'qr_uid' => $component->qr_uid,
            'uploads_count' => $component->uploads?->count() ?? 0,
            'events' => $component->relationLoaded('events')
                ? $component->events->map(fn ($event) => [
                    'id' => (int) $event->id,
                    'event_type' => $event->event_type,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'from_asset' => $event->fromAsset ? [
                        'id' => (int) $event->fromAsset->id,
                        'name' => e($event->fromAsset->present()->name()),
                    ] : null,
                    'to_asset' => $event->toAsset ? [
                        'id' => (int) $event->toAsset->id,
                        'name' => e($event->toAsset->present()->name()),
                    ] : null,
                    'from_storage_location' => $event->fromStorageLocation ? [
                        'id' => (int) $event->fromStorageLocation->id,
                        'name' => e($event->fromStorageLocation->name),
                    ] : null,
                    'to_storage_location' => $event->toStorageLocation ? [
                        'id' => (int) $event->toStorageLocation->id,
                        'name' => e($event->toStorageLocation->name),
                    ] : null,
                    'held_by' => $event->heldBy ? [
                        'id' => (int) $event->heldBy->id,
                        'name' => e($event->heldBy->present()->fullName()),
                    ] : null,
                    'performed_by' => $event->performedBy ? [
                        'id' => (int) $event->performedBy->id,
                        'name' => e($event->performedBy->present()->fullName()),
                    ] : null,
                    'note' => $event->note,
                    'created_at' => Helper::getFormattedDateObject($event->created_at, 'datetime'),
                ])->values()->all()
                : [],
            'available_actions' => [
                'update' => Gate::allows('update', $component),
                'delete' => Gate::allows('delete', $component),
                'extract' => Gate::allows('extract', $component),
                'install' => Gate::allows('install', $component),
                'move' => Gate::allows('move', $component),
                'verify' => Gate::allows('verify', $component),
            ],
        ];
    }
}
