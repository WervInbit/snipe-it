@extends('layouts/default')

@section('title')
{{ __('Register Component') }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.tray') }}" class="btn btn-default">
    {{ __('My Tray') }}
</a>
<a href="{{ route('components.index') }}" class="btn btn-default">
    {{ trans('general.back') }}
</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Register Loose Component') }}</h3>
            </div>
            <form method="POST" action="{{ route('components.store') }}">
                <div class="box-body">
                    @csrf
                    <p class="text-muted">
                        {{ __('Use this form for manual stock intake only. For removals from an asset, use the asset Components tab.') }}
                    </p>

                    @include('components.partials.manual-fields', [
                        'componentDefinitions' => $componentDefinitions,
                        'stockLocations' => $stockLocations,
                        'sourceTypeOptions' => $sourceTypeOptions,
                        'conditionOptions' => $conditionOptions,
                        'showSourceType' => true,
                        'showStorageLocation' => true,
                    ])
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">{{ __('Register Component') }}</button>
                    <a href="{{ route('components.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
