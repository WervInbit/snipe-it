@php
    use App\Models\ComponentInstance;

    $row = $row ?? null;
    $variant = $variant ?? 'table';
    $component = $component ?? ($row->component ?? null);
    $template = $template ?? ($row->template ?? null);
    $rowDisplayName = $rowDisplayName ?? ($row?->displayName() ?? $component?->display_name ?? $template?->expected_name ?? __('Component'));
    $isRemoved = $isRemoved ?? ($row?->isRemoved() ?? false);
    $blankComponent = new ComponentInstance();
    $user = auth()->user();
    $canMoveComponent = $component && $user?->can('move', $component);
    $canInstallComponent = $component && $user?->can('install', $component);
    $canMoveTemplate = !$component && $template && $user?->can('move', $blankComponent);
    $canInstallTemplate = !$component && $template && $user?->can('install', $blankComponent);
    $hasDefaultAction = !$isRemoved && (($component && $canMoveComponent) || (!$component && $template && $canMoveTemplate));
    $hasStorageAction = !$isRemoved && (($component && $canMoveComponent) || (!$component && $template && $canMoveTemplate));
    $hasReparentAction = !$isRemoved && $component && $canMoveComponent;
    $hasTransferAction = !$isRemoved && (($component && $canInstallComponent) || (!$component && $template && $canInstallTemplate));
    $hasOpenAction = (bool) $component;
    $hasSecondaryActions = $hasStorageAction || $hasReparentAction || $hasTransferAction || $hasOpenAction;
    $storageAction = $component
        ? route('hardware.components.storage.store', [$asset, $component])
        : ($template ? route('hardware.components.expected.storage.store', [$asset, $template]) : null);
    $transferAction = $component
        ? route('hardware.components.transfer.create', [$asset, $component])
        : ($template ? route('hardware.components.expected.transfer.create', [$asset, $template]) : null);
@endphp

@if($variant === 'mobile')
    @if($hasDefaultAction || $hasSecondaryActions)
        <div @class([
                'asset-component-card__actions',
                'asset-component-card__actions--single' => !$hasDefaultAction || !$hasSecondaryActions,
            ])
            data-testid="asset-component-card-actions">
            @if($hasDefaultAction)
                @if($component)
                    <form method="POST"
                          action="{{ route('components.remove_to_tray', $component) }}"
                          class="asset-component-card__default-action"
                          data-testid="asset-component-card-default-action">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm btn-block">{{ __('To Tray') }}</button>
                    </form>
                @elseif($template)
                    <form method="POST"
                          action="{{ route('hardware.components.expected.tray', [$asset, $template]) }}"
                          class="asset-component-card__default-action"
                          data-testid="asset-component-card-default-action">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm btn-block">{{ __('To Tray') }}</button>
                    </form>
                @endif
            @endif

            @if($hasSecondaryActions)
                @if($hasDefaultAction)
                    <div class="btn-group asset-component-card__more">
                        <button type="button"
                                class="btn btn-default btn-sm dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                                data-testid="asset-component-card-more">
                            {{ __('More') }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @if($hasStorageAction && $storageAction)
                                <li>
                                    <a href="#assetComponentStorageModal"
                                       data-toggle="modal"
                                       data-target="#assetComponentStorageModal"
                                       data-storage-action="{{ $storageAction }}"
                                       data-storage-name="{{ $component?->display_name ?: $rowDisplayName }}">
                                        {{ __('To Storage') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasReparentAction)
                                <li>
                                    <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}">
                                        {{ __('Move Within Device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasTransferAction && $transferAction)
                                <li>
                                    <a href="{{ $transferAction }}">
                                        {{ __('Move To Other Device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasOpenAction)
                                <li>
                                    <a href="{{ route('components.show', $component) }}">
                                        {{ __('Open') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @elseif($hasStorageAction || $hasReparentAction || $hasTransferAction)
                    <div class="btn-group asset-component-card__more">
                        <button type="button"
                                class="btn btn-default btn-sm btn-block dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                                data-testid="asset-component-card-more">
                            {{ __('More') }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @if($hasStorageAction && $storageAction)
                                <li>
                                    <a href="#assetComponentStorageModal"
                                       data-toggle="modal"
                                       data-target="#assetComponentStorageModal"
                                       data-storage-action="{{ $storageAction }}"
                                       data-storage-name="{{ $component?->display_name ?: $rowDisplayName }}">
                                        {{ __('To Storage') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasReparentAction)
                                <li>
                                    <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}">
                                        {{ __('Move Within Device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasTransferAction && $transferAction)
                                <li>
                                    <a href="{{ $transferAction }}">
                                        {{ __('Move To Other Device') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @elseif($hasOpenAction)
                    <a href="{{ route('components.show', $component) }}" class="btn btn-default btn-sm btn-block">
                        {{ __('Open') }}
                    </a>
                @endif
            @endif
        </div>
    @endif
@else
    @if($component)
        @unless($isRemoved)
            @if($canMoveComponent)
                <form method="POST" action="{{ route('components.remove_to_tray', $component) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-warning">{{ __('To Tray') }}</button>
                </form>
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
                <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}" class="btn btn-xs btn-default">{{ __('Move Within Device') }}</a>
            @endif
            @if($canInstallComponent)
                <a href="{{ route('hardware.components.transfer.create', [$asset, $component]) }}" class="btn btn-xs btn-primary">{{ __('Move To Other Device') }}</a>
            @endif
        @endunless
        <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
    @elseif($template)
        @if($canMoveTemplate)
            <form method="POST" action="{{ route('hardware.components.expected.tray', [$asset, $template]) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-xs btn-warning">{{ __('To Tray') }}</button>
            </form>
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
        @endif
        @if($canInstallTemplate)
            <a href="{{ route('hardware.components.expected.transfer.create', [$asset, $template]) }}" class="btn btn-xs btn-primary">{{ __('Move To Other Device') }}</a>
        @endif
    @endif
@endif
