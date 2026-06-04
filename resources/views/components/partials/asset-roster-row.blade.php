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
    $conditionStatus = $component?->effectiveConditionStatus();
    $rowDisplayName = $row->displayName();
    $issueLabelClass = match ($conditionStatus) {
        \App\Models\ComponentInstance::CONDITION_STATUS_DAMAGED => 'label-danger',
        \App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION => 'label-warning',
        default => null,
    };
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
        @if($issueLabelClass)
            <span class="label {{ $issueLabelClass }}">{{ \App\Models\ComponentInstance::conditionStatusLabel($conditionStatus) }}</span>
        @endif
        @if($row->isRemoved())
            <div class="small">{{ __('Removed from this asset') }}</div>
        @elseif($row->tracked)
            <div class="text-muted small">{{ __('Tracked') }}</div>
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
        @if($component)
            @unless($row->isRemoved())
                @can('move', $component)
                    <form method="POST" action="{{ route('components.remove_to_tray', $component) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-warning">{{ __('To Tray') }}</button>
                    </form>
                @endcan
                @can('move', $component)
                    <button
                        type="button"
                        class="btn btn-xs btn-default"
                        data-toggle="modal"
                        data-target="#assetComponentStorageModal"
                        data-storage-action="{{ route('hardware.components.storage.store', [$asset, $component]) }}"
                        data-storage-name="{{ $component->display_name }}"
                    >
                        {{ __('To Storage') }}
                    </button>
                @endcan
                @can('move', $component)
                    <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}" class="btn btn-xs btn-default">{{ __('Move Within Device') }}</a>
                @endcan
                @can('install', $component)
                    <a href="{{ route('hardware.components.transfer.create', [$asset, $component]) }}" class="btn btn-xs btn-primary">{{ __('Move To Other Device') }}</a>
                @endcan
            @endunless
            <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
        @elseif($template)
            @can('move', new \App\Models\ComponentInstance())
                <form method="POST" action="{{ route('hardware.components.expected.tray', [$asset, $template]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-warning">{{ __('To Tray') }}</button>
                </form>
            @endcan
            @can('move', new \App\Models\ComponentInstance())
                <button
                    type="button"
                    class="btn btn-xs btn-default"
                    data-toggle="modal"
                    data-target="#assetComponentStorageModal"
                    data-storage-action="{{ route('hardware.components.expected.storage.store', [$asset, $template]) }}"
                    data-storage-name="{{ $rowDisplayName }}"
                >
                    {{ __('To Storage') }}
                </button>
            @endcan
            @can('install', new \App\Models\ComponentInstance())
                <a href="{{ route('hardware.components.expected.transfer.create', [$asset, $template]) }}" class="btn btn-xs btn-primary">{{ __('Move To Other Device') }}</a>
            @endcan
        @endif
    </td>
</tr>
