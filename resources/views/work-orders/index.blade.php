@extends('layouts/default')

@section('title')
{{ __('Work Orders') }}
@parent
@stop

@section('header_right')
@can('create', \App\Models\WorkOrder::class)
<a href="{{ route('work-orders.create') }}" class="btn btn-primary">
    <i class="fas fa-plus" aria-hidden="true"></i>
    {{ __('New Work Order') }}
</a>
@endcan
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Work Orders') }}</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('work-orders.index') }}" class="form-inline" style="margin-bottom: 15px;">
                    <div class="form-group">
                        <label class="sr-only" for="search">{{ trans('general.search') }}</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ $search }}" placeholder="{{ __('Search work orders') }}">
                    </div>
                    <button type="submit" class="btn btn-default">{{ trans('general.search') }}</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ __('Work Order') }}</th>
                            <th>{{ trans('general.name') }}</th>
                            <th>{{ trans('general.company') }}</th>
                            <th>{{ trans('general.status') }}</th>
                            <th>{{ __('Priority') }}</th>
                            <th>{{ __('Intake Date') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Devices') }}</th>
                            <th>{{ __('Tasks') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($workOrders as $workOrder)
                            <tr>
                                <td><a href="{{ route('work-orders.show', $workOrder) }}">{{ $workOrder->work_order_number }}</a></td>
                                <td>{{ $workOrder->title }}</td>
                                <td>{{ $workOrder->company?->name ?? trans('general.none') }}</td>
                                <td>{{ \Illuminate\Support\Str::headline($workOrder->status) }}</td>
                                <td>{{ $workOrder->priority ? \Illuminate\Support\Str::headline($workOrder->priority) : trans('general.none') }}</td>
                                <td>{{ optional($workOrder->intake_date)->format('Y-m-d') ?: trans('general.none') }}</td>
                                <td>{{ optional($workOrder->due_date)->format('Y-m-d') ?: trans('general.none') }}</td>
                                <td>{{ $workOrder->assets_count }}</td>
                                <td>{{ $workOrder->tasks_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $workOrders->links() }}
            </div>
        </div>
    </div>
</div>
@stop
