@extends('layouts/default')

@section('title')
    {{ __('Move Component To Tray') }}
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
                    <h3 class="box-title">{{ __('To Tray') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ __('Remove this component from its current asset and place it in your tray.') }}</p>

                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                        @if($component->currentAsset)
                            <br><strong>{{ __('Current Asset') }}:</strong> {{ $component->currentAsset->present()->name() }}
                        @endif
                    </div>

                    <form method="POST" action="{{ route('components.remove_to_tray', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_remove_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_remove_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-warning">{{ __('Confirm To Tray') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
