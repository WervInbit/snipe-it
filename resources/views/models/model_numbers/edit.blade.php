@extends('layouts/edit-form', [
    'createText' => __('Edit Model Number'),
    'updateText' => __('Edit Model Number'),
    'formAction' => route('models.numbers.update', [$model, $modelNumber]),
    'topSubmit' => true,
])

@section('inputFields')
    @csrf
    @method('PUT')

    <div class="form-group">
        <label class="col-md-3 control-label">{{ __('Model') }}</label>
        <div class="col-md-7">
            <p class="form-control-static">{{ $model->name }}</p>
        </div>
    </div>

    <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="code">{{ __('Code') }}</label>
        <div class="col-md-7">
            <input type="text" name="code" id="code" class="form-control" value="{{ old('code', $modelNumber->code) }}" required>
            {!! $errors->first('code', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="label">{{ __('Label') }}</label>
        <div class="col-md-7">
            <input type="text" name="label" id="label" class="form-control" value="{{ old('label', $modelNumber->label) }}" placeholder="{{ __('Optional human-friendly description') }}">
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('status') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="status">{{ __('Status') }}</label>
        <div class="col-md-7">
            <select name="status" id="status" class="form-control">
                @php($currentStatus = old('status', $modelNumber->isDeprecated() ? 'deprecated' : 'active'))
                <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="deprecated" {{ $currentStatus === 'deprecated' ? 'selected' : '' }}>{{ __('Deprecated') }}</option>
            </select>
            <span class="help-block">{{ __('Deprecated presets remain for legacy assets but are hidden from new selections.') }}</span>
            {!! $errors->first('status', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-7 col-md-offset-3">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="make_primary" value="1" {{ old('make_primary') ? 'checked' : '' }} {{ $modelNumber->isDeprecated() ? 'disabled' : '' }}>
                    {{ __('Make this the default selection for new assets.') }}
                </label>
                @if($modelNumber->isDeprecated())
                    <p class="help-block text-warning">{{ __('Restore this preset before making it primary.') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
