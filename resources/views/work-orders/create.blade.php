@extends('layouts/default')

@section('title')
{{ __('New Work Order') }}
@parent
@stop

@section('content')
<form method="POST" action="{{ route('work-orders.store') }}">
    @csrf
    @include('work-orders.partials.form')
    <div class="text-right">
        <a href="{{ route('work-orders.index') }}" class="btn btn-default">{{ trans('general.cancel') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Create Work Order') }}</button>
    </div>
</form>
@stop
