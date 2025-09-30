@php($attributes = $attributes ?? collect())
@php($modelNumbers = $modelNumbers ?? collect())
@php($selectedModelNumber = $selectedModelNumber ?? null)
@php($selectedModelNumberId = old('model_number_id', $selectedModelNumber?->id))

@if($modelNumbers->isEmpty())
    <div class="alert alert-info">
        {{ __('Add a model number to this model before configuring specifications or overrides.') }}
    </div>
@else
    <div class="form-group">
        <label class="col-md-3 control-label" for="model_number_id">{{ __('Model Number') }}</label>
        <div class="col-md-7">
            <select name="model_number_id" id="model_number_id" class="form-control">
                @foreach($modelNumbers as $modelNumber)
                    <option value="{{ $modelNumber->id }}" {{ (string)$selectedModelNumberId === (string)$modelNumber->id ? 'selected' : '' }}>
                        {{ $modelNumber->label ?: $modelNumber->code }}
                    </option>
                @endforeach
            </select>
            {!! $errors->first('model_number_id', '<span class="alert-msg">:message</span>') !!}
            @if($modelNumbers->count() <= 1)
                <p class="help-block text-muted">{{ __('This model currently has a single specification preset.') }}</p>
            @else
                <p class="help-block text-muted">{{ __('Select a preset to load its specification and overrides.') }}</p>
            @endif
        </div>
    </div>

    @if($attributes->isEmpty())
        <div class="form-group">
            <label class="col-md-3 control-label">{{ __('Specification') }}</label>
            <div class="col-md-7">
                <p class="form-control-static text-muted">{{ __('No specification attributes are defined for this model yet.') }}</p>
            </div>
        </div>
    @else
    <hr>
    <div class="form-group">
        <label class="col-md-3 control-label">{{ __('Specification Overrides') }}</label>
        <div class="col-md-7">
            <p class="help-block">{{ __('Leave fields blank to inherit the model specification. Only attributes marked as overrideable can be changed per asset.') }}</p>
        </div>
    </div>
    @foreach($attributes as $attribute)
        @php($definition = $attribute->definition)
        @php($fieldName = 'attribute_overrides['.$definition->id.']')
        @php($fieldKey = 'attribute_overrides.'.$definition->id)
        @php($oldValue = old($fieldKey))
        @php($inputValue = !is_null($oldValue) ? $oldValue : ($attribute->isOverride ? $attribute->value : null))
        @php($modelDisplay = $attribute->formattedModelValue())
        @php($effectiveDisplay = $attribute->formattedValue())
        @php($baseDisplay = $modelDisplay ?? $effectiveDisplay)
        <div class="form-group{{ $errors->has($fieldKey) ? ' has-error' : '' }}">
            <label class="col-md-3 control-label" for="attribute_override_{{ $definition->id }}">
                {{ $definition->label }}
                @if($definition->unit)
                    <span class="text-muted">({{ $definition->unit }})</span>
                @endif
            </label>
            <div class="col-md-7">
                @if(!$definition->allow_asset_override)
                    <p class="form-control-static">
                        {{ $baseDisplay ?? __('Not specified') }}
                    </p>
                    <p class="help-block text-muted">{{ __('Overrides are disabled for this attribute.') }}</p>
                @else
                    @switch($definition->datatype)
                        @case(\App\Models\AttributeDefinition::DATATYPE_BOOL)
                            <select name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control">
                                <option value="">{{ __('Inherit') }}</option>
                                <option value="1" {{ (string)$inputValue === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                <option value="0" {{ (string)$inputValue === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                            </select>
                            @break

                        @case(\App\Models\AttributeDefinition::DATATYPE_INT)
                            <input type="number" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseDisplay }}">
                            @break

                        @case(\App\Models\AttributeDefinition::DATATYPE_DECIMAL)
                            <input type="number" step="any" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseDisplay }}">
                            @break

                        @case(\App\Models\AttributeDefinition::DATATYPE_ENUM)
                            <input type="text" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" list="attribute_override_{{ $definition->id }}_options" placeholder="{{ $baseDisplay }}">
                            <datalist id="attribute_override_{{ $definition->id }}_options">
                                @foreach($definition->options as $option)
                                    @if($option->active)
                                        <option value="{{ $option->value }}">{{ $option->label }}</option>
                                    @endif
                                @endforeach
                            </datalist>
                            <p class="help-block text-muted">{{ $definition->allow_custom_values ? __('Enter a custom value if it differs from the listed options.') : __('Choose from the allowed options or leave blank to inherit.') }}</p>
                            @break

                        @default
                            <input type="text" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseDisplay }}">
                    @endswitch

                    {!! $errors->first($fieldKey, '<span class="alert-msg">:message</span>') !!}
                    <p class="help-block text-muted">{{ __('Model spec: :value', ['value' => $modelDisplay ?? __('Not specified')]) }}</p>
                @endif
            </div>
       </div>
        @endforeach
    @endif
@endif

<script nonce="{{ csrf_token() }}">
    (function () {
        var selector = document.getElementById('model_number_id');

        if (!selector) {
            return;
        }

        selector.addEventListener('change', function () {
            var modelField = document.getElementById('model_select_id');
            if (!modelField || !modelField.value) {
                return;
            }

            window.fetchSpecification(modelField.value, this.value);
        });
    })();
</script>

