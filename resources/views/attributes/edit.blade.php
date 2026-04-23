@php($isEdit = $definition->exists)
@php($createManualKeyOverride = !$isEdit ? (bool) old('manual_key_override', false) : false)
@php($createOldLabel = !$isEdit ? trim((string) old('label', $definition->label)) : '')
@php($createOldKey = !$isEdit ? trim((string) old('key', $definition->key)) : '')
@php($createKeyValue = $createOldKey !== '' ? $createOldKey : ($createOldLabel !== '' ? \App\Models\AttributeDefinition::normalizeKeySource($createOldLabel) : ''))
@php($selectedCategories = old('category_ids', ($definition->categories ?? collect())->pluck('id')->toArray()))

@extends('layouts/edit-form', [
    'createText' => __('Create Attribute'),
    'updateText' => __('Update Attribute'),
    'helpText' => __('Attributes become the reusable specification fields your models, components, assets, tests, and reports rely on. Configure the datatype, constraints, and override rules here.'),
    'helpPosition' => 'right',
    'formAction' => $isEdit ? route('attributes.update', $definition) : route('attributes.store'),
    'method' => $isEdit ? 'PUT' : 'POST',
    'item' => $definition,
    'index_route' => 'attributes.index',
])

@section('inputFields')
    @if($isEdit && ($usageSummary['total'] ?? 0) > 0)
        <div class="alert alert-warning">
            {{ __('This attribute is already in use. Editing it updates current model specs, asset overrides, component definitions, and future test expectations that rely on this definition.') }}
            <ul style="margin:8px 0 0 16px;">
                @if(($usageSummary['model_values'] ?? 0) > 0)
                    <li>{{ __('Model-number values: :count', ['count' => $usageSummary['model_values']]) }}</li>
                @endif
                @if(($usageSummary['asset_overrides'] ?? 0) > 0)
                    <li>{{ __('Asset overrides: :count', ['count' => $usageSummary['asset_overrides']]) }}</li>
                @endif
                @if(($usageSummary['component_definitions'] ?? 0) > 0)
                    <li>{{ __('Component definitions: :count', ['count' => $usageSummary['component_definitions']]) }}</li>
                @endif
                @if(($usageSummary['tests'] ?? 0) > 0)
                    <li>{{ __('Test types: :count', ['count' => $usageSummary['tests']]) }}</li>
                @endif
            </ul>
        </div>
    @endif

    @if($isEdit && $definition->isDeprecated())
        <div class="alert alert-warning">
            {{ __('This attribute is deprecated. Existing records can still reference it, but it remains hidden from current selectors.') }}
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
            <input type="text" class="form-control" name="label" id="label" value="{{ old('label', $definition->label) }}" required>
            {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('key') ? ' has-error' : '' }}">
        <label for="key" class="col-md-3 control-label">{{ __('Key') }}</label>
        <div class="col-md-4">
            @if($isEdit)
                <input type="text" class="form-control" name="key" id="key" value="{{ old('key', $definition->key) }}" required>
                <span class="help-block">{{ __('Changing the key may affect API consumers, exports, and reports that reference it.') }}</span>
            @else
                <input type="hidden" name="manual_key_override" value="0">
                <div class="checkbox">
                    <label style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="manual_key_override" id="manual_key_override" value="1" {{ $createManualKeyOverride ? 'checked' : '' }}>
                        <span>{{ __('Manual key override') }}</span>
                    </label>
                </div>
                <input type="text" class="form-control" name="key" id="key" value="{{ $createKeyValue }}" {{ $createManualKeyOverride ? '' : 'disabled' }} required>
                <span class="help-block">{{ __('Used in API payloads and reports.') }}</span>
                <span class="help-block">{{ __('If manual override stays off, the key is generated from the label and normalized automatically.') }}</span>
            @endif
            {!! $errors->first('key', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group{{ $errors->has('datatype') ? ' has-error' : '' }}">
        <label for="datatype" class="col-md-3 control-label">{{ __('Datatype') }}</label>
        <div class="col-md-4">
            <select name="datatype" id="datatype" class="form-control" {{ $isEdit ? 'disabled' : '' }}>
                @foreach(\App\Models\AttributeDefinition::DATATYPES as $type)
                    <option value="{{ $type }}" {{ old('datatype', $definition->datatype ?: 'text') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            {!! $errors->first('datatype', '<span class="alert-msg">:message</span>') !!}
            @if($isEdit)
                <input type="hidden" name="datatype" value="{{ $definition->datatype }}">
                <span class="help-block">{{ __('Datatype cannot be changed after creation.') }}</span>
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
                    <input type="checkbox" name="required_for_category" value="1" {{ old('required_for_category', $definition->required_for_category) ? 'checked' : '' }}>
                    <span>{{ __('Required for category') }}</span>
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
            @php($constraintsSource = $definition->constraints ?: [])
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
@if(!$isEdit)
    <script nonce="{{ csrf_token() }}">
        (function () {
            var labelInput = document.getElementById('label');
            var keyInput = document.getElementById('key');
            var manualToggle = document.getElementById('manual_key_override');

            if (!labelInput || !keyInput || !manualToggle) {
                return;
            }

            function normalizeKey(value, fallback) {
                var source = (value || '').toString().trim().toLowerCase();
                source = source.replace(/[^a-z0-9]+/g, '_');
                source = source.replace(/^_+|_+$/g, '');
                source = source.replace(/_+/g, '_');

                if (source.length >= 3) {
                    return source;
                }

                return fallback || '';
            }

            function syncKeyState() {
                var manual = manualToggle.checked;
                keyInput.disabled = !manual;

                if (!manual) {
                    keyInput.value = normalizeKey(labelInput.value, labelInput.value.trim() !== '' ? 'attribute_key' : '');
                    return;
                }

                keyInput.value = normalizeKey(keyInput.value, keyInput.value.trim() !== '' ? 'attribute_key' : '');
            }

            manualToggle.addEventListener('change', syncKeyState);
            labelInput.addEventListener('input', syncKeyState);
            keyInput.addEventListener('input', function () {
                if (keyInput.disabled) {
                    return;
                }

                keyInput.value = normalizeKey(keyInput.value, keyInput.value.trim() !== '' ? 'attribute_key' : '');
            });

            syncKeyState();
        })();
    </script>
@endif
@endpush
