@php($definition = $resolved->definition)
@php($fieldKey = 'attributes.'.$definition->id)
@php($fieldName = 'attributes['.$definition->id.']')
@php($inputId = 'attribute_'.$definition->id)
@php($current = old($fieldKey, $resolved->value))
@php($isRequired = (bool) $definition->required_for_category)
@php($searchText = strtolower($definition->label.' '.$definition->key))
<div class="attribute-detail-panel" data-attribute-id="{{ $definition->id }}" data-search-text="{{ $searchText }}" hidden>
    <div class="attribute-detail-panel__header">
        <h4>
            {{ $definition->label }}
            <small class="text-muted">{{ $definition->key }}</small>
            @if($definition->isDeprecated())
                <span class="label label-warning">{{ __('Deprecated') }}</span>
            @endif
            @if($definition->isHidden())
                <span class="label label-default">{{ __('Hidden') }}</span>
            @endif
        </h4>
        @if($definition->unit)
            <p class="text-muted">{{ __('Unit: :unit', ['unit' => $definition->unit]) }}</p>
        @endif
        @if($definition->isDeprecated())
            <p class="text-warning" style="margin-top:8px;">{{ __('This attribute is deprecated. Plan to migrate to a newer version.') }}</p>
        @endif
    </div>

    <div class="form-group{{ $errors->has($fieldKey) ? ' has-error' : '' }}">
        <label for="{{ $inputId }}">
            {{ __('Specification Value') }}
            @if($isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        @switch($definition->datatype)
            @case(\App\Models\AttributeDefinition::DATATYPE_BOOL)
                <select name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" {{ $isRequired ? 'required' : '' }}>
                    <option value="" {{ $current === null ? 'selected' : '' }}>{{ __('Select yes or no') }}</option>
                    <option value="1" {{ $current === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                    <option value="0" {{ $current === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                </select>
                @break

            @case(\App\Models\AttributeDefinition::DATATYPE_INT)
                <input type="number" name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
                @break

            @case(\App\Models\AttributeDefinition::DATATYPE_DECIMAL)
                <input type="number" step="any" name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
                @break

            @case(\App\Models\AttributeDefinition::DATATYPE_ENUM)
                <input type="text" name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" value="{{ $current }}" list="{{ $inputId }}_options" {{ $isRequired ? 'required' : '' }}>
                <datalist id="{{ $inputId }}_options">
                    @foreach($definition->options as $option)
                        @if($option->active)
                            <option value="{{ $option->value }}">{{ $option->label }}</option>
                        @endif
                    @endforeach
                </datalist>
                <span class="help-block">
                    {{ $definition->allow_custom_values ? __('Enter a custom value if no option matches.') : __('Use one of the defined options.') }}
                </span>
                @break

            @default
                <input type="text" name="{{ $fieldName }}" id="{{ $inputId }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
        @endswitch

        {!! $errors->first($fieldKey, '<span class="alert-msg">:message</span>') !!}

        @if($resolved->source === 'missing')
            <span class="help-block text-warning">{{ __('This attribute has no saved value yet.') }}</span>
        @elseif($resolved->rawValue && $resolved->rawValue !== $resolved->value)
            <span class="help-block text-muted">{{ __('Original input: :value', ['value' => $resolved->rawValue]) }}</span>
        @endif
    </div>
</div>
