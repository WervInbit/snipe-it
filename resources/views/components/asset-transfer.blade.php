@extends('layouts/default')

@section('title')
    {{ trans('general.move_component_to_other_device') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('hardware.show', $asset) }}#components" class="btn btn-default">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    @php
        $isExpected = isset($template) && $template;
        $itemName = $isExpected ? ($template->expected_name ?: $template->componentDefinition?->name) : $component->display_name;
        $postRoute = $isExpected
            ? route('hardware.components.expected.transfer.store', [$asset, $template])
            : route('hardware.components.transfer.store', [$asset, $component]);
    @endphp

    <script nonce="{{ csrf_token() }}">
        window.scanConfig = Object.assign({}, window.scanConfig || {}, {
            resolveBasePath: '{{ url('/scan/resolve') }}',
            resolveQuery: @json($scanQuery),
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
    <style nonce="{{ csrf_token() }}">
        .scan-shell {width: 100%; margin: 0 auto;}
        #scan-area {position: relative; width: 100%; aspect-ratio: 4 / 3; min-height: 280px; margin: 0 auto; background: #111; border-radius: 8px; overflow: hidden;}
        #scan-video,
        #scan-overlay {position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: block;}
        #scan-overlay {z-index: 2; pointer-events: none;}
        .scan-actions {display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; margin-top: 1rem;}
        .scan-actions .btn {padding: 1rem 1rem; font-size: 1rem;}
        .scan-actions select {padding: 1rem; font-size: 1rem;}
        #scan-hint {position: absolute; right: 0; bottom: 0; left: 0; z-index: 4; padding: .75rem 1rem; color: #fff; background: rgba(0, 0, 0, .68);}
        #scan-success {position: absolute; inset: 0; z-index: 5; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; color: #fff; background: rgba(15, 23, 42, 0.85); font-weight: 600; font-size: 1rem;}
        .scan-frame-guide {position: absolute; inset: 18% 12%; z-index: 3; border: 3px solid rgba(255, 255, 255, .75); border-radius: 12px; box-shadow: 0 0 0 999px rgba(0, 0, 0, .12); pointer-events: none;}
        #scan-area[data-scan-state="no-code"] .scan-frame-guide {border-color: #f0ad4e;}
        #scan-area[data-scan-state="success"] .scan-frame-guide {border-color: #5cb85c;}
        #scan-status {margin-top: 1rem; margin-bottom: 0;}
    </style>

    <div class="row">
        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('general.scan_destination_asset') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        {{ trans('general.scan_destination_asset_help') }}
                    </p>
                    <div id="scan-permission" class="alert alert-warning d-none" role="alert" data-testid="scan-permission-banner" style="display:none;">
                        <span id="scan-permission-text">{{ trans('general.scan_camera_permission_help') }}</span>
                    </div>

                    <div class="scan-shell">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('general.move_to_other_device') }}</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <strong>{{ trans('general.component') }}:</strong> {{ $itemName }}
                    </div>

                    @if($isExpected)
                        <p class="text-muted">{{ trans('general.expected_component_transfer_help') }}</p>
                    @endif

                    <form method="POST" action="{{ $postRoute }}">
                        @csrf
                        @include('components.partials.condition-warning-confirmation', [
                            'component' => $isExpected ? null : $component,
                            'conditionStatus' => $isExpected ? \App\Models\ComponentInstance::CONDITION_STATUS_NEEDS_ATTENTION : null,
                            'show' => $isExpected || (!$isExpected && $component->requiresConditionWarningForAttachment()),
                            'message' => $isExpected
                                ? trans('general.expected_component_attention_warning')
                                : null,
                            'checkboxLabel' => trans('general.confirm_condition_warning_move'),
                        ])
                        @include('components.partials.lifecycle-warning-confirmation', [
                            'component' => $isExpected ? null : $component,
                            'show' => !$isExpected && $component->requiresLifecycleWarningForAttachment(),
                            'checkboxLabel' => trans('general.confirm_lifecycle_warning_move'),
                        ])
                        <div class="form-group {{ $errors->has('destination_asset_id') ? 'has-error' : '' }}">
                            <label for="asset_component_destination">{{ trans('general.destination_asset') }}</label>
                            <select class="js-data-ajax select2"
                                    data-endpoint="hardware"
                                    data-placeholder="{{ trans('general.search_assets') }}"
                                    aria-label="destination_asset_id"
                                    name="destination_asset_id"
                                    style="width: 100%"
                                    id="asset_component_destination"
                                    required>
                                <option value="">{{ trans('general.search_assets') }}</option>
                                @if($destinationAsset)
                                    <option value="{{ $destinationAsset->id }}" selected="selected">{{ $destinationAsset->present()->fullName }}</option>
                                @elseif(old('destination_asset_id'))
                                    @php($selectedDestination = \App\Models\Asset::find(old('destination_asset_id')))
                                    @if($selectedDestination)
                                        <option value="{{ $selectedDestination->id }}" selected="selected">{{ $selectedDestination->present()->fullName }}</option>
                                    @endif
                                @endif
                            </select>
                            {!! $errors->first('destination_asset_id', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="asset_component_transfer_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="asset_component_transfer_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-primary">{{ trans('general.move_to_other_device') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('moar_scripts')
    <script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
