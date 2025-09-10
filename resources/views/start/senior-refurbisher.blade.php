@extends('layouts/default')

@section('title')
    {{ __('Start') }}
    @parent
@stop

@section('content')
<div class="container py-4 text-center">
    <h1 class="h4 mb-4">{{ __('Welcome, :name', ['name' => auth()->user()->present()->name()]) }}</h1>
    @can('scanning')
        @include('start.partials.action-button', [
            'href' => route('scan'),
            'icon' => 'camera',
            'label' => __('Scan QR')
        ])
    @endcan
    @can('assets.create')
        @include('start.partials.action-button', [
            'href' => route('hardware.create'),
            'icon' => 'plus',
            'label' => trans('general.new_asset')
        ])
    @endcan
</div>
@stop
