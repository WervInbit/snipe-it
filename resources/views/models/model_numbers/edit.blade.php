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
            @php($codeCaseOverrideActive = old('code_case_override', 0) ? true : false)
            <div class="js-model-number-case-wrapper">
                <div class="input-group">
                    <input type="text" name="code" id="code" class="form-control js-uppercase-input" value="{{ old('code', $modelNumber->code) }}" required>
                    <span class="input-group-btn">
                        <button type="button" class="btn {{ $codeCaseOverrideActive ? 'btn-warning active' : 'btn-default' }} js-case-override-toggle" aria-pressed="{{ $codeCaseOverrideActive ? 'true' : 'false' }}" title="{{ __('Preserve case') }}">
                            Aa
                        </button>
                    </span>
                </div>
                <input type="hidden" name="code_case_override" class="js-case-override-input" value="{{ $codeCaseOverrideActive ? '1' : '0' }}">
            </div>
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

@endsection
@section('content')
    @parent

    @include('models.model_numbers.partials.images-manager', ['modelNumber' => $modelNumber])

    <div class="row" id="files">
        <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0">
            <div class="box box-default">
                <div class="box-header with-border">
                    <div class="box-heading">
                        <h2 class="box-title">{{ __('Files') }}</h2>
                        <div class="box-tools pull-right">
                            <a href="#" data-toggle="modal" data-target="#uploadFileModal" class="btn btn-default btn-sm">
                                <x-icon type="paperclip" /> {{ trans('button.upload') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <x-filestable object_type="model-numbers" :object="$modelNumber" />
                </div>
            </div>
        </div>
    </div>

    @include('modals.upload-file', ['item_type' => 'model-numbers', 'item_id' => $modelNumber->id])
@endsection

@section('moar_scripts')
    @parent
    @include('models.model_numbers.partials.case-override-script')
@endsection
