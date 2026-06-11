@php
    $component = $row->component;
    $template = $row->template;
    $definition = $component?->componentDefinition ?? $template?->componentDefinition;
    $mutedCellClass = $row->isRemoved() ? 'text-muted' : '';
    $depth = (int) ($depth ?? 0);
    $nameCellStyle = $depth > 0 ? 'padding-left: '.(20 + ($depth * 18)).'px;' : null;
    $componentDetailUrl = $component ? route('components.show', $component) : null;
    $canViewComponent = $component && auth()->user()?->can('view', $component);
    $definitionDetailUrl = $definition ? route('settings.component_definitions.edit', $definition) : null;
    $canViewDefinition = $definition && auth()->user()?->can('update', $definition);
    $conditionOptions = $componentConditionOptions ?? \App\Models\ComponentInstance::conditionCodeOptions();
    $rowDisplayName = $row->displayName();
    $conditionBadgeClass = $component?->conditionBadgeClass();
    $conditionBadgeLabel = $component?->conditionBadgeLabel();
@endphp

<tr data-testid="asset-component-row" data-component-classification="{{ $row->classification }}" data-component-depth="{{ $depth }}">
    <td @class([$mutedCellClass])>
        <span class="label {{ $row->isExpected() ? 'label-primary' : ($row->isExtra() ? 'label-warning' : 'label-default') }}">
            {{ $row->label }}
        </span>
        @if($depth > 0)
            <div class="text-muted small">{{ __('Child component') }}</div>
        @endif
    </td>
    <td @class([$mutedCellClass]) @if($nameCellStyle) style="{{ $nameCellStyle }}" @endif>
        @if($component && $canViewComponent)
            <a href="{{ $componentDetailUrl }}">{{ $rowDisplayName }}</a>
        @elseif(!$component && $definition && $canViewDefinition)
            <a href="{{ $definitionDetailUrl }}">{{ $rowDisplayName }}</a>
        @else
            {{ $rowDisplayName }}
        @endif
        @if($conditionBadgeClass && $conditionBadgeLabel)
            <span class="label {{ $conditionBadgeClass }}">{{ $conditionBadgeLabel }}</span>
        @endif
        @if($row->isRemoved())
            <div class="small">{{ __('Removed from this asset') }}</div>
        @elseif($row->tracked)
            <div class="text-muted small">{{ __('Tracked') }}</div>
        @endif
        @if($component && !$row->isRemoved())
            @include('components.partials.asset-component-condition-control', [
                'component' => $component,
                'asset' => $asset,
                'conditionOptions' => $conditionOptions,
                'context' => 'table',
                'variant' => 'table',
            ])
        @endif
    </td>
    <td @class([$mutedCellClass])>
        @if($component)
            @if($canViewComponent)
                <a href="{{ $componentDetailUrl }}">{{ $component->component_tag }}</a>
            @else
                {{ $component->component_tag }}
            @endif
        @else
            <span class="text-muted">{{ $row->quantity > 1 ? __('Assumed x:count', ['count' => $row->quantity]) : __('Assumed') }}</span>
        @endif
    </td>
    <td @class([$mutedCellClass])>{{ $component?->serial ?: trans('general.none') }}</td>
    <td @class([$mutedCellClass])>{{ $definition?->category?->name ?: trans('general.none') }}</td>
    <td @class([$mutedCellClass])>{{ $definition?->manufacturer?->name ?: trans('general.none') }}</td>
    <td class="text-nowrap">
        @include('components.partials.asset-component-actions', [
            'row' => $row,
            'asset' => $asset,
            'component' => $component,
            'template' => $template,
            'rowDisplayName' => $rowDisplayName,
            'variant' => 'table',
        ])
    </td>
</tr>
