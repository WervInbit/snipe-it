@extends('layouts/default')

@section('title')
    {{ $mode === 'destroyed' ? __('Mark Component Destroyed') : __('Mark Destruction Pending') }}
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
                    <h3 class="box-title">{{ $mode === 'destroyed' ? __('Mark Destroyed') : __('Mark Destruction Pending') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        @if($mode === 'destroyed')
                            {{ __('Complete destruction for this component and keep the event history intact.') }}
                        @else
                            {{ __('Flag this component so it is clearly queued for destruction.') }}
                        @endif
                    </p>

                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                        <br><strong>{{ trans('general.status') }}:</strong> {{ $component->status }}
                    </div>

                    <form method="POST" action="{{ $mode === 'destroyed' ? route('components.mark_destroyed', $component) : route('components.mark_destruction_pending', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        @if($mode === 'pending')
                            <div class="form-group {{ $errors->has('storage_location_id') ? 'has-error' : '' }}">
                                <label for="component_destruction_location_id">{{ __('Destruction Location') }}</label>
                                <select class="form-control" id="component_destruction_location_id" name="storage_location_id">
                                    <option value="">{{ __('No location selected') }}</option>
                                    @foreach ($destructionLocations as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('storage_location_id', $component->storage_location_id) === (string) $location->id)>
                                            {{ $location->name }}
                                            @if ($location->siteLocation)
                                                - {{ $location->siteLocation->name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                {!! $errors->first('storage_location_id', '<span class="help-block">:message</span>') !!}
                            </div>
                        @endif

                        <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                            <label for="component_destruction_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_destruction_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn btn-danger">
                            {{ $mode === 'destroyed' ? __('Confirm Destroyed') : __('Mark Destruction Pending') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
