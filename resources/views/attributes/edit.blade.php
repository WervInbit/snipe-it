@php($versionSource = $versionSource ?? null)
@php($isEdit = $definition->exists)
@php($isVersion = (bool) $versionSource)

@extends('layouts/edit-form', [
    'createText' => $isVersion ? __('Create Attribute Version') : __('Create Attribute'),
    'updateText' => __('Update Attribute'),
    'helpText' => __('Attributes become the reusable specification fields your models and assets rely on. Configure the datatype, constraints, and override rules here to drive presets, validation, and reporting.'),
    'helpPosition' => 'right',
    'formAction' => $isVersion ? route('attributes.versions.store', $versionSource) : ($isEdit ? route('attributes.update', $definition) : route('attributes.store')),
    'method' => $isVersion ? 'POST' : ($isEdit ? 'PUT' : 'POST'),
    'item' => $definition,
    'index_route' => 'attributes.index',
])

@section('inputFields')
    @if($versionSource)
        <div class="alert alert-info">
            {{ __('Creating a new version based on :label (:type). Key will remain :key.', ['label' => $versionSource->label, 'type' => ucfirst($versionSource->datatype), 'key' => $versionSource->key]) }}
        </div>
    @elseif($isEdit)
        <div class="alert alert-info">
            {{ __('Keys and datatypes are immutable. Use "New Version" to change the datatype or structure.') }}
        </div>
    @endif

    @if($isEdit && $definition->isDeprecated())
        <div class="alert alert-warning">
            {{ __('This attribute is deprecated. Existing assets will continue to read stored values, but new assignments should use a newer version.') }}
        </div>
    @endif

    @if($isEdit && $definition->isHidden())
        <div class="alert alert-default">
            {{ __('This attribute is hidden from selectors.') }}
        </div>
    @endif

    <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
        <label for="label" class="col-md-3 control-label">{{ __('Label') }}</label>
        <div class="col-md-7">
            <input type="text" class="form-control" name="label" id="label" value="{{ old('label', $definition->label ?: ($versionSource->label ?? null)) }}" required>
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
        <label for="key" class="col-md-3 control-label">{{ __('Key') }}</label>
        <div class="col-md-4">
            @if($isVersion)
                <input type="text" class="form-control" id="key" value="{{ $versionSource->key }}" readonly>
                <input type="hidden" name="key" value="{{ $versionSource->key }}">
            @elseif($isEdit)
                <input type="text" class="form-control" name="key" id="key" value="{{ $definition->key }}" readonly>
            @else
                <input type="text" class="form-control" name="key" id="key" value="{{ old('key', $definition->key) }}" required>
                <span class="help-block">{{ __('Used in API payloads and reports.') }}</span>
            @endif
            {!! $errors->first('key', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('datatype') ? ' has-error' : '' }}">
        <label for="datatype" class="col-md-3 control-label">{{ __('Datatype') }}</label>
        <div class="col-md-4">
            <select name="datatype" id="datatype" class="form-control" {{ $isEdit && !$isVersion ? 'disabled' : '' }}>
                @foreach(\App\Models\AttributeDefinition::DATATYPES as $type)
                    <option value="{{ $type }}" {{ old('datatype', $definition->datatype ?: ($versionSource->datatype ?? 'text')) === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            {!! $errors->first('datatype', '<span class="alert-msg">:message</span>') !!}
            @if($isEdit && !$isVersion)
                <input type="hidden" name="datatype" value="{{ $definition->datatype }}">
            @endif
        </div>
    </div>

    <div class="form-group{{ $errors->has('unit') ? ' has-error' : '' }}">
        <label for="unit" class="col-md-3 control-label">{{ __('Unit (optional)') }}</label>
        <div class="col-md-4">
            <input type="text" class="form-control" name="unit" id="unit" value="{{ old('unit', $definition->unit ?: ($versionSource->unit ?? null)) }}">
            {!! $errors->first('unit', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('category_ids') ? ' has-error' : '' }}">
        <label for="category_ids" class="col-md-3 control-label">{{ __('Category Scope') }}</label>
        <div class="col-md-7">
            @php($selectedCategories = old('category_ids', ($definition->categories ?? collect())->pluck('id')->toArray() ?: ($versionSource?->categories->pluck('id')->toArray() ?? [])))
            <select name="category_ids[]" id="category_ids" class="form-control select2" multiple>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>
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
                    <input type="checkbox" name="required_for_category" value="1" {{ old('required_for_category', $definition->required_for_category ?? ($versionSource->required_for_category ?? false)) ? 'checked' : '' }}>
                    <span>{{ __('Required for category') }}</span>
                </label>
            </div>
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="allow_asset_override" value="1" {{ old('allow_asset_override', $definition->allow_asset_override ?? ($versionSource->allow_asset_override ?? false)) ? 'checked' : '' }}>
                    <span>{{ __('Allow asset overrides') }}</span>
                </label>
            </div>
            <div class="checkbox">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="allow_custom_values" value="1" {{ old('allow_custom_values', $definition->allow_custom_values ?? ($versionSource->allow_custom_values ?? false)) ? 'checked' : '' }}>
                    <span>{{ __('Allow custom values (enum only)') }}</span>
                </label>
            </div>
        </div>
    </div>

    <hr>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ __('Constraints') }}</label>
        <div class="col-md-7">
            @php($constraintsSource = $definition->constraints ?: ($versionSource->constraints ?? []))
            <div class="row">
                <div class="col-sm-4">
                    <label for="constraints_min" class="control-label">{{ __('Minimum') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[min]" id="constraints_min" value="{{ old('constraints.min', $constraintsSource['min'] ?? null) }}">
                </div>
                <div class="col-sm-4">
                    <label for="constraints_max" class="control-label">{{ __('Maximum') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[max]" id="constraints_max" value="{{ old('constraints.max', $constraintsSource['max'] ?? null) }}">
                </div>
                <div class="col-sm-4">
                    <label for="constraints_step" class="control-label">{{ __('Step') }}</label>
                    <input type="number" step="any" class="form-control" name="constraints[step]" id="constraints_step" value="{{ old('constraints.step', $constraintsSource['step'] ?? null) }}">
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-sm-12">
                    <label for="constraints_regex" class="control-label">{{ __('Regex') }}</label>
                    <input type="text" class="form-control" name="constraints[regex]" id="constraints_regex" value="{{ old('constraints.regex', $constraintsSource['regex'] ?? null) }}" placeholder="{{ __('^([A-Z0-9-]+)$') }}">
                </div>
            </div>
        </div>
    </div>

@include('attributes.partials.options', ['definition' => $definition])
@endsection

@push('js')
@if($isVersion)
    <script nonce="{{ csrf_token() }}">
        (function () {
            var form = document.getElementById('create-form');
            if (!form || !window.history || !window.history.replaceState) {
                return;
            }
            form.addEventListener('submit', function () {
                try {
                    window.history.replaceState(null, '', '{{ route('attributes.index') }}');
                } catch (error) {
                    // Ignore history errors and allow submit.
                }
            });
        })();
    </script>
@endif
@endpush

