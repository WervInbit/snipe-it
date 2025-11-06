@extends('layouts/default')

@section('title')
    {{ __('Start') }}
    @parent
@stop

@section('content')
<div class="container py-4">
    <div class="mx-auto" style="max-width:420px;">
        <h1 class="h4 text-center mb-4">{{ __('Start') }}</h1>
        @can('scanning')
            @include('start.partials.action-button', [
                'href' => route('scan'),
                'icon' => 'camera',
                'label' => trans('general.scan_qr'),
                'dusk' => 'start-scan',
                'testid' => 'start-scan'
            ])
        @endcan
    </div>
</div>
@stop
