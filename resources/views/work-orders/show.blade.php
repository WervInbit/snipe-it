@extends('layouts/default')

@section('title')
{{ $workOrder->work_order_number }}
@parent
@stop

@section('header_right')
@can('update', $workOrder)
<a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-primary">
    <i class="fas fa-edit" aria-hidden="true"></i>
    {{ trans('general.edit') }}
</a>
@endcan
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-default" id="summary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Summary') }}</h3>
            </div>
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>{{ __('Work Order') }}</dt>
                    <dd>{{ $workOrder->work_order_number }}</dd>
                    <dt>{{ trans('general.name') }}</dt>
                    <dd>{{ $workOrder->title }}</dd>
                    <dt>{{ trans('general.status') }}</dt>
                    <dd>{{ \Illuminate\Support\Str::headline($workOrder->status) }}</dd>
                    <dt>{{ __('Priority') }}</dt>
                    <dd>{{ $workOrder->priority ? \Illuminate\Support\Str::headline($workOrder->priority) : trans('general.none') }}</dd>
                    <dt>{{ trans('general.company') }}</dt>
                    <dd>{{ $workOrder->company?->name ?? trans('general.none') }}</dd>
                    <dt>{{ __('Primary Contact') }}</dt>
                    <dd>{{ $workOrder->primaryContact?->present()->fullName() ?? trans('general.none') }}</dd>
                    <dt>{{ __('Intake Date') }}</dt>
                    <dd>{{ optional($workOrder->intake_date)->format('Y-m-d') ?: trans('general.none') }}</dd>
                    <dt>{{ __('Due Date') }}</dt>
                    <dd>{{ optional($workOrder->due_date)->format('Y-m-d') ?: trans('general.none') }}</dd>
                    <dt>{{ __('Visibility') }}</dt>
                    <dd>{{ \Illuminate\Support\Str::headline($workOrder->visibility_profile) }}</dd>
                </dl>

                @if($workOrder->description)
                <hr>
                <strong>{{ trans('general.notes') }}</strong>
                <div>{!! nl2br(e($workOrder->description)) !!}</div>
                @endif

                @if($workOrder->visibleUsers->isNotEmpty())
                <hr>
                <strong>{{ __('Explicit Visible Users') }}</strong>
                <ul class="list-unstyled" style="margin-top: 10px;">
                    @foreach($workOrder->visibleUsers as $visibleUser)
                    <li>{{ $visibleUser->present()->fullName() }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-default" id="devices">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Devices') }}</h3>
            </div>
            <div class="box-body">
                @forelse($workOrder->assets as $device)
                <div class="panel panel-default">
                    <div class="panel-body">
                        @can('update', $workOrder)
                        <form method="POST" action="{{ route('work-orders.assets.update', [$workOrder, $device]) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>{{ __('Linked Asset') }}</label>
                                    <select name="asset_id" class="form-control input-sm">
                                        <option value="">{{ __('Freeform Intake') }}</option>
                                        @foreach($assetOptions as $assetOption)
                                        <option value="{{ $assetOption->id }}" {{ (int) $device->asset_id === (int) $assetOption->id ? 'selected' : '' }}>
                                            {{ $assetOption->asset_tag }}{{ $assetOption->name ? ' - '.$assetOption->name : '' }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('Customer Label') }}</label>
                                    <input type="text" name="customer_label" class="form-control input-sm" value="{{ $device->customer_label }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ trans('general.tag') }}</label>
                                    <input type="text" name="asset_tag_snapshot" class="form-control input-sm" value="{{ $device->asset_tag_snapshot }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ trans('admin/hardware/form.serial') }}</label>
                                    <input type="text" name="serial_snapshot" class="form-control input-sm" value="{{ $device->serial_snapshot }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>{{ trans('general.status') }}</label>
                                    <input type="text" name="status" class="form-control input-sm" value="{{ $device->status }}">
                                </div>
                                <div class="col-md-8 form-group" style="padding-top: 24px;">
                                    <button type="submit" class="btn btn-xs btn-primary">{{ trans('general.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('work-orders.assets.destroy', [$workOrder, $device]) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">{{ trans('general.delete') }}</button>
                                </form>
                                </div>
                            </div>
                        @else
                        <strong>{{ $device->customer_label ?: ($device->asset?->present()->name() ?? trans('general.none')) }}</strong>
                        <p style="margin-top: 10px; margin-bottom: 0;">
                            {{ trans('general.tag') }}: {{ $device->asset_tag_snapshot ?: trans('general.none') }} |
                            {{ trans('admin/hardware/form.serial') }}: {{ $device->serial_snapshot ?: trans('general.none') }} |
                            {{ trans('general.status') }}: {{ $device->status ?: trans('general.none') }}
                        </p>
                        @endcan
                    </div>
                </div>
                @empty
                <p class="text-muted">{{ trans('general.no_results') }}</p>
                @endforelse

                @can('update', $workOrder)
                <hr>
                <h4>{{ __('Add Device') }}</h4>
                <form method="POST" action="{{ route('work-orders.assets.store', $workOrder) }}" class="row">
                    @csrf
                    <div class="col-md-4 form-group">
                        <label for="asset_id">{{ __('Linked Asset') }}</label>
                        <select name="asset_id" id="asset_id" class="form-control">
                            <option value="">{{ __('Freeform Intake') }}</option>
                            @foreach($assetOptions as $assetOption)
                            <option value="{{ $assetOption->id }}">{{ $assetOption->asset_tag }}{{ $assetOption->name ? ' - '.$assetOption->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="customer_label">{{ __('Customer Label') }}</label>
                        <input type="text" name="customer_label" id="customer_label" class="form-control">
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="asset_status">{{ trans('general.status') }}</label>
                        <input type="text" name="status" id="asset_status" class="form-control" value="pending">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">{{ __('Add') }}</button>
                    </div>
                </form>
                @endcan
            </div>
        </div>

        <div class="box box-default" id="tasks">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Tasks') }}</h3>
            </div>
            <div class="box-body">
                @forelse($workOrder->tasks as $task)
                <div class="panel panel-default" id="task-{{ $task->id }}">
                    <div class="panel-body">
                        @can('update', $workOrder)
                        <form method="POST" action="{{ route('work-orders.tasks.update', [$workOrder, $task]) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>{{ __('Type') }}</label>
                                    <input type="text" name="task_type" class="form-control input-sm" value="{{ $task->task_type }}">
                                </div>
                                <div class="col-md-5 form-group">
                                    <label>{{ trans('general.name') }}</label>
                                    <input type="text" name="title" class="form-control input-sm" value="{{ $task->title }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ trans('general.status') }}</label>
                                    <select name="status" class="form-control input-sm">
                                        @foreach(\App\Models\WorkOrderTask::statusOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ $task->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('Assignee') }}</label>
                                    <select name="assigned_to" class="form-control input-sm">
                                        <option value="">{{ trans('general.none') }}</option>
                                        @foreach($taskAssignees as $assignee)
                                        <option value="{{ $assignee->id }}" {{ (int) $task->assigned_to === (int) $assignee->id ? 'selected' : '' }}>{{ $assignee->present()->fullName() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>{{ __('Device') }}</label>
                                    <select name="work_order_asset_id" class="form-control input-sm">
                                        <option value="">{{ trans('general.none') }}</option>
                                        @foreach($workOrder->assets as $device)
                                        <option value="{{ $device->id }}" {{ (int) $task->work_order_asset_id === (int) $device->id ? 'selected' : '' }}>
                                            {{ $device->customer_label ?: ($device->asset_tag_snapshot ?: 'Device #'.$device->id) }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>{{ __('Customer Status Label') }}</label>
                                    <input type="text" name="customer_status_label" class="form-control input-sm" value="{{ $task->customer_status_label }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('Started At') }}</label>
                                    <input type="datetime-local" name="started_at" class="form-control input-sm" value="{{ optional($task->started_at)->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('Completed At') }}</label>
                                    <input type="datetime-local" name="completed_at" class="form-control input-sm" value="{{ optional($task->completed_at)->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('Customer Visible') }}</label>
                                    <div class="checkbox" style="margin-top: 8px;">
                                        <label><input type="checkbox" name="customer_visible" value="1" {{ $task->customer_visible ? 'checked' : '' }}> {{ __('Yes') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control input-sm" rows="2">{{ $task->description }}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Internal Notes') }}</label>
                                    <textarea name="notes_internal" class="form-control input-sm" rows="2">{{ $task->notes_internal }}</textarea>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>{{ __('Customer Notes') }}</label>
                                    <textarea name="notes_customer" class="form-control input-sm" rows="2">{{ $task->notes_customer }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-xs btn-primary">{{ trans('general.save') }}</button>
                        </form>
                        <form method="POST" action="{{ route('work-orders.tasks.destroy', [$workOrder, $task]) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger">{{ trans('general.delete') }}</button>
                        </form>
                        @else
                        <strong>{{ $task->title }}</strong>
                        <span class="label label-default">{{ $task->customer_status_label ?: \Illuminate\Support\Str::headline($task->status) }}</span>
                        <p style="margin-top: 10px; margin-bottom: 5px;">{{ $task->description ?: trans('general.none') }}</p>
                        <p class="text-muted" style="margin-bottom: 0;">
                            {{ __('Type') }}: {{ $task->task_type }} |
                            {{ __('Assignee') }}: {{ $task->assignee?->present()->fullName() ?? trans('general.none') }}
                        </p>
                        @endcan
                    </div>
                </div>
                @empty
                <p class="text-muted">{{ trans('general.no_results') }}</p>
                @endforelse

                @can('update', $workOrder)
                <hr>
                <h4>{{ __('Add Task') }}</h4>
                <form method="POST" action="{{ route('work-orders.tasks.store', $workOrder) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="task_type">{{ __('Type') }}</label>
                            <input type="text" name="task_type" id="task_type" class="form-control" value="general">
                        </div>
                        <div class="col-md-5 form-group">
                            <label for="title">{{ trans('general.name') }}</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="task_status">{{ trans('general.status') }}</label>
                            <select name="status" id="task_status" class="form-control">
                                @foreach(\App\Models\WorkOrderTask::statusOptions() as $value => $label)
                                <option value="{{ $value }}" {{ $value === \App\Models\WorkOrderTask::STATUS_PENDING ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="assigned_to">{{ __('Assignee') }}</label>
                            <select name="assigned_to" id="assigned_to" class="form-control">
                                <option value="">{{ trans('general.none') }}</option>
                                @foreach($taskAssignees as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->present()->fullName() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="work_order_asset_id">{{ __('Device') }}</label>
                            <select name="work_order_asset_id" id="work_order_asset_id" class="form-control">
                                <option value="">{{ trans('general.none') }}</option>
                                @foreach($workOrder->assets as $device)
                                <option value="{{ $device->id }}">{{ $device->customer_label ?: ($device->asset_tag_snapshot ?: 'Device #'.$device->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="customer_status_label">{{ __('Customer Status Label') }}</label>
                            <input type="text" name="customer_status_label" id="customer_status_label" class="form-control">
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="started_at">{{ __('Started At') }}</label>
                            <input type="datetime-local" name="started_at" id="started_at" class="form-control">
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="completed_at">{{ __('Completed At') }}</label>
                            <input type="datetime-local" name="completed_at" id="completed_at" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="task_description">{{ __('Description') }}</label>
                        <textarea name="description" id="task_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="notes_internal">{{ __('Internal Notes') }}</label>
                            <textarea name="notes_internal" id="notes_internal" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="notes_customer">{{ __('Customer Notes') }}</label>
                            <textarea name="notes_customer" id="notes_customer" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="customer_visible" value="1" checked>
                            {{ __('Visible to customer') }}
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Add Task') }}</button>
                </form>
                @endcan
            </div>
        </div>

        <div class="box box-default" id="component-activity">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Component Activity') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans('general.date') }}</th>
                        <th>{{ trans('general.component') }}</th>
                        <th>{{ trans('general.action') }}</th>
                        <th>{{ __('Task') }}</th>
                        <th>{{ trans('general.details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($componentEvents as $event)
                        <tr>
                            <td>{{ optional($event->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($event->componentInstance)
                                <a href="{{ route('components.show', $event->componentInstance) }}">{{ $event->componentInstance->component_tag }}</a>
                                @else
                                <span class="text-muted">{{ trans('general.na') }}</span>
                                @endif
                            </td>
                            <td>{{ $event->actionLabel() }}</td>
                            <td>
                                @if($event->relatedWorkOrderTask)
                                <a href="#task-{{ $event->relatedWorkOrderTask->id }}">{{ $event->relatedWorkOrderTask->title }}</a>
                                @else
                                <span class="text-muted">{{ trans('general.none') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($event->fromAsset)
                                <a href="{{ route('hardware.show', $event->fromAsset) }}">From asset: {{ $event->fromAsset->present()->name() }}</a>
                                @endif
                                @if($event->fromAsset && ($event->toAsset || $event->note))
                                <span> | </span>
                                @endif
                                @if($event->toAsset)
                                <a href="{{ route('hardware.show', $event->toAsset) }}">To asset: {{ $event->toAsset->present()->name() }}</a>
                                @endif
                                @if($event->toAsset && $event->note)
                                <span> | </span>
                                @endif
                                @if($event->note)
                                <span>{{ $event->note }}</span>
                                @endif
                                @if(!$event->fromAsset && !$event->toAsset && !$event->note)
                                <span class="text-muted">{{ trans('general.na') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
