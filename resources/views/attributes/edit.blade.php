@php($isEdit = $definition->exists)

@extends('layouts/edit-form', [
    'createText' => __('Create Attribute'),
    'updateText' => __('Update Attribute'),
    'helpText' => __('Attributes become the reusable specification fields your models and assets rely on. Configure the datatype, constraints, and override rules here to drive presets, validation, and reporting.'),
    'helpPosition' => 'right',
    'formAction' => $isEdit ? route('attributes.update', $definition) : route('attributes.store'),
    'method' => $isEdit ? 'PUT' : 'POST',
    'item' => $definition,
])

@section('inputFields')
    <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
        <label for="label" class="col-md-3 control-label">{{ __('Label') }}</label>
        <div class="col-md-7">
            <input type="text" class="form-control" name="label" id="label" value="{{ old('label', $definition->label) }}" required>
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
        <label for="key" class="col-md-3 control-label">{{ __('Key') }}</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="key" id="key" value="{{ old('key', $definition->key) }}" {{ $isEdit ? 'readonly' : 'required' }}>
            <span class="help-block">{{ __('Used in API payloads and reports.') }}</span>
            {!! $errors->first('key', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('datatype') ? ' has-error' : '' }}">
        <label for="datatype" class="col-md-3 control-label">{{ __('Datatype') }}</label>
        <div class="col-md-4">
            <select name="datatype" id="datatype" class="form-control" {{ $isEdit ? 'disabled' : '' }}>
                @foreach(\App\Models\AttributeDefinition::DATATYPES as $type)
                    <option value="{{ $type }}" {{ old('datatype', $definition->datatype ?? 'text') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            {!! $errors->first('datatype', '<span class="alert-msg">:message</span>') !!}
            @if($isEdit)
                <input type="hidden" name="datatype" value="{{ $definition->datatype }}">
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('unit') ? ' has-error' : '' }}">
        <label for="unit" class="col-md-3 control-label">{{ __('Unit (optional)') }}</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="unit" id="unit" value="{{ old('unit', $definition->unit) }}">
            {!! $errors->first('unit', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('category_ids') ? ' has-error' : '' }}">
        <label for="category_ids" class="col-md-3 control-label">{{ __('Category Scope') }}</label>
        <div class="col-md-7">
            <select name="category_ids[]" id="category_ids" class="form-control select2" multiple>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ in_array($category->id, old('category_ids', $definition->categories->pluck('id')->toArray())) ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <span class="help-block">{{ __('Leave blank to apply to every asset category.') }}</span>
            {!! $errors->first('category_ids', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-7 col-md-offset-3">
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="required_for_category" value="1" {{ old('required_for_category', $definition->required_for_category) ? 'checked' : '' }}>
                    <span>{{ __('Required for category') }}</span>
                </label>
            </div>
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="needs_test" value="1" {{ old('needs_test', $definition->needs_test) ? 'checked' : '' }}>
                    <span>{{ __('Needs test item') }}</span>
                </label>
            </div>
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="allow_asset_override" value="1" {{ old('allow_asset_override', $definition->allow_asset_override) ? 'checked' : '' }}>
                    <span>{{ __('Allow asset overrides') }}</span>
                </label>
            </div>
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="allow_custom_values" value="1" {{ old('allow_custom_values', $definition->allow_custom_values) ? 'checked' : '' }}>
                    <span>{{ __('Allow custom values (enum only)') }}</span>
                </label>
            </div>
        </div>
    </div>

    <hr>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ __('Constraints') }}</label>
        <div class="col-md-7">
            <div class="row">
                <div class="col-sm-4">
                    <label for="constraints_min" class="control-label">{{ __('Minimum') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[min]" id="constraints_min" value="{{ old('constraints.min', $definition->constraints['min'] ?? null) }}">
                </div>
                <div class="col-sm-4">
                    <label for="constraints_max" class="control-label">{{ __('Maximum') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[max]" id="constraints_max" value="{{ old('constraints.max', $definition->constraints['max'] ?? null) }}">
                </div>
                <div class="col-sm-4">
                    <label for="constraints_step" class="control-label">{{ __('Step') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[step]" id="constraints_step" value="{{ old('constraints.step', $definition->constraints['step'] ?? null) }}">
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-sm-12">
                    <label for="constraints_regex" class="control-label">{{ __('Regex') }}</label>
                    <input type="text" class="form-control" name="constraints[regex]" id="constraints_regex" value="{{ old('constraints.regex', $definition->constraints['regex'] ?? null) }}" placeholder="{{ __('^([A-Z0-9-]+)$') }}">
                </div>
            </div>
        </div>
    </div>

    @if($isEdit && $definition->isEnum())
        <hr>
        @include('attributes.partials.options', ['definition' => $definition])
    @endif
@endsection
