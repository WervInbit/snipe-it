@extends('layouts/default')

@section('title')
    {{ __('Scan Asset Tag') }}
    @parent
@stop

@section('content')
<style>
    #scan-area {
        max-width: 100%;
    }
    #scan-video,
    #scan-overlay {
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    .scan-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        gap: .5rem;
        margin-top: .75rem;
    }
</style>
<div class="container py-4">
    <div class="mx-auto" style="max-width:420px;">
        <h1 class="h4 text-center mb-4">{{ trans('general.scan_qr') }}</h1>

        <div id="scan-permission" class="alert alert-warning d-none" role="alert" data-testid="scan-permission-banner" style="display:none;">
            {{ trans('general.scan_camera_denied') }}
        </div>

        <div id="scan-area" class="position-relative rounded overflow-hidden shadow-sm">
            <video id="scan-video"
                   class="w-100 d-block"
                   data-testid="scan-video"
                   muted
                   playsinline></video>
            <canvas id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></canvas>
            <div id="scan-hint" class="position-absolute bottom-0 start-0 end-0 text-white bg-dark bg-opacity-50 py-2 px-3 small d-none" style="display:none;">
                {{ trans('general.scan_hint_move_closer') }}
            </div>
        </div>

        <div class="scan-actions">
            <button id="scan-switch" type="button" class="btn btn-outline-secondary flex-fill d-none" data-testid="scan-switch">
                <i class="fas fa-sync" aria-hidden="true"></i> {{ trans('general.scan_switch_camera') }}
            </button>
            <button id="scan-refocus" type="button" class="btn btn-outline-secondary flex-fill" data-testid="scan-refocus">
                <i class="fas fa-bullseye" aria-hidden="true"></i> {{ trans('general.scan_refocus') }}
            </button>
            <button id="scan-torch" type="button" class="btn btn-outline-secondary flex-fill d-none" data-testid="scan-torch" aria-pressed="false">
                <i class="fas fa-lightbulb" aria-hidden="true"></i> {{ trans('general.scan_torch') }}
            </button>
            <button id="manual-toggle" type="button" class="btn btn-outline-secondary flex-fill" data-testid="scan-manual-toggle">
                <i class="fas fa-keyboard" aria-hidden="true"></i> {{ trans('general.scan_manual_entry') }}
            </button>
        </div>

        <div id="scan-error" class="alert alert-danger d-none mt-3" role="alert" data-testid="scan-error" style="display:none;"></div>

        <form id="manual-form" class="mt-3 d-none" data-testid="scan-manual-form" style="display:none;">
            <label class="form-label visually-hidden" for="manual-tag">{{ trans('general.scan_manual_entry') }}</label>
            <input id="manual-tag"
                   type="text"
                   class="form-control form-control-lg"
                   placeholder="{{ trans('general.scan_manual_placeholder') }}"
                   autocomplete="off"
                   data-testid="scan-manual-input">
            <button type="submit" class="btn btn-primary btn-lg mt-3 w-100" data-testid="scan-manual-submit">
                {{ trans('general.search') }}
            </button>
        </form>
    </div>
</div>
@stop

@section('moar_scripts')
<script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
