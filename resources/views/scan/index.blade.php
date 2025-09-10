@extends('layouts/default')

@section('title')
    {{ __('Scan Asset Tag') }}
    @parent
@stop

@section('content')
<div class="container py-4 text-center">
    <h1 class="h3 mb-3">{{ __('Scan Asset Tag') }}</h1>

    <div id="scan-area" class="position-relative mx-auto" style="max-width:480px;">
        <video id="scan-video" class="w-100 rounded" autoplay muted playsinline></video>
        <canvas id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100"></canvas>
    </div>

    <div id="scan-error" class="alert alert-danger d-none mt-3"></div>

    <button id="scan-start" class="btn btn-primary btn-lg btn-block mt-3">{{ __('Start scanning') }}</button>
    <button id="manual-toggle" class="btn btn-secondary btn-lg btn-block mt-2">{{ __('Enter tag manually') }}</button>

    <form id="manual-form" class="mt-3 d-none">
        <input id="manual-tag" type="text" class="form-control form-control-lg" placeholder="{{ __('Enter asset tag') }}">
        <button type="submit" class="btn btn-primary btn-lg mt-2 btn-block">{{ __('Go') }}</button>
    </form>
</div>
@stop

@section('moar_scripts')
<script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
