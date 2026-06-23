@extends('layouts/default')

@section('title')
    {{ trans('general.move_component_to_stock') }}
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
                    <h3 class="box-title">{{ trans('general.to_storage') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">{{ trans('general.tracked_component_stock_page_help') }}</p>

                    <div class="alert alert-info">
                        <strong>{{ trans('general.component') }}:</strong> {{ $component->display_name }}
                        <br><strong>{{ trans('general.status') }}:</strong> {{ \App\Models\ComponentInstance::lifecycleStatusLabel($component->effectiveLifecycleStatus()) ?? $component->effectiveLifecycleStatus() }}
                        <br><strong>{{ trans('general.condition') }}:</strong> {{ $component->displayConditionLabel() }}
                    </div>

                    <form method="POST" action="{{ route('components.move_to_stock', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        <div class="checkbox">
                            <label>
                                <input type="hidden" name="needs_verification" value="0">
                                <input type="checkbox" name="needs_verification" value="1" @checked(old('needs_verification'))>
                                {{ trans('general.mark_needs_verification_after_stock') }}
                            </label>
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_storage_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_storage_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-warning">{{ trans('general.confirm_to_stock') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
