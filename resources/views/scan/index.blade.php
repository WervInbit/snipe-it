@extends('layouts/default')

@section('title')
    {{ __('Scan QR Code') }}
    @parent
@stop

@section('content')
<div class="container text-center">
    <h1>{{ __('Scan a QR code') }}</h1>
    <p>{{ __('Align the QR code within the frame.') }}</p>

    <div id="scan-error" class="alert alert-danger" style="display:none;"></div>
    <div id="scan-status" class="mb-2"></div>

    <div class="d-inline-block position-relative" style="max-width:400px;">
        <video id="scan-video" class="w-100" style="height:auto;border:1px solid #ccc;" autoplay muted playsinline></video>
        <canvas id="scan-overlay" class="position-absolute top-0 start-0 w-100" style="height:auto;pointer-events:none;"></canvas>
    </div>

    <div class="mt-2">
        <select id="camera-select" class="form-select form-select-sm d-inline-block w-auto"></select>
    </div>

    <div class="mt-2">
        <button id="scan-start" class="btn btn-primary btn-lg">{{ __('Start') }}</button>
        <button id="scan-stop" class="btn btn-secondary btn-lg">{{ __('Stop') }}</button>
    </div>

    <form id="scan-manual" class="mt-3">
        <input id="asset-tag" type="text" class="form-control form-control-lg" placeholder="{{ __('Enter asset tag') }}">
        <button type="submit" class="btn btn-primary btn-lg mt-2 w-100">{{ __('Go') }}</button>
    </form>

    <a href="{{ route('start') }}" class="btn btn-link mt-3">{{ __('Back to start') }}</a>
</div>
@stop

@section('moar_scripts')
<script src="{{ mix('js/dist/scan.js') }}"></script>
@stop

