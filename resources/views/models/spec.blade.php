@extends('layouts/edit-form', [
    'updateText' => __('Save Specification'),
    'helpText' => __('Fill in the final specifications for this model. Assets inherit these values by default.'),
    'helpPosition' => 'right',
    'formAction' => route('models.spec.update', $model),
    'method' => 'PUT',
])

@section('inputFields')
    @if(($modelNumbers ?? collect())->isEmpty() || !$modelNumber)
        <div class="alert alert-info">
            {{ __('Add a model number to this model to edit its specification.') }}
        </div>
    @else
        <input type="hidden" name="model_number_id" id="model_spec_model_number_id" value="{{ $modelNumber->id }}">

        <div class="form-group">
            <label class="col-md-3 control-label" for="model_spec_model_number_selector">{{ __('Model Number') }}</label>
            <div class="col-md-7">
                <select id="model_spec_model_number_selector" class="form-control">
                    @foreach($modelNumbers as $number)
                        <option value="{{ $number->id }}" {{ $number->id === $modelNumber->id ? 'selected' : '' }}>
                            {{ $number->label ?: $number->code }}
                        </option>
                    @endforeach
                </select>
                <p class="help-block">
                    {{ __('Switch presets to review or edit their specification values.') }}
                </p>
            </div>
        </div>

        @if($attributes->isEmpty())
            <div class="alert alert-info">
                {{ __("No attribute definitions are scoped to this model's category yet.") }}
            </div>
        @endif

        @foreach($attributes as $resolved)
            @php($definition = $resolved->definition)
            @php($name = "attributes.".$definition->id)
            @php($inputName = "attributes[".$definition->id."]")
            @php($current = old($name, $resolved->value))
            @php($isRequired = $definition->required_for_category)
        <div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
            <label class="col-md-3 control-label" for="attribute_{{ $definition->id }}">
                {{ $definition->label }}
                @if($definition->required_for_category)
                    <span class="text-danger">*</span>
                @endif
                @if($definition->unit)
                    <span class="text-muted">({{ $definition->unit }})</span>
                @endif
                @if($definition->needs_test)
                    <span class="label label-info" style="margin-left:4px;">{{ __('Tested') }}</span>
                @endif
            </label>
            <div class="col-md-7">
                @switch($definition->datatype)
                    @case(\App\Models\AttributeDefinition::DATATYPE_BOOL)
                        <select name="{{ $inputName }}" id="attribute_{{ $definition->id }}" class="form-control" {{ $isRequired ? 'required' : '' }}>
                            <option value="" {{ $current === null ? 'selected' : '' }}>{{ __('Select yes or no') }}</option>
                            <option value="1" {{ $current === '1' ? 'selected' : '' }}>{{ __('Yes') }}</option>
                            <option value="0" {{ $current === '0' ? 'selected' : '' }}>{{ __('No') }}</option>
                        </select>
                        @break

                    @case(\App\Models\AttributeDefinition::DATATYPE_INT)
                        <input type="number" name="{{ $inputName }}" id="attribute_{{ $definition->id }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
                        @break

                    @case(\App\Models\AttributeDefinition::DATATYPE_DECIMAL)
                        <input type="number" step="any" name="{{ $inputName }}" id="attribute_{{ $definition->id }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
                        @break

                    @case(\App\Models\AttributeDefinition::DATATYPE_ENUM)
                        <input type="text" name="{{ $inputName }}" id="attribute_{{ $definition->id }}" class="form-control" value="{{ $current }}" list="attribute_{{ $definition->id }}_options" {{ $isRequired ? 'required' : '' }}>
                        <datalist id="attribute_{{ $definition->id }}_options">
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
                        <input type="text" name="{{ $inputName }}" id="attribute_{{ $definition->id }}" class="form-control" value="{{ $current }}" {{ $isRequired ? 'required' : '' }}>
                @endswitch

                {!! $errors->first($name, '<span class="alert-msg">:message</span>') !!}

                @if($resolved->source === 'missing')
                    <span class="help-block text-warning">{{ __('This attribute has no saved value yet.') }}</span>
                @elseif($resolved->rawValue && $resolved->rawValue !== $resolved->value)
                    <span class="help-block text-muted">{{ __('Original input: :value', ['value' => $resolved->rawValue]) }}</span>
                @endif
            </div>
        </div>
    @endforeach
    @endif
@endsection

@section('moar_scripts')
    @parent
    <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            var selector = document.getElementById('model_spec_model_number_selector');
            var hidden = document.getElementById('model_spec_model_number_id');

            if (!selector) {
                return;
            }

            selector.addEventListener('change', function () {
                if (hidden) {
                    hidden.value = this.value;
                }

                var url = new URL(window.location.href);
                url.searchParams.set('model_number_id', this.value);
                window.location.href = url.toString();
            });
        });
    </script>
@endsection
