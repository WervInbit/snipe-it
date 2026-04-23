@extends('layouts/default')

@section('title')
    {{ $mode === 'confirm' ? __('Confirm Verification') : __('Needs Verification') }}
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
                    <h3 class="box-title">{{ $mode === 'confirm' ? __('Confirm Verification') : __('Needs Verification') }}</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        @if($mode === 'confirm')
                            {{ __('Confirm that this component has been verified and place it in stock.') }}
                        @else
                            {{ __('Flag this component so it is clearly marked for verification.') }}
                        @endif
                    </p>

                    <div class="alert alert-info">
                        <strong>{{ __('Component') }}:</strong> {{ $component->display_name }}
                        <br><strong>{{ trans('general.status') }}:</strong> {{ $component->status }}
                    </div>

                    <form method="POST" action="{{ $mode === 'confirm' ? route('components.confirm_verification', $component) : route('components.flag_needs_verification', $component) }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">

                        @if($mode === 'confirm')
                            <div class="form-group {{ $errors->has('storage_location_id') ? 'has-error' : '' }}">
                                <label for="component_confirm_location_id">{{ __('Stock Location') }}</label>
                                <select class="form-control" id="component_confirm_location_id" name="storage_location_id" required>
                                    <option value="">{{ __('Choose a location') }}</option>
                                    @foreach ($stockLocations as $location)
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
                        @else
                            <div class="form-group {{ $errors->has('storage_location_id') ? 'has-error' : '' }}">
                                <label for="component_flag_location_id">{{ __('Verification Location') }}</label>
                                <select class="form-control" id="component_flag_location_id" name="storage_location_id">
                                    <option value="">{{ __('Keep current location') }}</option>
                                    @foreach ($verificationLocations as $location)
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
                            <label for="component_verification_note">{{ trans('general.notes') }}</label>
                            <textarea class="form-control" id="component_verification_note" name="note" rows="4">{{ old('note') }}</textarea>
                            {!! $errors->first('note', '<span class="help-block">:message</span>') !!}
                        </div>

                        <button type="submit" class="btn {{ $mode === 'confirm' ? 'btn-success' : 'btn-warning' }}">
                            {{ $mode === 'confirm' ? __('Confirm Verification') : __('Mark Needs Verification') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
