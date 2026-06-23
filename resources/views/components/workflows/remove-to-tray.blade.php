@extends('layouts/default')

@section('title')
    {{ trans('general.move_component_to_tray') }}
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
                    <h3 class="box-title">{{ trans('general.to_tray') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ trans('general.remove_component_to_tray_help') }}</p>

                    <div class="alert alert-info">
                        <strong>{{ trans('general.component') }}:</strong> {{ $component->display_name }}
                        @if($component->currentAsset)
                            <br><strong>{{ trans('general.current_asset') }}:</strong> {{ $component->currentAsset->present()->name() }}
                        @endif
                    </div>

                    <form method="POST" action="{{ route('components.remove_to_tray', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        @include('components.partials.serial-change-control', [
                            'component' => $component,
                            'serialId' => 'component_remove_serial',
                        ])

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_remove_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_remove_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-warning">{{ trans('general.confirm_to_tray') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
