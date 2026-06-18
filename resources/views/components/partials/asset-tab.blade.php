@php
    $componentRoster = $componentRoster ?? new \App\Services\Components\AssetComponentRoster(collect());
    $rosterRows = $componentRoster->rows ?? collect();
    $removedExpectedSubcomponentsByParent = $removedExpectedSubcomponentsByParent ?? collect();
    $topLevelComponentIds = $rosterRows
        ->pluck('component')
        ->filter(fn ($component) => $component && !$component->parent_component_instance_id)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();
    $nestedChildRows = $rosterRows
        ->filter(fn ($row) => $row->component && in_array((int) $row->component->parent_component_instance_id, $topLevelComponentIds, true))
        ->values();
    $nestedChildRowComponentIds = $nestedChildRows
        ->pluck('component.id')
        ->map(fn ($id) => (int) $id)
        ->all();
    $childRowsByParentId = $nestedChildRows
        ->groupBy(fn ($row) => (int) $row->component->parent_component_instance_id);
    $primaryRows = $rosterRows
        ->reject(fn ($row) => $row->component && in_array((int) $row->component->id, $nestedChildRowComponentIds, true))
        ->values();
    $priorityRows = $primaryRows->filter(fn ($row) => !$row->isExpected() && !$row->isRemoved())->values();
    $baselineRows = $primaryRows->filter(fn ($row) => $row->isExpected() || $row->isRemoved())->values();
@endphp

