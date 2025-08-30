@extends('layouts/default')

@section('title')
{{ __('Scan Assets') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12 text-center">
        <video id="scan-video" playsinline muted style="width:100%;max-width:400px;" autoplay></video>
        <div class="row" style="margin-top:10px; gap:10px; justify-content:center;">
            <button id="scan-start" type="button" class="btn btn-success" onclick="window.snipeStartScan && window.snipeStartScan()">Start Camera</button>
            <button id="scan-stop" type="button" class="btn btn-default" onclick="window.snipeStopScan && window.snipeStopScan()">Stop</button>
            <select id="camera-select" class="form-control" style="max-width:260px; display:inline-block;"></select>
        </div>
        <p id="scan-status" class="text-muted" style="margin-top:6px;"></p>
        <p id="scan-error" class="text-danger" style="display:none;margin-top:8px;"></p>

        <div class="panel panel-default" style="margin-top:15px; text-align:left;">
            <div class="panel-heading">Diagnostics</div>
            <div class="panel-body">
                <div style="margin-bottom:8px; display:flex; gap:8px; flex-wrap:wrap;">
                    <button id="scan-diagnostics" type="button" class="btn btn-info btn-sm">Run Diagnostics</button>
                    <button id="scan-request-perm" type="button" class="btn btn-warning btn-sm">Request Permission Now</button>
                </div>
                <ul id="scan-diag-list" style="padding-left:18px;">
                    <li><strong>Secure Context</strong>: <span id="diag-secure">n/a</span></li>
                    <li><strong>Origin</strong>: <span id="diag-origin">n/a</span></li>
                    <li><strong>Camera Permission</strong>: <span id="diag-perm">n/a</span></li>
                    <li><strong>MediaDevices</strong>: <span id="diag-mediadev">n/a</span></li>
                    <li><strong>Video Inputs</strong>: <span id="diag-videos">n/a</span></li>
                </ul>
                <pre id="scan-diag-log" style="max-height:180px; overflow:auto; background:#f7f7f7; padding:8px; border:1px solid #ddd;"></pre>
            </div>
        </div>
        <form id="scan-manual" class="form-inline" style="margin-top:15px;">
            <div class="form-group">
                <label class="sr-only" for="asset-tag">{{ trans('general.asset_tag') }}</label>
                <input type="text" class="form-control" id="asset-tag" placeholder="{{ trans('general.asset_tag') }}">
            </div>
            <button type="submit" class="btn btn-primary">Go</button>
        </form>
    </div>
</div>
@stop

@section('moar_scripts')
<script src="{{ url(mix('js/dist/scan.js')) }}"></script>
@stop
