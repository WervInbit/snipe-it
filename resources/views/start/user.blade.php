@extends('layouts/default')

@section('title')
    {{ __('Start') }}
    @parent
@stop

@section('content')
<div class="container py-4">
    <div class="mx-auto" style="max-width:420px;">
        <h1 class="h4 text-center mb-4">{{ __('Start') }}</h1>
        @include('start.partials.action-button', [
            'href' => route('view-assets'),
            'icon' => 'box',
            'label' => __('My Assets')
        ])
    </div>
</div>
@stop