<div class="tab-pane fade" id="components">
    <div class="row">
        <div class="col-md-12">
            <div class="clearfix" style="margin-bottom: 15px;">
                <a href="{{ route('hardware.components.add', $asset) }}" class="btn btn-primary pull-left">
                    {{ __('Add / Install Component') }}
                </a>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">{{ __('Current Components') }}</div>
                <div class="panel-body">
                    @if($rosterRows->isEmpty())
                        <p class="text-muted">{{ __('No current components are shown for this asset.') }}</p>
                    @else
                        <div class="table-responsive asset-components-desktop hidden-xs hidden-sm" data-testid="asset-components-desktop-table">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ trans('general.name') }}</th>
                                    <th>{{ trans('general.tag') }}</th>
                                    <th>{{ trans('admin/hardware/form.serial') }}</th>
                                    <th>{{ trans('general.category') }}</th>
                                    <th>{{ trans('general.manufacturer') }}</th>
                                    <th>{{ trans('general.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($priorityRows as $row)
                                    @include('components.partials.asset-roster-row', ['row' => $row, 'asset' => $asset, 'depth' => 0])
                                    @include('components.partials.asset-subcomponent-rows', [
                                        'parentRow' => $row,
                                        'asset' => $asset,
                                        'childRowsByParentId' => $childRowsByParentId,
                                        'removedExpectedSubcomponentsByParent' => $removedExpectedSubcomponentsByParent,
                                    ])
                                @endforeach

                                @if($priorityRows->isNotEmpty() && $baselineRows->isNotEmpty())
                                    <tr class="active" data-testid="asset-component-expected-separator">
                                        <td colspan="7">
                                            <small class="text-muted">{{ __('Expected baseline') }}</small>
                                        </td>
                                    </tr>
                                @endif

                                @foreach($baselineRows as $row)
                                    @include('components.partials.asset-roster-row', ['row' => $row, 'asset' => $asset, 'depth' => 0])
                                    @include('components.partials.asset-subcomponent-rows', [
                                        'parentRow' => $row,
                                        'asset' => $asset,
                                        'childRowsByParentId' => $childRowsByParentId,
                                        'removedExpectedSubcomponentsByParent' => $removedExpectedSubcomponentsByParent,
                                    ])
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="asset-components-mobile hidden-md hidden-lg" data-testid="asset-components-mobile-list">
                            @foreach($priorityRows as $row)
                                @include('components.partials.asset-roster-card', ['row' => $row, 'asset' => $asset, 'depth' => 0])
                                @include('components.partials.asset-subcomponent-cards', [
                                    'parentRow' => $row,
                                    'asset' => $asset,
                                    'childRowsByParentId' => $childRowsByParentId,
                                    'removedExpectedSubcomponentsByParent' => $removedExpectedSubcomponentsByParent,
                                ])
                            @endforeach

                            @if($priorityRows->isNotEmpty() && $baselineRows->isNotEmpty())
                                <div class="asset-components-mobile__section-label" data-testid="asset-component-mobile-baseline-separator">
                                    {{ __('Model baseline') }}
                                </div>
                            @endif

                            @foreach($baselineRows as $row)
                                @include('components.partials.asset-roster-card', ['row' => $row, 'asset' => $asset, 'depth' => 0])
                                @include('components.partials.asset-subcomponent-cards', [
                                    'parentRow' => $row,
                                    'asset' => $asset,
                                    'childRowsByParentId' => $childRowsByParentId,
                                    'removedExpectedSubcomponentsByParent' => $removedExpectedSubcomponentsByParent,
                                ])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">{{ trans('general.history') }}</div>
                <div class="panel-body">
                    @if ($componentHistory->isEmpty())
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @else
                        <div class="table-responsive asset-components-history-desktop hidden-xs hidden-sm" data-testid="asset-components-history-desktop-table">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ trans('general.date') }}</th>
                                    <th>{{ trans('general.asset_tag') }}</th>
                                    <th>{{ trans('general.action') }}</th>
                                    <th>{{ trans('general.location') }}</th>
                                    <th>{{ trans('general.performed_by') }}</th>
                                    <th>{{ trans('general.notes') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($componentHistory as $event)
                                    @php($component = $event->componentInstance)
                                    <tr>
                                        <td>{{ Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                                        <td>
                                            @if ($component && !$component->trashed())
                                                <a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a>
                                            @elseif ($component)
                                                {{ $component->component_tag }}
                                                <div class="text-muted small">{{ __('Deleted') }}</div>
                                            @else
                                                {{ trans('general.none') }}
                                            @endif
                                        </td>
                                        <td>{{ $event->actionLabel() }}</td>
                                        <td>{{ $event->toStorageLocation?->name ?: $event->fromStorageLocation?->name ?: trans('general.none') }}</td>
                                        <td>{{ $event->performedBy ? $event->performedBy->present()->fullName() : trans('general.system') }}</td>
                                        <td>{{ $event->note ?: trans('general.none') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="asset-component-history-mobile hidden-md hidden-lg" data-testid="asset-components-history-mobile-list">
                            @foreach ($componentHistory as $event)
                                @php($component = $event->componentInstance)
                                <article class="asset-component-history-card" data-testid="asset-component-history-card">
                                    <time class="asset-component-history-card__date">
                                        {{ Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}
                                    </time>
                                    <div class="asset-component-history-card__title">
                                        @if ($component && !$component->trashed())
                                            <a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a>
                                        @elseif ($component)
                                            {{ $component->component_tag }}
                                            <span class="text-muted small">{{ __('Deleted') }}</span>
                                        @else
                                            {{ trans('general.none') }}
                                        @endif
                                    </div>
                                    <div class="asset-component-history-card__meta">
                                        {{ $event->actionLabel() }}
                                        @php($historyLocation = $event->toStorageLocation?->name ?: $event->fromStorageLocation?->name)
                                        @if($historyLocation)
                                            | {{ $historyLocation }}
                                        @endif
                                    </div>
                                    <div class="asset-component-history-card__meta">
                                        {{ __('By') }}: {{ $event->performedBy ? $event->performedBy->present()->fullName() : trans('general.system') }}
                                    </div>
                                    @if($event->note)
                                        <div class="asset-component-history-card__note">{{ $event->note }}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal fade" id="assetComponentStorageModal" tabindex="-1" role="dialog" aria-labelledby="assetComponentStorageModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="" data-asset-component-storage-form>
                            @csrf
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="assetComponentStorageModalLabel">{{ __('Move To Stock') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Move this component into stock now. You can assign a specific storage location later from the component detail page.') }}</p>

                                <div class="alert alert-info">
                                    <strong>{{ __('Component') }}:</strong>
                                    <span data-asset-component-storage-name>{{ trans('general.none') }}</span>
                                </div>

                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="needs_verification" value="1">
                                        {{ __('Mark as needing verification after moving to stock') }}
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label for="asset_component_storage_modal_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="asset_component_storage_modal_note" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-warning">{{ __('Confirm Move To Stock') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="assetComponentTrayModal" tabindex="-1" role="dialog" aria-labelledby="assetComponentTrayModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="" data-asset-component-tray-form>
                            @csrf
                            <input type="hidden" name="return_to" value="{{ route('hardware.show', $asset) }}#components" data-asset-component-tray-return-to>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="assetComponentTrayModalLabel">{{ __('Move To Tray') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Remove this component from its current asset and place it in your tray.') }}</p>

                                <div class="alert alert-info">
                                    <strong>{{ __('Component') }}:</strong>
                                    <span data-asset-component-tray-name>{{ trans('general.none') }}</span>
                                </div>

                                @include('components.partials.serial-change-control', [
                                    'serialId' => 'asset_component_tray_serial',
                                    'currentSerial' => '',
                                ])

                                <div class="form-group">
                                    <label for="asset_component_tray_modal_note">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="asset_component_tray_modal_note" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-warning">{{ __('Confirm To Tray') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
