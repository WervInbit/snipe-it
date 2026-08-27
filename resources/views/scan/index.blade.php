@extends('layouts/default')

@section('title')
    {{ trans('general.scan_asset_or_component') }}
    @parent
@stop

@section('content')
<style>
    .scan-screen {min-height: 100vh; padding: 0.5rem 0.5rem 1rem;}
    .scan-shell {width: 100%; margin: 0 auto;}
    #scan-area {position: relative; width: 100%; aspect-ratio: 4 / 3; min-height: 280px; margin: 0 auto; background: #111; border-radius: 8px; overflow: hidden;}
    #scan-video,
    #scan-overlay {position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: block;}
    #scan-overlay {z-index: 2; pointer-events: none;}
    .scan-actions {display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; margin-top: 1rem;}
    .scan-actions .btn {padding: 1rem 1rem; font-size: 1.1rem;}
    .scan-actions select {padding: 1rem; font-size: 1.05rem;}
    #manual-section {max-width: 520px; margin: 1rem auto 0;}
    #scan-hint {position: absolute; right: 0; bottom: 0; left: 0; z-index: 4; padding: .75rem 1rem; color: #fff; background: rgba(0, 0, 0, .68);}
    #scan-success {position: absolute; inset: 0; z-index: 5; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; color: #fff; background: rgba(15, 23, 42, 0.85); font-weight: 600; font-size: 1rem;}
    .scan-frame-guide {position: absolute; inset: 18% 12%; z-index: 3; border: 3px solid rgba(255, 255, 255, .75); border-radius: 12px; box-shadow: 0 0 0 999px rgba(0, 0, 0, .12); pointer-events: none;}
    #scan-area[data-scan-state="no-code"] .scan-frame-guide {border-color: #f0ad4e;}
    #scan-area[data-scan-state="success"] .scan-frame-guide {border-color: #5cb85c;}
    #scan-status {margin-top: 1rem; margin-bottom: 0;}

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
        <h1 class="h4 text-center mb-2">{{ trans('general.scan_asset_or_component') }}</h1>
        <p class="text-center text-muted" style="margin-bottom: 1.5rem;">
            {{ trans('general.scan_asset_or_component_help') }}
        </p>

        <div id="scan-permission" class="alert alert-warning d-none" role="alert" data-testid="scan-permission-banner" style="display:none;">
            <span id="scan-permission-text">{{ trans('general.scan_camera_permission_help') }}</span>
        </div>

        <div id="scan-area" class="shadow-sm" data-scan-state="starting">
            <video id="scan-video"
                   class="w-100 d-block"
                   data-testid="scan-video"
                   muted
                   playsinline></video>
            <canvas id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></canvas>
            <div class="scan-frame-guide" aria-hidden="true"></div>
            <div id="scan-hint" class="position-absolute bottom-0 start-0 end-0 text-white bg-dark bg-opacity-50 py-2 px-3 small d-none" style="display:none;">
                {{ trans('general.scan_hint_move_closer') }}
            </div>
            <div id="scan-success" class="d-none" role="status" aria-live="polite" style="display:none;">
                {{ trans('general.scan_status_success') }}
            </div>
        </div>

        <div id="scan-status" class="alert alert-info" role="status" aria-live="polite" aria-atomic="true" data-state="starting" data-testid="scan-status">
            <span id="scan-status-text">{{ trans('general.scan_status_starting') }}</span>
        </div>

        <div class="scan-actions">
            <button id="scan-switch" type="button" class="btn btn-outline-secondary" data-testid="scan-switch">
                <i class="fas fa-sync" aria-hidden="true"></i> {{ trans('general.scan_retry_camera') }}
            </button>
            <button id="scan-refocus" type="button" class="btn btn-outline-secondary" data-testid="scan-refocus" disabled>
                <i class="fas fa-bullseye" aria-hidden="true"></i> {{ trans('general.scan_refocus') }}
            </button>
            <button id="scan-torch" type="button" class="btn btn-outline-secondary" data-testid="scan-torch" aria-pressed="false" disabled>
                <i class="fas fa-lightbulb" aria-hidden="true"></i> {{ trans('general.scan_torch') }}
            </button>
            <button id="scan-request" type="button" class="btn btn-outline-secondary" data-testid="scan-request">
                <i class="fas fa-unlock" aria-hidden="true"></i> {{ trans('general.scan_request_camera_access') }}
            </button>
            <div class="form-group mb-0">
                <label class="sr-only" for="scan-camera-select">{{ trans('general.scan_select_camera') }}</label>
                <select id="scan-camera-select" class="form-control" data-testid="scan-camera-select" aria-label="{{ trans('general.scan_select_camera') }}" disabled>
                    <option value="">{{ trans('general.scan_select_camera') }}</option>
                </select>
            </div>
        </div>

        <div id="scan-error" class="alert alert-danger d-none mt-3" role="alert" data-testid="scan-error" style="display:none;"></div>

        <form id="manual-section"
              method="GET"
              action="{{ route('scan.lookup') }}"
              data-testid="scan-manual-form">
            <label for="scan-manual-code">{{ trans('general.scan_manual_entry') }}</label>
            <div class="input-group">
                <input id="scan-manual-code"
                       name="code"
                       type="search"
                       class="form-control"
                       maxlength="255"
                       required
                       autocomplete="off"
                       autocapitalize="off"
                       spellcheck="false"
                       placeholder="{{ trans('general.scan_manual_placeholder') }}"
                       aria-describedby="scan-manual-help"
                       data-testid="scan-manual-input">
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-primary" data-testid="scan-manual-submit">
                        {{ trans('general.search') }}
                    </button>
                </span>
            </div>
            <p id="scan-manual-help" class="help-block">{{ trans('general.scan_manual_help') }}</p>
        </form>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    window.scanConfig = Object.assign({}, window.scanConfig || {}, {
        text: {
            selectCamera: @json(trans('general.scan_select_camera')),
            cameraLabel: @json(trans('general.camera')),
            cameraAccessFailed: @json(trans('general.scan_camera_access_failed')),
            cameraUnavailable: @json(trans('general.scan_camera_unavailable')),
            cameraBusy: @json(trans('general.scan_camera_busy')),
            permissionHelp: @json(trans('general.scan_camera_permission_help')),
            statusStarting: @json(trans('general.scan_status_starting')),
            statusScanning: @json(trans('general.scan_status_scanning')),
            statusNoCode: @json(trans('general.scan_status_no_code')),
            statusRetrying: @json(trans('general.scan_status_retrying')),
            statusRefocusing: @json(trans('general.scan_status_refocusing')),
            statusSuccess: @json(trans('general.scan_status_success')),
            statusPaused: @json(trans('general.scan_status_paused')),
            navigationFailed: @json(trans('general.scan_navigation_failed'))
        }
    });
</script>
<script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
