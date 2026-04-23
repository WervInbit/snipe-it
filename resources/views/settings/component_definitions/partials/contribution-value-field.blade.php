@php
    $definition = $selectedDefinition ?? null;
    $fieldName = 'attribute_contributions[' . $index . '][value]';
    $fieldId = 'attribute_contributions_' . $index . '_value';
    $resolveFieldName = 'attribute_contributions[' . $index . '][resolves_to_spec]';
    $currentValue = (string) ($row['value'] ?? '');
    $resolveChecked = !empty($row['resolves_to_spec']);
    $constraints = $definition?->constraints ?? [];
    $activeOptions = $definition?->options?->filter(fn ($option) => (bool) $option->active)->values() ?? collect();
    $constraintHints = collect();

    if ($definition && array_key_exists('min', $constraints) && $constraints['min'] !== null && $constraints['min'] !== '') {
        $constraintHints->push(__('Min: :value', ['value' => $constraints['min']]));
    }

    if ($definition && array_key_exists('max', $constraints) && $constraints['max'] !== null && $constraints['max'] !== '') {
        $constraintHints->push(__('Max: :value', ['value' => $constraints['max']]));
    }

    if ($definition && array_key_exists('step', $constraints) && $constraints['step'] !== null && $constraints['step'] !== '') {
        $constraintHints->push(__('Step: :value', ['value' => $constraints['step']]));
    }

    if ($definition && array_key_exists('regex', $constraints) && $constraints['regex']) {
        $constraintHints->push(__('Pattern: :pattern', ['pattern' => $constraints['regex']]));
    }
@endphp

@if($definition)
    @switch($definition->datatype)
        @case(\App\Models\AttributeDefinition::DATATYPE_BOOL)
            <select name="{{ $fieldName }}"
                    id="{{ $fieldId }}"
                    class="form-control"
                    data-contribution-value-input>
                <option value="" {{ $currentValue === '' ? 'selected' : '' }}>{{ __('Select yes or no') }}</option>
                <option value="1" {{ $currentValue === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                <option value="0" {{ $currentValue === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
            </select>
            @break

        @case(\App\Models\AttributeDefinition::DATATYPE_INT)
            <input type="number"
                   name="{{ $fieldName }}"
                   id="{{ $fieldId }}"
                   class="form-control"
                   value="{{ $currentValue }}"
                   @if(array_key_exists('min', $constraints) && $constraints['min'] !== null && $constraints['min'] !== '') min="{{ $constraints['min'] }}" @endif
                   @if(array_key_exists('max', $constraints) && $constraints['max'] !== null && $constraints['max'] !== '') max="{{ $constraints['max'] }}" @endif
                   @if(array_key_exists('step', $constraints) && $constraints['step'] !== null && $constraints['step'] !== '') step="{{ $constraints['step'] }}" @else step="1" @endif
                   data-contribution-value-input>
            @break

        @case(\App\Models\AttributeDefinition::DATATYPE_DECIMAL)
            <input type="number"
                   name="{{ $fieldName }}"
                   id="{{ $fieldId }}"
                   class="form-control"
                   value="{{ $currentValue }}"
                   @if(array_key_exists('min', $constraints) && $constraints['min'] !== null && $constraints['min'] !== '') min="{{ $constraints['min'] }}" @endif
                   @if(array_key_exists('max', $constraints) && $constraints['max'] !== null && $constraints['max'] !== '') max="{{ $constraints['max'] }}" @endif
                   @if(array_key_exists('step', $constraints) && $constraints['step'] !== null && $constraints['step'] !== '') step="{{ $constraints['step'] }}" @else step="any" @endif
                   data-contribution-value-input>
            @break

        @case(\App\Models\AttributeDefinition::DATATYPE_ENUM)
            @if($definition->allow_custom_values)
                <input type="text"
                       name="{{ $fieldName }}"
                       id="{{ $fieldId }}"
                       class="form-control"
                       value="{{ $currentValue }}"
                       list="{{ $fieldId }}_options"
                       data-contribution-value-input>
                <datalist id="{{ $fieldId }}_options">
                    @foreach($activeOptions as $option)
                        <option value="{{ $option->value }}">{{ $option->label }}</option>
                    @endforeach
                </datalist>
                <span class="help-block">{{ __('Enter a custom value if no option matches.') }}</span>
            @else
                <select name="{{ $fieldName }}"
                        id="{{ $fieldId }}"
                        class="form-control"
                        data-contribution-value-input>
                    <option value="" {{ $currentValue === '' ? 'selected' : '' }}>{{ __('Select an option') }}</option>
                    @foreach($activeOptions as $option)
                        <option value="{{ $option->value }}" {{ (string) $currentValue === (string) $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                    @endforeach
                </select>
                <span class="help-block">{{ __('Use one of the defined options.') }}</span>
            @endif
            @break

        @default
            <input type="text"
                   name="{{ $fieldName }}"
                   id="{{ $fieldId }}"
                   class="form-control"
                   value="{{ $currentValue }}"
                   data-contribution-value-input>
    @endswitch

    <p class="help-block text-muted" style="margin-bottom:0;">
        {{ __('Datatype: :type', ['type' => ucfirst($definition->datatype)]) }}
        @if($definition->unit)
            - {{ __('Unit: :unit', ['unit' => $definition->unit]) }}
        @endif
        @if($constraintHints->isNotEmpty())
            - {{ $constraintHints->implode(' - ') }}
        @endif
    </p>

    @if($definition->isNumericDatatype())
        <div class="checkbox" style="margin-top:10px; margin-bottom:0;">
            <label>
                <input type="checkbox" name="{{ $resolveFieldName }}" value="1" @checked($resolveChecked)>
                {{ __('Use for calculated specification') }}
            </label>
        </div>
        <p class="help-block text-muted" style="margin-bottom:0;">{{ __('Only numeric contributions can replace calculated specification values.') }}</p>
    @endif
@else
    <input type="text"
           class="form-control"
           id="{{ $fieldId }}"
           name="{{ $fieldName }}"
           value="{{ $currentValue }}"
           placeholder="{{ __('Select an attribute first') }}"
           disabled
           data-contribution-value-input>
@endif
