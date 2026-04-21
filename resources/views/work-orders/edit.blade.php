@extends('layouts/default')

@section('title')
{{ $workOrder->work_order_number }}
@parent
@stop

@section('content')
<form method="POST" action="{{ route('work-orders.update', $workOrder) }}">
    @csrf
    @method('PUT')
    @include('work-orders.partials.form')
    <div class="text-right">
        <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-default">{{ trans('general.cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Save Work Order') }}</button>
    </div>
</form>
@stop
