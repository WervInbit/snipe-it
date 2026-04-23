@extends('layouts/default')

@section('title')
    {{ __('Install Component') }}
    @parent
@stop

@section('header_right')
    <a href="{{ $returnTo }}" class="btn btn-default">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Install') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('Install this tracked component into an asset.') }}</p>

                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                        <br><strong>{{ trans('general.status') }}:</strong> {{ $component->status }}
                    </div>

                    <form method="POST" action="{{ route('components.install', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        <div class="form-group {{ $errors->has('asset_id') ? 'has-error' : '' }}">
                            <label for="component_install_asset_id">{{ __('Asset') }}</label>
                            <select class="form-control select2" aria-label="asset_id" name="asset_id" id="component_install_asset_id" style="width: 100%" required>
                                <option value="">{{ trans('general.select_asset') }}</option>
                                @foreach ($installableAssets as $asset)
                                    <option value="{{ $asset->id }}" @selected((string) old('asset_id') === (string) $asset->id)>
                                        {{ $asset->present()->fullName }}
                                    </option>
                                @endforeach
                            </select>
                            {!! $errors->first('asset_id', '<span class="help-block">:message</span>') !!}
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_install_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_install_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Confirm Install') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
