@php
    $definition = $child->componentDefinition;
    $depth = (int) ($depth ?? 1);
    $nameCellStyle = 'padding-left: '.(20 + ($depth * 18)).'px;';
    $conditionBadgeClass = $child->conditionBadgeClass();
    $conditionBadgeLabel = $child->conditionBadgeLabel();
@endphp

<tr data-testid="asset-component-removed-child-row" data-component-depth="{{ $depth }}">
    <td class="text-muted">
        <span class="label label-default">{{ __('Removed Child') }}</span>
        <div class="text-muted small">{{ __('Expected child') }}</div>
    </td>
    <td class="text-muted" style="{{ $nameCellStyle }}">
        @can('view', $child)
            <a href="{{ route('components.show', $child) }}">{{ $child->display_name }}</a>
        @else
            {{ $child->display_name }}
        @endcan
        @if($conditionBadgeClass && $conditionBadgeLabel)
            <span class="label {{ $conditionBadgeClass }}">{{ $conditionBadgeLabel }}</span>
        @endif
        @if($definition)
            <div class="text-muted small">{{ $definition->name }}</div>
        @endif
        <div class="small">{{ __('Removed from this parent component') }}</div>
    </td>
    <td class="text-muted">
        @can('view', $child)
            <a href="{{ route('components.show', $child) }}">{{ $child->component_tag }}</a>
        @else
            {{ $child->component_tag }}
        @endcan
    </td>
    <td class="text-muted">{{ $child->serial ?: trans('general.none') }}</td>
    <td class="text-muted">{{ $definition?->category?->name ?: trans('general.none') }}</td>
    <td class="text-muted">{{ $definition?->manufacturer?->name ?: trans('general.none') }}</td>
    <td class="text-nowrap">
        <a href="{{ route('components.show', $child) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
    </td>
</tr>
