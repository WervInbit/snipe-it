@php
    $parentComponent = $parentRow->component;
    $parentDefinition = $parentComponent?->componentDefinition ?? $parentRow->template?->componentDefinition;
    $attachedChildRows = $parentComponent
        ? ($childRowsByParentId->get((int) $parentComponent->id, collect()) ?? collect())
        : collect();
    $removedExpectedChildren = $parentComponent
        ? ($removedExpectedSubcomponentsByParent->get((int) $parentComponent->id, collect()) ?? collect())
        : collect();
    $expectedSubcomponents = $parentDefinition?->subcomponentTemplates ?? collect();
    $expectedSubcomponentStates = $parentComponent
        ? (($parentComponent->expectedSubcomponentStates ?? collect())->keyBy('component_definition_subcomponent_template_id'))
        : collect();
@endphp

@foreach($attachedChildRows as $childRow)
    @include('components.partials.asset-roster-card', ['row' => $childRow, 'asset' => $asset, 'depth' => 1])
@endforeach

@foreach($removedExpectedChildren as $child)
    @include('components.partials.asset-subcomponent-removed-card', ['child' => $child, 'asset' => $asset, 'depth' => 1])
@endforeach

@foreach($expectedSubcomponents as $template)
    @php
        $state = $expectedSubcomponentStates->get($template->id);
        $expectedQty = max(1, (int) $template->expected_qty);
        $materializedQty = min($expectedQty, max(0, (int) ($state?->materialized_qty ?? 0)));
        $removedQty = min($expectedQty - $materializedQty, max(0, (int) ($state?->removed_qty ?? 0)));
        $remainingQty = max(0, $expectedQty - $materializedQty - $removedQty);
    @endphp
    @include('components.partials.asset-subcomponent-expected-card', [
        'parentComponent' => $parentComponent,
        'template' => $template,
        'expectedQty' => $expectedQty,
        'materializedQty' => $materializedQty,
        'removedQty' => $removedQty,
        'remainingQty' => $remainingQty,
        'depth' => 1,
    ])
@endforeach
