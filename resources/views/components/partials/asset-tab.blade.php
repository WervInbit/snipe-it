@php
    $componentRoster = $componentRoster ?? new \App\Services\Components\AssetComponentRoster(collect());
    $rosterRows = $componentRoster->rows ?? collect();
    $priorityRows = $rosterRows->filter(fn ($row) => !$row->isExpected() && !$row->isRemoved())->values();
    $baselineRows = $rosterRows->filter(fn ($row) => $row->isExpected() || $row->isRemoved())->values();
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
                        <div class="table-responsive">
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
                                    @include('components.partials.asset-roster-row', ['row' => $row, 'asset' => $asset])
                                @endforeach

                                @if($priorityRows->isNotEmpty() && $baselineRows->isNotEmpty())
                                    <tr class="active" data-testid="asset-component-expected-separator">
                                        <td colspan="7">
                                            <small class="text-muted">{{ __('Expected baseline') }}</small>
                                        </td>
                                    </tr>
                                @endif

                                @foreach($baselineRows as $row)
                                    @include('components.partials.asset-roster-row', ['row' => $row, 'asset' => $asset])
                                @endforeach
                                </tbody>
                            </table>
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
                        <div class="table-responsive">
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
        </div>
    </div>
</div>
