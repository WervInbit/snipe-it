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
            @php($codeCaseOverrideActive = old('code_case_override', 0) ? true : false)
            <div class="js-model-number-case-wrapper">
                <div class="input-group">
                    <input type="text" name="code" id="code" class="form-control js-uppercase-input" value="{{ old('code') }}" required>
                    <span class="input-group-btn">
                        <button type="button" class="btn {{ $codeCaseOverrideActive ? 'btn-warning active' : 'btn-default' }} js-case-override-toggle" aria-pressed="{{ $codeCaseOverrideActive ? 'true' : 'false' }}" title="{{ __('Preserve case') }}">
                            Aa
                        </button>
                    </span>
                </div>
                <input type="hidden" name="code_case_override" class="js-case-override-input" value="{{ $codeCaseOverrideActive ? '1' : '0' }}">
            </div>
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

@endsection

@section('moar_scripts')
    @parent
    @include('models.model_numbers.partials.case-override-script')
@endsection

