@extends('layouts/default')

@section('title')
{{ __('My Work Orders') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('My Work Orders') }}</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ __('Work Order') }}</th>
                        <th>{{ trans('general.name') }}</th>
                        <th>{{ trans('general.status') }}</th>
                        <th>{{ trans('general.company') }}</th>
                        <th>{{ __('Intake Date') }}</th>
                        <th>{{ __('Due Date') }}</th>
                        <th>{{ __('Devices') }}</th>
                        <th>{{ __('Tasks') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($workOrders as $workOrder)
                        <tr>
                            <td><a href="{{ route('account.work-orders.show', $workOrder) }}">{{ $workOrder->work_order_number }}</a></td>
                            <td>{{ $workOrder->title }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($workOrder->status) }}</td>
                            <td>{{ $workOrder->company?->name ?? trans('general.none') }}</td>
                            <td>{{ optional($workOrder->intake_date)->format('Y-m-d') ?: trans('general.none') }}</td>
                            <td>{{ optional($workOrder->due_date)->format('Y-m-d') ?: trans('general.none') }}</td>
                            <td>{{ $workOrder->assets_count }}</td>
                            <td>{{ $workOrder->tasks_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $workOrders->links() }}
            </div>
        </div>
    </div>
</div>
@stop
