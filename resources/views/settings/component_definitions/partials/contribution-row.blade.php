@php
    $showErrors = $showErrors ?? true;
    $selectedDefinition = $selectedDefinition ?? $attributeDefinitionsById->get((int) ($row['attribute_definition_id'] ?? 0));
    $attributeErrorKey = 'attribute_contributions.' . $index . '.attribute_definition_id';
    $valueErrorKey = 'attribute_contributions.' . $index . '.value';
    $resolveErrorKey = 'attribute_contributions.' . $index . '.resolves_to_spec';
    $pickerValue = trim((string) ($row['attribute_search'] ?? ''));

    if ($pickerValue === '' && $selectedDefinition) {
        $pickerValue = $selectedDefinition->label . ' (' . $selectedDefinition->key . ')';
    }
@endphp

<div class="panel panel-default" data-contribution-row>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-5 form-group {{ $showErrors && $errors->has($attributeErrorKey) ? 'has-error' : '' }}">
                <label>{{ __('Attribute') }}</label>
                <input type="hidden"
                       name="attribute_contributions[{{ $index }}][attribute_definition_id]"
                       value="{{ $row['attribute_definition_id'] ?? '' }}"
                       data-contribution-attribute-id>
                <input type="search"
                       class="form-control"
                       name="attribute_contributions[{{ $index }}][attribute_search]"
                       value="{{ $pickerValue }}"
                       placeholder="{{ __('Search attributes...') }}"
                       autocomplete="off"
                       data-contribution-attribute-search>
                <div class="list-group component-definition-attribute-results" data-contribution-search-results hidden></div>
                <p class="help-block text-muted">
                    {{ __('Start typing an attribute label or key, then pick a match.') }}
                </p>
                @if($showErrors)
                    {!! $errors->first($attributeErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>

            <div class="col-md-5 form-group {{ $showErrors && $errors->has($valueErrorKey) ? 'has-error' : '' }}">
                <label>{{ __('Value') }}</label>
                <div data-contribution-value-field
                     data-contribution-index="{{ $index }}"
                     data-current-value="{{ $row['value'] ?? '' }}"
                     data-current-resolves="{{ !empty($row['resolves_to_spec']) ? '1' : '0' }}">
                    @include('settings.component_definitions.partials.contribution-value-field', [
                        'index' => $index,
                        'row' => $row,
                        'selectedDefinition' => $selectedDefinition,
                    ])
                </div>
                @if($showErrors)
                    {!! $errors->first($valueErrorKey, '<span class="help-block">:message</span>') !!}
                    {!! $errors->first($resolveErrorKey, '<span class="help-block">:message</span>') !!}
                @endif
            </div>

            <div class="col-md-2 form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-default btn-block" data-remove-contribution>{{ __('Remove') }}</button>
            </div>
        </div>
    </div>
</div>
