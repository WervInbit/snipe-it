@php
    use App\Models\ComponentInstance;

    $row = $row ?? null;
    $variant = $variant ?? 'table';
    $component = $component ?? ($row->component ?? null);
    $template = $template ?? ($row->template ?? null);
    $rowDisplayName = $rowDisplayName ?? ($row?->displayName() ?? $component?->display_name ?? $template?->expected_name ?? trans('general.component'));
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
    $trayAction = $component
        ? route('components.remove_to_tray', $component)
        : ($template ? route('hardware.components.expected.tray', [$asset, $template]) : null);
    $trayReturnTo = route('hardware.show', $asset).'#components';
@endphp

@if($variant === 'mobile')
    @if($hasDefaultAction || $hasSecondaryActions)
        <div @class([
                'asset-component-card__actions',
                'asset-component-card__actions--single' => !$hasDefaultAction || !$hasSecondaryActions,
            ])
            data-testid="asset-component-card-actions">
            @if($hasDefaultAction)
                <button type="button"
                        class="btn btn-warning btn-sm btn-block asset-component-card__default-action"
                        data-toggle="modal"
                        data-target="#assetComponentTrayModal"
                        data-tray-action="{{ $trayAction }}"
                        data-tray-name="{{ $component?->display_name ?: $rowDisplayName }}"
                        data-tray-serial="{{ $component?->serial ?? '' }}"
                        data-tray-return-to="{{ $trayReturnTo }}"
                        data-testid="asset-component-card-default-action">
                    {{ trans('general.to_tray') }}
                </button>
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
                            {{ trans('general.more') }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @if($hasStorageAction && $storageAction)
                                <li>
                                    <a href="#assetComponentStorageModal"
                                       data-toggle="modal"
                                       data-target="#assetComponentStorageModal"
                                       data-storage-action="{{ $storageAction }}"
                                       data-storage-name="{{ $component?->display_name ?: $rowDisplayName }}">
                                        {{ trans('general.to_storage') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasReparentAction)
                                <li>
                                    <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}">
                                        {{ trans('general.move_within_device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasTransferAction && $transferAction)
                                <li>
                                    <a href="{{ $transferAction }}">
                                        {{ trans('general.move_to_other_device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasOpenAction)
                                <li>
                                    <a href="{{ route('components.show', $component) }}">
                                        {{ trans('general.open') }}
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
                            {{ trans('general.more') }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @if($hasStorageAction && $storageAction)
                                <li>
                                    <a href="#assetComponentStorageModal"
                                       data-toggle="modal"
                                       data-target="#assetComponentStorageModal"
                                       data-storage-action="{{ $storageAction }}"
                                       data-storage-name="{{ $component?->display_name ?: $rowDisplayName }}">
                                        {{ trans('general.to_storage') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasReparentAction)
                                <li>
                                    <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}">
                                        {{ trans('general.move_within_device') }}
                                    </a>
                                </li>
                            @endif
                            @if($hasTransferAction && $transferAction)
                                <li>
                                    <a href="{{ $transferAction }}">
                                        {{ trans('general.move_to_other_device') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @elseif($hasOpenAction)
                    <a href="{{ route('components.show', $component) }}" class="btn btn-default btn-sm btn-block">
                        {{ trans('general.open') }}
                    </a>
                @endif
            @endif
        </div>
    @endif
@else
    @if($component)
        @unless($isRemoved)
            @if($canMoveComponent)
                <button
                    type="button"
                    class="btn btn-xs btn-warning"
                    data-toggle="modal"
                    data-target="#assetComponentTrayModal"
                    data-tray-action="{{ $trayAction }}"
                    data-tray-name="{{ $component->display_name }}"
                    data-tray-serial="{{ $component->serial ?? '' }}"
                    data-tray-return-to="{{ $trayReturnTo }}"
                >
                    {{ trans('general.to_tray') }}
                </button>
                <button
                    type="button"
                    class="btn btn-xs btn-default"
                    data-toggle="modal"
                    data-target="#assetComponentStorageModal"
                    data-storage-action="{{ route('hardware.components.storage.store', [$asset, $component]) }}"
                    data-storage-name="{{ $component->display_name }}"
                >
                    {{ trans('general.to_storage') }}
                </button>
                <a href="{{ route('hardware.components.reparent.create', [$asset, $component]) }}" class="btn btn-xs btn-default">{{ trans('general.move_within_device') }}</a>
            @endif
            @if($canInstallComponent)
                <a href="{{ route('hardware.components.transfer.create', [$asset, $component]) }}" class="btn btn-xs btn-primary">{{ trans('general.move_to_other_device') }}</a>
            @endif
        @endunless
        <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ trans('general.open') }}</a>
    @elseif($template)
        @if($canMoveTemplate)
            <button
                type="button"
                class="btn btn-xs btn-warning"
                data-toggle="modal"
                data-target="#assetComponentTrayModal"
                data-tray-action="{{ $trayAction }}"
                data-tray-name="{{ $rowDisplayName }}"
                data-tray-serial=""
                data-tray-return-to="{{ $trayReturnTo }}"
            >
                {{ trans('general.to_tray') }}
            </button>
            <button
                type="button"
                class="btn btn-xs btn-default"
                data-toggle="modal"
                data-target="#assetComponentStorageModal"
                data-storage-action="{{ route('hardware.components.expected.storage.store', [$asset, $template]) }}"
                data-storage-name="{{ $rowDisplayName }}"
            >
                {{ trans('general.to_storage') }}
            </button>
        @endif
        @if($canInstallTemplate)
            <a href="{{ route('hardware.components.expected.transfer.create', [$asset, $template]) }}" class="btn btn-xs btn-primary">{{ trans('general.move_to_other_device') }}</a>
        @endif
    @endif
@endif
