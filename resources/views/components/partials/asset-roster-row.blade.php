@php
    $component = $row->component;
    $template = $row->template;
    $definition = $component?->componentDefinition ?? $template?->componentDefinition;
    $mutedCellClass = $row->isRemoved() ? 'text-muted' : '';
    $componentDetailUrl = $component ? route('components.show', $component) : null;
    $canViewComponent = $component && auth()->user()?->can('view', $component);
    $definitionDetailUrl = $definition ? route('settings.component_definitions.edit', $definition) : null;
    $canViewDefinition = $definition && auth()->user()?->can('update', $definition);
@endphp

<tr data-testid="asset-component-row" data-component-classification="{{ $row->classification }}">
    <td @class([$mutedCellClass])>
        <span class="label {{ $row->isExpected() ? 'label-primary' : ($row->isExtra() ? 'label-warning' : 'label-default') }}">
            {{ $row->label }}
        </span>
    </td>
    <td @class([$mutedCellClass])>
        @if($component && $canViewComponent)
            <a href="{{ $componentDetailUrl }}">{{ $row->name }}</a>
        @elseif(!$component && $definition && $canViewDefinition)
            <a href="{{ $definitionDetailUrl }}">{{ $row->name }}</a>
        @else
            {{ $row->name }}
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
            <span class="text-muted">{{ __('Assumed') }}</span>
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
                    data-storage-name="{{ $template->expected_name ?: ($template->componentDefinition?->name ?? __('Expected component')) }}"
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
