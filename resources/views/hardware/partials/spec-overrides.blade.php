@php($attributes = $attributes ?? collect())

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
        @php($baseValue = $attribute->modelValue ?? $attribute->value)
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
                        {{ $baseValue ?? __('Not specified') }}
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
                            <input type="number" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseValue }}">
                            @break

                        @case(\App\Models\AttributeDefinition::DATATYPE_DECIMAL)
                            <input type="number" step="any" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseValue }}">
                            @break

                        @case(\App\Models\AttributeDefinition::DATATYPE_ENUM)
                            <input type="text" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" list="attribute_override_{{ $definition->id }}_options" placeholder="{{ $baseValue }}">
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
                            <input type="text" name="{{ $fieldName }}" id="attribute_override_{{ $definition->id }}" class="form-control" value="{{ $inputValue }}" placeholder="{{ $baseValue }}">
                    @endswitch

                    {!! $errors->first($fieldKey, '<span class="alert-msg">:message</span>') !!}
                    <p class="help-block text-muted">{{ __('Model spec: :value', ['value' => $baseValue ?? __('Not specified')]) }}</p>
                @endif
            </div>
        </div>
    @endforeach
@endif
