@extends('layouts/default')

@section('title')
    {{ __('Scan Asset Or Component') }}
    @parent
@stop

@section('content')
<style>
    .scan-screen {min-height: 100vh; padding: 0.5rem 0.5rem 1rem;}
    .scan-shell {width: 100%; margin: 0 auto;}
    #scan-area {position: relative; width: 100%; aspect-ratio: 4 / 3; min-height: 280px; margin: 0 auto; background: #111; border-radius: 8px; overflow: hidden;}
    #scan-video,
    #scan-overlay {width: 100%; height: 100%; object-fit: contain; display: block;}
    .scan-actions {display: grid; grid-template-columns: repeat(auto-fit, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem;}
    .scan-actions .btn {padding: 1rem 1rem; font-size: 1.1rem;}
    .scan-actions select {padding: 1rem; font-size: 1.05rem;}
    #manual-section {max-width: 520px; margin: 1rem auto 0;}
    #scan-success {position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; color: #fff; background: rgba(15, 23, 42, 0.85); font-weight: 600; font-size: 1rem;}

    @media (max-width: 991px) {
        .scan-screen {padding: 0.35rem 0.35rem 0.85rem;}
        #scan-area {aspect-ratio: 3 / 4; min-height: clamp(380px, 68vh, 760px);}
    }

    @media (max-width: 575px) {
        #scan-area {min-height: clamp(420px, 72vh, 820px);}
    }
</style>
<div class="container-fluid py-3 scan-screen">
    <div class="scan-shell">
        <h1 class="h4 text-center mb-2">{{ __('Scan Asset Or Component') }}</h1>
        <p class="text-center text-muted" style="margin-bottom: 1.5rem;">
            {{ __('Point the camera at an asset tag or a tracked component QR label to open its detail page.') }}
        </p>

        <div id="scan-permission" class="alert alert-warning d-none" role="alert" data-testid="scan-permission-banner" style="display:none;">
            {{ trans('general.scan_camera_denied') }}
        </div>

        <div id="scan-area" class="shadow-sm">
            <video id="scan-video"
                   class="w-100 d-block"
                   data-testid="scan-video"
                   muted
                   playsinline></video>
            <canvas id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></canvas>
            <div id="scan-hint" class="position-absolute bottom-0 start-0 end-0 text-white bg-dark bg-opacity-50 py-2 px-3 small d-none" style="display:none;">
                {{ trans('general.scan_hint_move_closer') }}
            </div>
            <div id="scan-success" class="d-none" role="status" aria-live="polite" style="display:none;">
                {{ trans('general.loading') }}
            </div>
        </div>

        <div class="scan-actions">
            <button id="scan-switch" type="button" class="btn btn-outline-secondary" data-testid="scan-switch">
                <i class="fas fa-sync" aria-hidden="true"></i> {{ __('Refresh camera') }}
            </button>
            <button id="scan-torch" type="button" class="btn btn-outline-secondary" data-testid="scan-torch" aria-pressed="false">
                <i class="fas fa-lightbulb" aria-hidden="true"></i> {{ trans('general.scan_torch') }}
            </button>
            <button id="scan-request" type="button" class="btn btn-outline-secondary" data-testid="scan-request">
                <i class="fas fa-unlock" aria-hidden="true"></i> {{ __('Request camera access') }}
            </button>
            <div class="form-group mb-0">
                <label class="sr-only" for="scan-camera-select">{{ __('Select camera') }}</label>
                <select id="scan-camera-select" class="form-control" data-testid="scan-camera-select" aria-label="{{ __('Select camera') }}">
                    <option value="">{{ __('Select camera') }}</option>
                </select>
            </div>
        </div>

        <div id="scan-error" class="alert alert-danger d-none mt-3" role="alert" data-testid="scan-error" style="display:none;"></div>

    </div>
</div>
@stop

@section('moar_scripts')
<script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
