@extends('layouts/default')

@section('title')
    {{ trans('general.move_component_to_stock') }}
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
            ? route('hardware.components.expected.storage.store', [$asset, $template])
            : route('hardware.components.storage.store', [$asset, $component]);
    @endphp

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('general.move_to_stock') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        @if($isExpected)
                            {{ trans('general.expected_component_stock_help') }}
                        @else
                            {{ trans('general.tracked_component_stock_help') }}
                        @endif
                    </p>

                    <div class="alert alert-info">
                        <strong>{{ trans('general.component') }}:</strong> {{ $itemName }}
                    </div>

                    <form method="POST" action="{{ $postRoute }}">
                        @csrf
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="needs_verification" value="1" @checked(old('needs_verification'))>
                                {{ trans('general.mark_needs_verification_after_stock') }}
                            </label>
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="asset_component_storage_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="asset_component_storage_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-warning">{{ trans('general.confirm_move_to_stock') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
