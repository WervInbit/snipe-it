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
        @can('assets.create')
            @include('start.partials.action-button', [
                'href' => route('hardware.create'),
                'icon' => 'plus',
                'label' => trans('general.new_asset'),
                'dusk' => 'start-new-asset',
                'testid' => 'start-new-asset'
            ])
        @endcan
        @include('start.partials.action-button', [
            'href' => route('home'),
            'icon' => 'sliders-h',
            'label' => trans('general.manage_portal'),
            'variant' => 'outline-secondary',
            'dusk' => 'start-manage',
            'testid' => 'start-manage'
        ])
        @include('start.partials.action-button', [
            'href' => route('hardware.index'),
            'icon' => 'desktop',
            'label' => __('Hardware')
        ])
        @include('start.partials.action-button', [
            'href' => route('users.index'),
            'icon' => 'users',
            'label' => trans('general.users')
        ])
        @include('start.partials.action-button', [
            'href' => route('settings.general.index'),
            'icon' => 'cog',
            'label' => trans('general.settings')
        ])
    </div>
</div>
@stop
