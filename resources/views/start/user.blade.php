@extends('layouts/default')

@section('title')
    {{ __('Start') }}
    @parent
@stop

@section('content')
<div class="container py-4 text-center">
    <h1 class="h4 mb-4">{{ __('Welcome, :name', ['name' => auth()->user()->present()->name()]) }}</h1>
    @include('start.partials.action-button', [
        'href' => route('view-assets'),
        'icon' => 'box',
        'label' => __('My Assets')
    ])
</div>
@stop
