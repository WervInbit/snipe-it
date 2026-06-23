@extends('layouts/default')

@section('title')
{{ trans('general.register_component') }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.tray') }}" class="btn btn-default">
    {{ trans('general.my_tray') }}
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
                <h3 class="box-title">{{ trans('general.register_loose_component') }}</h3>
            </div>
            <form method="POST" action="{{ route('components.store') }}">
                <div class="box-body">
                    @csrf
                    <p class="text-muted">
                        {{ trans('general.register_loose_component_help') }}
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
                    <button type="submit" class="btn btn-primary">{{ trans('general.register_component') }}</button>
                    <a href="{{ route('components.index') }}" class="btn btn-default">{{ trans('general.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
