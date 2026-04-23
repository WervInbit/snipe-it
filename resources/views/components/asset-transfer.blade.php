@extends('layouts/default')

@section('title')
    {{ __('Move Component To Other Device') }}
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
        });
    </script>
    <style nonce="{{ csrf_token() }}">
        .scan-shell {width: 100%; margin: 0 auto;}
        #scan-area {position: relative; width: 100%; aspect-ratio: 4 / 3; min-height: 280px; margin: 0 auto; background: #111; border-radius: 8px; overflow: hidden;}
        #scan-video,
        #scan-overlay {width: 100%; height: 100%; object-fit: contain; display: block;}
        .scan-actions {display: grid; grid-template-columns: repeat(auto-fit, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem;}
        .scan-actions .btn {padding: 1rem 1rem; font-size: 1rem;}
        .scan-actions select {padding: 1rem; font-size: 1rem;}
        #scan-success {position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; color: #fff; background: rgba(15, 23, 42, 0.85); font-weight: 600; font-size: 1rem;}
    </style>

    <div class="row">
        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Scan Destination Asset') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        {{ __('Scan the destination asset QR label to prefill the move form below. You can also choose the destination manually.') }}
                    </p>
                    <div id="scan-permission" class="alert alert-warning d-none" role="alert" data-testid="scan-permission-banner" style="display:none;">
                        {{ trans('general.scan_camera_denied') }}
                    </div>

                    <div class="scan-shell">
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
            </div>
        </div>

        <div class="col-md-5">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Move To Other Device') }}</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $itemName }}
                    </div>

                    @if($isExpected)
                        <p class="text-muted">{{ __('This expected component will be materialized as a tracked part and moved directly to the destination asset.') }}</p>
                    @endif

                    <form method="POST" action="{{ $postRoute }}">
                        @csrf
                        <div class="form-group {{ $errors->has('destination_asset_id') ? 'has-error' : '' }}">
                            <label for="asset_component_destination">{{ __('Destination Asset') }}</label>
                            <select class="js-data-ajax select2"
                                    data-endpoint="hardware"
                                    data-placeholder="{{ __('Search assets') }}"
                                    aria-label="destination_asset_id"
                                    name="destination_asset_id"
                                    style="width: 100%"
                                    id="asset_component_destination"
                                    required>
                                <option value="">{{ __('Search assets') }}</option>
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

                        <button type="submit" class="btn btn-primary">{{ __('Move To Other Device') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('moar_scripts')
    <script src="{{ mix('js/dist/scan.js') }}"></script>
@stop
