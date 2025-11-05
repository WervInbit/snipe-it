@extends('layouts/default')

@section('title')
    {{ __('Start') }}
    @parent
@stop

@section('content')
<div class="container py-4 text-center">
    <h1 class="h4 mb-4">{{ __('Welcome, :name', ['name' => auth()->user()->present()->name()]) }}</h1>
    @include('start.partials.action-button', [
        'href' => route('home'),
        'icon' => 'chart-bar',
        'label' => trans('general.dashboard'),
        'dusk' => 'start-dashboard'
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
@stop
