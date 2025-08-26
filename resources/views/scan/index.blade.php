@extends('layouts/default')

@section('title')
{{ __('Scan Assets') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12 text-center">
        <video id="scan-video" style="width:100%;max-width:400px;" autoplay></video>
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
