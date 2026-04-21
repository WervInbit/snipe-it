@extends('layouts/default')

@section('title')
{{ $workOrder->work_order_number }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-default">
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
                    <dt>{{ trans('general.company') }}</dt>
                    <dd>{{ $workOrder->company?->name ?? trans('general.none') }}</dd>
                    <dt>{{ __('Primary Contact') }}</dt>
                    <dd>{{ $workOrder->primaryContact?->present()->fullName() ?? trans('general.none') }}</dd>
                    <dt>{{ __('Intake Date') }}</dt>
                    <dd>{{ optional($workOrder->intake_date)->format('Y-m-d') ?: trans('general.none') }}</dd>
                    <dt>{{ __('Due Date') }}</dt>
                    <dd>{{ optional($workOrder->due_date)->format('Y-m-d') ?: trans('general.none') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Devices') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ trans('general.tag') }}</th>
                        <th>{{ trans('admin/hardware/form.serial') }}</th>
                        <th>{{ trans('general.status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($workOrder->assets as $device)
                        <tr>
                            <td>{{ $device->customer_label ?: ($device->asset?->present()->name() ?? trans('general.none')) }}</td>
                            <td>{{ $device->asset_tag_snapshot ?: trans('general.none') }}</td>
                            <td>{{ $device->serial_snapshot ?: trans('general.none') }}</td>
                            <td>{{ $device->status ?: trans('general.none') }}</td>
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

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Tasks') }}</h3>
            </div>
            <div class="box-body">
                @forelse($workOrder->tasks as $task)
                <div class="panel panel-default">
                    <div class="panel-body">
                        <strong>{{ $task->title }}</strong>
                        <span class="label label-default">{{ $task->customer_status_label ?: \Illuminate\Support\Str::headline($task->status) }}</span>
                        @if($task->description)
                        <p style="margin-top: 10px;">{{ $task->description }}</p>
                        @endif
                        @if($workOrder->portalShowsCustomerNotes() && $task->notes_customer)
                        <p class="text-muted" style="margin-bottom: 0;">{{ $task->notes_customer }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted">{{ trans('general.no_results') }}</p>
                @endforelse
            </div>
        </div>

        @if($workOrder->portalShowsComponents())
        <div class="box box-default">
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
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($componentEvents as $event)
                        <tr>
                            <td>{{ optional($event->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->componentInstance?->component_tag ?? trans('general.na') }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($event->event_type) }}</td>
                            <td>{{ $event->relatedWorkOrderTask?->title ?? trans('general.none') }}</td>
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
        @endif
    </div>
</div>
@stop
