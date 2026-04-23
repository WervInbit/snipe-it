@extends('layouts/default')

@section('title')
{{ $component->component_tag }} {{ $component->display_name }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.tray') }}" class="btn btn-default">
    {{ __('My Tray') }}
</a>
@can('create', \App\Models\ComponentInstance::class)
<a href="{{ route('components.create') }}" class="btn btn-primary">
    {{ __('Register Component') }}
</a>
@endcan
<a href="{{ route('components.index') }}" class="btn btn-default">
    {{ trans('general.back') }}
</a>
@stop

@section('content')
@php
    $isInstalled = $component->status === \App\Models\ComponentInstance::STATUS_INSTALLED;
    $isInTray = $component->status === \App\Models\ComponentInstance::STATUS_IN_TRANSFER;
    $isInStock = $component->status === \App\Models\ComponentInstance::STATUS_IN_STOCK;
    $isNeedsVerification = $component->status === \App\Models\ComponentInstance::STATUS_NEEDS_VERIFICATION;
    $isDefective = $component->status === \App\Models\ComponentInstance::STATUS_DEFECTIVE;
    $isDestructionPending = $component->status === \App\Models\ComponentInstance::STATUS_DESTRUCTION_PENDING;
    $isDestroyed = $component->status === \App\Models\ComponentInstance::STATUS_DESTROYED_RECYCLED;
    $returnTo = route('components.show', $component);
    $statusTransitions = [];

    if ($isInstalled) {
        $statusTransitions[] = [
            'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_IN_TRANSFER),
            'target' => '#componentToTrayModal',
        ];
    } elseif (!$isDestroyed) {
        if ($isInTray || $isNeedsVerification || $isDefective) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_IN_STOCK),
                'target' => '#componentToStockModal',
            ];
        }

        if ($isInTray || $isInStock || $isDefective) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_NEEDS_VERIFICATION),
                'target' => '#componentNeedsVerificationModal',
            ];
        }

        if ($isInTray || $isInStock || $isNeedsVerification) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_DEFECTIVE),
                'target' => '#componentDefectiveModal',
            ];
        }

        if (!$isDestructionPending) {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_DESTRUCTION_PENDING),
                'target' => '#componentDestructionPendingModal',
            ];
        } else {
            $statusTransitions[] = [
                'label' => \App\Models\ComponentInstance::statusLabel(\App\Models\ComponentInstance::STATUS_DESTROYED_RECYCLED),
                'target' => '#componentDestroyedModal',
            ];
        }
    }

    $statusHistory = $component->events->filter(fn ($event) => filled($event->from_status) || filled($event->to_status))->values();
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.details') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ trans('general.tag') }}</dt>
                    <dd>{{ $component->component_tag }}</dd>

                    <dt>{{ trans('general.name') }}</dt>
                    <dd>{{ $component->display_name }}</dd>

                    <dt>{{ trans('general.status') }}</dt>
                    <dd>{{ \App\Models\ComponentInstance::statusLabel($component->status) ?? $component->status }}</dd>

                    <dt>{{ trans('general.type') }}</dt>
                    <dd>{{ \App\Models\ComponentInstance::sourceTypeLabel($component->source_type) ?? $component->source_type }}</dd>

                    <dt>{{ trans('general.condition') }}</dt>
                    <dd>{{ $component->condition_code }}</dd>

                    @if($component->serial)
                    <dt>{{ trans('admin/hardware/form.serial') }}</dt>
                    <dd>{{ $component->serial }}</dd>
                    @endif

                    @if($component->componentDefinition)
                    <dt>{{ trans('general.category') }}</dt>
                    <dd>{{ $component->componentDefinition->category?->name }}</dd>

                    <dt>{{ trans('general.manufacturer') }}</dt>
                    <dd>{{ $component->componentDefinition->manufacturer?->name }}</dd>
                    @endif

                    @if($component->supplier)
                    <dt>{{ trans('general.supplier') }}</dt>
                    <dd>{{ $component->supplier->name }}</dd>
                    @endif

                    @if($component->received_at)
                    <dt>{{ trans('general.purchase_date') }}</dt>
                    <dd>{{ $component->received_at?->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.current') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ trans('general.asset') }}</dt>
                    <dd>
                        @if($component->currentAsset)
                        <a href="{{ route('hardware.show', $component->currentAsset) }}">{{ $component->currentAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.location') }}</dt>
                    <dd>
                        @if($component->storageLocation)
                        {{ $component->storageLocation->name }}
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.user') }}</dt>
                    <dd>
                        @if($component->heldBy)
                        <a href="{{ route('users.show', $component->heldBy) }}">{{ $component->heldBy->present()->fullName() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>

                    <dt>{{ trans('general.source') }}</dt>
                    <dd>
                        @if($component->sourceAsset)
                        <a href="{{ route('hardware.show', $component->sourceAsset) }}">{{ $component->sourceAsset->present()->name() }}</a>
                        @else
                        <span class="text-muted">{{ trans('general.na') }}</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @if(in_array($component->status, [
            \App\Models\ComponentInstance::STATUS_IN_STOCK,
            \App\Models\ComponentInstance::STATUS_NEEDS_VERIFICATION,
            \App\Models\ComponentInstance::STATUS_DEFECTIVE,
            \App\Models\ComponentInstance::STATUS_DESTRUCTION_PENDING,
        ], true))
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Storage Location') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('Components can be moved into stock first and assigned to a specific storage location later here.') }}</p>
                    @can('update', $component)
                        <form method="POST" action="{{ route('components.update', $component) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group {{ $errors->has('storage_location_id') ? 'has-error' : '' }}">
                                <label for="component_storage_location_id">{{ __('Storage Location') }}</label>
                                <select class="form-control" id="component_storage_location_id" name="storage_location_id">
                                    <option value="">{{ __('No specific storage location yet') }}</option>
                                    @foreach($editableStorageLocations as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                            {{ $location->name }} ({{ \Illuminate\Support\Str::headline($location->type) }})
                                            @if($location->siteLocation)
                                                - {{ $location->siteLocation->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                {!! $errors->first('storage_location_id', '<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="form-group {{ $errors->has('storage_location_note') ? 'has-error' : '' }}">
                                <label for="component_storage_location_note">{{ trans('general.notes') }}</label>
                                <textarea class="form-control" id="component_storage_location_note" name="storage_location_note" rows="3" placeholder="{{ __('Optional note for the storage-location change') }}">{{ old('storage_location_note') }}</textarea>
                                {!! $errors->first('storage_location_note', '<span class="help-block">:message</span>') !!}
                            </div>
                            <button type="submit" class="btn btn-default">{{ __('Save Storage Location') }}</button>
                        </form>
                    @else
                        <p class="text-muted">{{ __('You do not have permission to change the storage location for this component.') }}</p>
                    @endcan
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.actions') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">{{ __('Launch the next workflow for this component from the buttons below.') }}</p>

                <div class="btn-toolbar" role="toolbar">
                    @if(!empty($statusTransitions))
                        <div class="form-group" style="display:inline-block; min-width:260px; margin-right:10px; margin-bottom:0; vertical-align:top;">
                            <label class="sr-only" for="component_status_transition">{{ __('Status') }}</label>
                            <select class="form-control" id="component_status_transition">
                                <option value="">{{ __('Status') }}: {{ \App\Models\ComponentInstance::statusLabel($component->status) ?? $component->status }}</option>
                                @foreach($statusTransitions as $transition)
                                    <option value="{{ $transition['target'] }}">{{ $transition['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($isInstalled)
                        @can('install', $component)
                            @if($component->currentAsset)
                                <a href="{{ route('hardware.components.transfer.create', [$component->currentAsset, $component]) }}" class="btn btn-primary">{{ __('Move To Other Device') }}</a>
                            @endif
                        @endcan
                    @elseif (!$isDestroyed)
                        @can('install', $component)
                            @if(!$isDestructionPending && !$isDefective)
                                <a href="{{ route('components.install.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-primary">{{ __('Install') }}</a>
                            @endif
                        @endcan
                    @endif

                    @if($component->currentAsset)
                        <a href="{{ route('hardware.show', $component->currentAsset) }}" class="btn btn-default">{{ __('Open Asset') }}</a>
                    @endif
                    <a href="{{ route('components.tray') }}" class="btn btn-default">{{ __('My Tray') }}</a>
                </div>
            </div>
        </div>

        @if ($isInstalled)
            @can('move', $component)
                <div class="modal fade" id="componentToTrayModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentToTrayModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('components.remove_to_tray', $component) }}">
                                @csrf
                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="componentToTrayModalLabel">{{ __('Move To Tray') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted">{{ __('Remove this component from its current asset and place it in your tray.') }}</p>
                                    <div class="alert alert-info">
                                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                                        @if($component->currentAsset)
                                            <br><strong>{{ __('Current Asset') }}:</strong> {{ $component->currentAsset->present()->name() }}
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="component_remove_note_modal">{{ trans('general.notes') }}</label>
                                        <textarea class="form-control" id="component_remove_note_modal" name="note" rows="4"></textarea>
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
            @endcan
        @endif

        @if (($isInTray || $isNeedsVerification || $isDefective) && !$isDestroyed)
            <div class="modal fade" id="componentToStockModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentToStockModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.move_to_stock', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentToStockModalLabel">{{ __('Move To Stock') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as in stock. You can assign a specific storage location later on this page.') }}</p>
                                <div class="form-group">
                                    <label for="component_move_to_stock_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_move_to_stock_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-default">{{ __('Confirm In Stock') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (($isInTray || $isInStock || $isDefective) && !$isDestroyed)
            <div class="modal fade" id="componentNeedsVerificationModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentNeedsVerificationModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.flag_needs_verification', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentNeedsVerificationModalLabel">{{ __('Mark Needs Verification') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as needing verification.') }}</p>
                                <div class="form-group">
                                    <label for="component_needs_verification_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_needs_verification_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-warning">{{ __('Confirm Needs Verification') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (($isInTray || $isInStock || $isNeedsVerification) && !$isDestroyed && !$isDefective)
            <div class="modal fade" id="componentDefectiveModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDefectiveModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_defective', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDefectiveModalLabel">{{ __('Mark Defective') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as defective so it is clearly separated from normal stock.') }}</p>
                                <div class="form-group">
                                    <label for="component_defective_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_defective_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Defective') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isDestroyed && !$isDestructionPending)
            <div class="modal fade" id="componentDestructionPendingModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDestructionPendingModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_destruction_pending', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDestructionPendingModalLabel">{{ __('Mark Destruction Pending') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Mark this component as pending destruction.') }}</p>
                                <div class="form-group">
                                    <label for="component_destruction_pending_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_destruction_pending_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Destruction Pending') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($isDestructionPending)
            <div class="modal fade" id="componentDestroyedModal" data-component-status-modal tabindex="-1" role="dialog" aria-labelledby="componentDestroyedModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('components.mark_destroyed', $component) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title" id="componentDestroyedModalLabel">{{ __('Mark Destroyed') }}</h4>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('Confirm that this component has been destroyed or recycled.') }}</p>
                                <div class="form-group">
                                    <label for="component_destroyed_note_modal">{{ trans('general.notes') }}</label>
                                    <textarea class="form-control" id="component_destroyed_note_modal" name="note" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Destroyed') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if (!$isInstalled)
            @can('delete', $component)
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Delete Component') }}</h3>
                    </div>
                    <form method="POST" action="{{ route('components.destroy', $component) }}">
                        <div class="box-body">
                            @csrf
                            @method('DELETE')
                            <p class="text-muted">{{ __('Only loose or inactive components can be deleted. Installed components must be removed first.') }}</p>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            @endcan
        @endif

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.notes') }}</h3>
            </div>
            <div class="box-body">
                @can('update', $component)
                    <form method="POST" action="{{ route('components.update', $component) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                            <textarea class="form-control" name="notes" id="component_notes" rows="4" placeholder="{{ __('Add a note for this component') }}">{{ old('notes', $component->notes ?? '') }}</textarea>
                            {!! $errors->first('notes', '<span class="help-block">:message</span>') !!}
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Save Note') }}</button>
                    </form>
                @else
                    @if($component->notes)
                        <div>{!! nl2br(\App\Helpers\Helper::parseEscapedMarkedownInline($component->notes)) !!}</div>
                    @else
                        <p class="text-muted">{{ trans('general.none') }}</p>
                    @endif
                @endcan
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.file_uploads') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">{{ __('Upload photos or files for this component here.') }}</p>
                <x-filestable object_type="component-instances" :object="$component" />
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Status History') }}</h3>
            </div>
            <div class="box-body table-responsive">
                @if($statusHistory->isEmpty())
                    <p class="text-muted">{{ trans('general.none') }}</p>
                @else
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ trans('general.date') }}</th>
                            <th>{{ trans('general.from') }}</th>
                            <th>{{ trans('general.to') }}</th>
                            <th>{{ trans('general.user') }}</th>
                            <th>{{ trans('general.notes') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($statusHistory as $event)
                            <tr>
                                <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ \App\Models\ComponentInstance::statusLabel($event->from_status) ?? trans('general.none') }}</td>
                                <td>{{ \App\Models\ComponentInstance::statusLabel($event->to_status) ?? trans('general.none') }}</td>
                                <td>{{ $event->performedBy?->present()->fullName() ?? trans('general.system') }}</td>
                                <td>{{ $event->note ?: trans('general.none') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.history') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans('general.date') }}</th>
                        <th>{{ trans('general.action') }}</th>
                        <th>{{ trans('general.user') }}</th>
                        <th>{{ trans('general.details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($component->events as $event)
                        <tr>
                            <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->actionLabel() }}</td>
                            <td>{{ $event->performedBy?->present()->fullName() ?? trans('general.system') }}</td>
                            <td>
                                @php
                                    $details = collect([
                                        $event->fromAsset ? 'From asset: '.$event->fromAsset->present()->name() : null,
                                        $event->toAsset ? 'To asset: '.$event->toAsset->present()->name() : null,
                                        $event->fromStorageLocation ? 'From location: '.$event->fromStorageLocation->name : null,
                                        $event->toStorageLocation ? 'To location: '.$event->toStorageLocation->name : null,
                                        $event->heldBy ? 'Held by: '.$event->heldBy->present()->fullName() : null,
                                        $event->relatedWorkOrder ? 'Work order: '.$event->relatedWorkOrder->work_order_number : null,
                                        $event->relatedWorkOrderTask ? 'Task: '.$event->relatedWorkOrderTask->title : null,
                                        $event->isAutoAgedVerificationEscalation() ? 'Triggered automatically by tray aging.' : null,
                                        $event->note,
                                    ])->filter()->values();
                                @endphp
                                @if($details->isEmpty())
                                <span class="text-muted">{{ trans('general.na') }}</span>
                                @else
                                @php
                                    $taskWorkOrder = $event->relatedWorkOrderTask?->workOrder;
                                @endphp
                                @if($event->relatedWorkOrder || $event->relatedWorkOrderTask)
                                    @foreach($details as $detail)
                                        @if(\Illuminate\Support\Str::startsWith($detail, 'From asset:') && $event->fromAsset && auth()->user()?->can('view', $event->fromAsset))
                                        <a href="{{ route('hardware.show', $event->fromAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'To asset:') && $event->toAsset && auth()->user()?->can('view', $event->toAsset))
                                        <a href="{{ route('hardware.show', $event->toAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'Work order:') && $event->relatedWorkOrder)
                                        <a href="{{ route('work-orders.show', $event->relatedWorkOrder) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'Task:') && $taskWorkOrder && $event->relatedWorkOrderTask)
                                        <a href="{{ route('work-orders.show', $taskWorkOrder) }}#task-{{ $event->relatedWorkOrderTask->id }}">{{ $detail }}</a>
                                        @else
                                        {{ $detail }}
                                        @endif
                                        @if(!$loop->last)
                                        <span> | </span>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach($details as $detail)
                                        @if(\Illuminate\Support\Str::startsWith($detail, 'From asset:') && $event->fromAsset && auth()->user()?->can('view', $event->fromAsset))
                                        <a href="{{ route('hardware.show', $event->fromAsset) }}">{{ $detail }}</a>
                                        @elseif(\Illuminate\Support\Str::startsWith($detail, 'To asset:') && $event->toAsset && auth()->user()?->can('view', $event->toAsset))
                                        <a href="{{ route('hardware.show', $event->toAsset) }}">{{ $detail }}</a>
                                        @else
                                        {{ $detail }}
                                        @endif
                                        @if(!$loop->last)
                                        <span> | </span>
                                        @endif
                                    @endforeach
                                @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
    @parent
    <script>
        (function () {
            var select = document.getElementById('component_status_transition');
            if (!select) {
                return;
            }

            function resetSelect() {
                select.value = '';
            }

            select.addEventListener('change', function () {
                var target = select.value;
                if (!target) {
                    return;
                }

                var modalEl = document.querySelector(target);
                if (!modalEl) {
                    resetSelect();
                    return;
                }

                if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    return;
                }

                var $ = window.jQuery || window.$;
                if ($ && $.fn && $.fn.modal) {
                    $(modalEl).modal('show');
                    return;
                }

                resetSelect();
            });

            document.querySelectorAll('[data-component-status-modal]').forEach(function (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', resetSelect);
            });
        })();
    </script>
@stop
