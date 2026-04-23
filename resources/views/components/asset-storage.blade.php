@extends('layouts/default')

@section('title')
    {{ __('Move Component To Stock') }}
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
                    <h3 class="box-title">{{ __('Move To Stock') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        @if($isExpected)
                            {{ __('This expected component will be materialized as a tracked part and moved directly into stock. You can assign a specific storage location later from the component detail page.') }}
                        @else
                            {{ __('Move this tracked component out of the device and into stock. You can assign a specific storage location later from the component detail page.') }}
                        @endif
                    </p>

                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $itemName }}
                    </div>

                    <form method="POST" action="{{ $postRoute }}">
                        @csrf
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="needs_verification" value="1" @checked(old('needs_verification'))>
                                {{ __('Mark as needing verification after moving to stock') }}
                            </label>
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="asset_component_storage_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="asset_component_storage_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-warning">{{ __('Confirm Move To Stock') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
