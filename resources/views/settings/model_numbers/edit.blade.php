@extends('layouts/edit-form', [
    'createText' => __('Edit Model Number'),
    'updateText' => __('Edit Model Number'),
    'formAction' => route('settings.model_numbers.update', $modelNumber),
    'topSubmit' => true,
])

@section('inputFields')
    @csrf
    @method('PUT')

    <div class="form-group">
        <label class="col-md-3 control-label" for="model_id">{{ __('Model') }}</label>
        <div class="col-md-7">
            <select name="model_id" id="model_id" class="form-control select2" disabled>
                @foreach($models as $modelId => $modelName)
                    <option value="{{ $modelId }}" {{ (int) $selectedModelId === (int) $modelId ? 'selected' : '' }}>{{ $modelName }}</option>
                @endforeach
            </select>
            <span class="help-block">{{ __('Model numbers are bound to the original model.') }}</span>
        </div>
    </div>

    <div class="form-group{{ $errors->has('code') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="code">{{ __('Code') }}</label>
        <div class="col-md-7">
            <input type="text" name="code" id="code" class="form-control" value="{{ old('code', $modelNumber->code) }}" required>
            <span class="help-block">{{ __('Canonical identifier for this preset (e.g., SKU or variant code).') }}</span>
            {!! $errors->first('code', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="label">{{ __('Label') }}</label>
        <div class="col-md-7">
            <input type="text" name="label" id="label" class="form-control" value="{{ old('label', $modelNumber->label) }}" placeholder="{{ __('Optional human-friendly description') }}">
            <span class="help-block">{{ __('Describe the hardware variant shown to refurbishers (e.g., CPU/RAM/storage).') }}</span>
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('status') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label" for="status">{{ __('Status') }}</label>
        <div class="col-md-7">
            <select name="status" id="status" class="form-control">
                <option value="active" {{ old('status', $modelNumber->isDeprecated() ? 'deprecated' : 'active') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="deprecated" {{ old('status', $modelNumber->isDeprecated() ? 'deprecated' : 'active') === 'deprecated' ? 'selected' : '' }}>{{ __('Deprecated') }}</option>
            </select>
            <span class="help-block">{{ __('Deprecated presets remain visible to existing assets but are hidden from new selections.') }}</span>
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
                    <p class="help-block text-warning">{{ __('Restore the preset before making it primary.') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            $('.select2').select2();
        });
    </script>
@endpush
