@extends('layouts/default')

@section('title')
    {{ __('Move Component Within Device') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('hardware.show', $asset) }}#components" class="btn btn-default">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Move Component Within Device') }}</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                        <div class="text-muted">{{ $component->component_tag }}</div>
                        <div>
                            <strong>{{ __('Current parent') }}:</strong>
                            {{ $component->parentComponent?->display_name ?: __('Asset root') }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('hardware.components.reparent.store', [$asset, $component]) }}">
                        @csrf

                        <div class="form-group {{ $errors->has('parent_component_instance_id') ? 'has-error' : '' }}">
                            <label for="parent_component_instance_id">{{ __('New parent') }}</label>
                            <select class="form-control" id="parent_component_instance_id" name="parent_component_instance_id">
                                <option value="">{{ __('Asset root') }}</option>
                                @foreach($parentCandidates as $candidate)
                                    <option value="{{ $candidate->id }}" @selected((string) old('parent_component_instance_id', $component->parent_component_instance_id) === (string) $candidate->id)>
                                        {{ $candidate->display_name }} - {{ $candidate->component_tag }}{{ $candidate->componentDefinition?->category ? ' - '.$candidate->componentDefinition->category->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            {!! $errors->first('parent_component_instance_id', '<span class="help-block">:message</span>') !!}
                            @if($parentCandidates->isEmpty())
                                <span class="help-block">{{ __('No other top-level components are attached to this asset.') }}</span>
                            @endif
                        </div>

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_reparent_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_reparent_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save Hierarchy') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
