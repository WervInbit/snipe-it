@extends('layouts/edit-form', [
    'createText' => __('Add Model Number'),
    'updateText' => __('Add Model Number'),
    'formAction' => route('models.numbers.store', $model),
    'topSubmit' => true,
])

@section('inputFields')
    @csrf

    <div class="form-group">
        <label class="col-md-3 control-label">{{ __('Model') }}</label>
        <div class="col-md-7">
            <p class="form-control-static">{{ $model->name }}</p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="code">{{ __('Code') }}</label>
        <div class="col-md-7">
            <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required>
            <span class="help-block">{{ __('Identifier for this preset (e.g., SKU or hardware variant code).') }}</span>
            {!! $errors->first('code', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="label">{{ __('Label') }}</label>
        <div class="col-md-7">
            <input type="text" name="label" id="label" class="form-control" value="{{ old('label') }}" placeholder="{{ __('Optional human-friendly description') }}">
            <span class="help-block">{{ __('Describe the variant shown to refurbishers (for example: CPU, RAM, storage).') }}</span>
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-7 col-md-offset-3">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="make_primary" value="1" {{ old('make_primary', $hasExistingNumbers ? null : '1') ? 'checked' : '' }}>
                    {{ __('Make this the default selection for new assets.') }}
                </label>
            </div>
        </div>
    </div>
@endsection

