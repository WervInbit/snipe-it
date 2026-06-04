@csrf
@if(($method ?? 'POST') !== 'POST')
    @method($method)
@endif

@php
    $attributeDefinitions = $attributeDefinitions ?? collect();
    $attributeDefinitionsById = $attributeDefinitions->keyBy('id');
    $componentDefinitionOptions = ($componentDefinitions ?? collect())->sortBy('name')->values();
    $hierarchyOverlapWarnings = collect($hierarchyOverlapWarnings ?? []);
    $componentDefinitionPayload = $componentDefinitionOptions->map(function ($definition) {
        $meta = collect([
            $definition->part_code ? __('Part Code') . ': ' . $definition->part_code : null,
            $definition->model_number ? __('Model Number') . ': ' . $definition->model_number : null,
            $definition->category?->name,
            $definition->manufacturer?->name,
        ])->filter()->values();

        return [
            'id' => $definition->id,
            'name' => $definition->name,
            'part_code' => $definition->part_code,
            'model_number' => $definition->model_number,
            'category' => $definition->category?->name,
            'manufacturer' => $definition->manufacturer?->name,
            'display' => $definition->name . ($definition->part_code ? ' (' . $definition->part_code . ')' : ''),
            'meta' => $meta->implode(' - '),
            'search_text' => strtolower(implode(' ', array_filter([
                $definition->name,
                $definition->part_code,
                $definition->model_number,
                $definition->category?->name,
                $definition->manufacturer?->name,
            ]))),
        ];
    })->values();
    $attributeDefinitionPayload = $attributeDefinitions->map(function ($definition) {
        return [
            'id' => $definition->id,
            'label' => $definition->label,
            'key' => $definition->key,
            'display' => $definition->label . ' (' . $definition->key . ')',
            'search_text' => strtolower($definition->label . ' ' . $definition->key),
            'datatype' => $definition->datatype,
            'unit' => $definition->unit,
            'allow_custom_values' => (bool) $definition->allow_custom_values,
            'constraints' => $definition->constraints,
            'is_numeric' => $definition->isNumericDatatype(),
            'options' => $definition->options
                ->filter(fn ($option) => (bool) $option->active)
                ->values()
                ->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'value' => $option->value,
                ])
                ->all(),
        ];
    })->values();
    $contributionText = [
        'datatype' => __('Datatype'),
        'unit' => __('Unit'),
        'min' => __('Min'),
        'max' => __('Max'),
        'step' => __('Step'),
        'pattern' => __('Pattern'),
        'selectYesNo' => __('Select yes or no'),
        'selectOption' => __('Select an option'),
        'yes' => __('Yes'),
        'no' => __('No'),
        'options' => __('Options'),
        'customEnumHelp' => __('Enter a custom value if no option matches.'),
        'strictEnumHelp' => __('Use one of the defined options.'),
        'selectAttributeFirst' => __('Select an attribute first'),
        'noMatchingAttributes' => __('No matching attributes.'),
        'resolveToSpec' => __('Use for calculated specification'),
        'resolveToSpecHelp' => __('When checked, this component value is used as the model or asset specification instead of a manual attribute value.'),
    ];
    $subcomponentPickerText = [
        'noMatchingDefinitions' => __('No matching component definitions.'),
        'freeformExpectedSubcomponent' => __('Freeform expected subcomponent'),
    ];
    $contributionRows = collect(old('attribute_contributions', []));

    if ($contributionRows->isEmpty() && $item->exists) {
        $item->loadMissing(['attributeContributions.definition.options', 'attributeContributions.option']);
        $contributionRows = $item->attributeContributions->map(function ($contribution) {
            return [
                'attribute_definition_id' => $contribution->attribute_definition_id,
                'attribute_search' => optional($contribution->definition)->label
                    ? $contribution->definition->label . ' (' . $contribution->definition->key . ')'
                    : '',
                'value' => $contribution->raw_value ?? $contribution->value,
                'resolves_to_spec' => (bool) $contribution->resolves_to_spec,
            ];
        });
    }

    if ($contributionRows->isEmpty()) {
        $contributionRows = collect([[
            'attribute_definition_id' => '',
            'attribute_search' => '',
            'value' => '',
            'resolves_to_spec' => false,
        ]]);
    }

    $subcomponentTemplateRows = collect(old('expected_subcomponents', []));

    if ($subcomponentTemplateRows->isEmpty() && $item->exists) {
        $item->loadMissing(['subcomponentTemplates.childComponentDefinition']);
        $subcomponentTemplateRows = $item->subcomponentTemplates->map(function ($template) {
            return [
                'id' => $template->id,
                'child_component_definition_id' => $template->child_component_definition_id,
                'expected_name' => $template->expected_name,
                'expected_qty' => $template->expected_qty,
                'is_required' => (bool) $template->is_required,
                'notes' => $template->notes,
            ];
        });
    }

    if ($subcomponentTemplateRows->isEmpty()) {
        $subcomponentTemplateRows = collect([[
            'id' => '',
            'child_component_definition_id' => '',
            'expected_name' => '',
            'expected_qty' => '',
            'is_required' => true,
            'notes' => '',
        ]]);
    }
@endphp

@once
    @push('css')
        <style nonce="{{ csrf_token() }}">
            .component-definition-attribute-results {
                max-height: 220px;
                overflow-y: auto;
                margin-top: 6px;
                margin-bottom: 0;
            }

            .component-definition-picker-results {
                max-height: 220px;
                overflow-y: auto;
                margin-top: 6px;
                margin-bottom: 0;
            }

            .component-definition-attribute-result {
                width: 100%;
                text-align: left;
                border: 0;
                background: transparent;
            }

            .component-definition-picker-result {
                width: 100%;
                text-align: left;
                border: 0;
                background: transparent;
            }

            .component-definition-attribute-result strong,
            .component-definition-attribute-result span,
            .component-definition-picker-result strong,
            .component-definition-picker-result span {
                pointer-events: none;
            }

            .component-definition-subcomponent-notes {
                margin-top: 10px;
            }
        </style>
    @endpush
@endonce

<div class="box box-default">
    <div class="box-body">
        <div class="row">
            <div class="col-md-6 form-group {{ $errors->has('name') ? 'has-error' : '' }}">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required>
                {!! $errors->first('name', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-3 form-group {{ $errors->has('part_code') ? 'has-error' : '' }}">
                <label for="part_code">{{ __('Part Code') }}</label>
                <input type="text" class="form-control" id="part_code" name="part_code" value="{{ old('part_code', $item->part_code) }}">
                {!! $errors->first('part_code', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-3 form-group {{ $errors->has('model_number') ? 'has-error' : '' }}">
                <label for="model_number">{{ __('Model Number') }}</label>
                <input type="text" class="form-control" id="model_number" name="model_number" value="{{ old('model_number', $item->model_number) }}">
                {!! $errors->first('model_number', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group {{ $errors->has('category_id') ? 'has-error' : '' }}">
                <label for="category_id">{{ __('Category') }}</label>
                <select class="form-control" id="category_id" name="category_id">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('category_id', $item->category_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('category_id', '<span class="help-block">:message</span>') !!}
            </div>

            <div class="col-md-4 form-group {{ $errors->has('manufacturer_id') ? 'has-error' : '' }}">
                <label for="manufacturer_id">{{ __('Manufacturer') }}</label>
                <select class="form-control" id="manufacturer_id" name="manufacturer_id">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($manufacturers as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('manufacturer_id', $item->manufacturer_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                {!! $errors->first('manufacturer_id', '<span class="help-block">:message</span>') !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label>{{ __('Status') }}</label>
                <div class="checkbox" style="margin-top:8px;">
                    <label>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $item->exists ? $item->is_active : true))>
                        {{ __('Active') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group {{ $errors->has('spec_summary') ? 'has-error' : '' }}">
            <label for="spec_summary">{{ __('Specification Summary') }}</label>
            <textarea class="form-control" id="spec_summary" name="spec_summary" rows="5">{{ old('spec_summary', $item->spec_summary) }}</textarea>
            {!! $errors->first('spec_summary', '<span class="help-block">:message</span>') !!}
        </div>

        <hr>

        <div class="form-group{{ $errors->has('expected_subcomponents') ? ' has-error' : '' }}" id="expected-subcomponents">
            <label>{{ __('Expected Subcomponents') }}</label>
            <p class="help-block">
                {{ __('Define child parts that are normally expected inside this component definition.') }}
            </p>
            {!! $errors->first('expected_subcomponents', '<span class="help-block">:message</span>') !!}

            @include('settings.component_definitions.partials.hierarchy-overlap-warnings', [
                'warnings' => $hierarchyOverlapWarnings,
            ])

            <div data-subcomponent-template-rows data-next-index="{{ $subcomponentTemplateRows->count() }}">
                @foreach($subcomponentTemplateRows->values() as $index => $row)
                    @include('settings.component_definitions.partials.subcomponent-template-row', [
                        'index' => $index,
                        'row' => $row,
                        'componentDefinitionOptions' => $componentDefinitionOptions,
                        'currentDefinitionId' => $item->id,
                    ])
                @endforeach
            </div>

            <template data-subcomponent-template>
                @include('settings.component_definitions.partials.subcomponent-template-row', [
                    'index' => '__INDEX__',
                    'row' => [
                        'id' => '',
                        'child_component_definition_id' => '',
                        'expected_name' => '',
                        'expected_qty' => '',
                        'is_required' => true,
                        'notes' => '',
                    ],
                    'componentDefinitionOptions' => $componentDefinitionOptions,
                    'currentDefinitionId' => $item->id,
                    'showErrors' => false,
                ])
            </template>

            <button type="button" class="btn btn-default" data-add-subcomponent-template>{{ __('Add Expected Subcomponent') }}</button>
        </div>

        <hr>

        <div class="form-group{{ $errors->has('attribute_contributions') ? ' has-error' : '' }}">
            <label>{{ __('Attribute Contributions') }}</label>
            <p class="help-block">
                {{ __('Use shared attribute definitions here so installed components can roll into the asset specification.') }}
            </p>
            {!! $errors->first('attribute_contributions', '<span class="help-block">:message</span>') !!}

            <div data-contribution-rows data-next-index="{{ $contributionRows->count() }}">
                @foreach($contributionRows->values() as $index => $row)
                    @include('settings.component_definitions.partials.contribution-row', [
                        'index' => $index,
                        'row' => $row,
                        'selectedDefinition' => $attributeDefinitionsById->get((int) ($row['attribute_definition_id'] ?? 0)),
                        'attributeDefinitionsById' => $attributeDefinitionsById,
                    ])
                @endforeach
            </div>

            <template data-contribution-template>
                @include('settings.component_definitions.partials.contribution-row', [
                    'index' => '__INDEX__',
                    'row' => [
                        'attribute_definition_id' => '',
                        'attribute_search' => '',
                        'value' => '',
                        'resolves_to_spec' => false,
                    ],
                    'selectedDefinition' => null,
                    'attributeDefinitionsById' => $attributeDefinitionsById,
                    'showErrors' => false,
                ])
            </template>

            <button type="button" class="btn btn-default" data-add-contribution>{{ __('Add Attribute Contribution') }}</button>
        </div>
    </div>

    <div class="box-footer">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('settings.component_definitions.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
    </div>
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                var definitions = @json($componentDefinitionPayload);
                var definitionMap = {};
                var currentDefinitionId = @json((int) ($item->id ?? 0));
                var text = @json($subcomponentPickerText);

                definitions.forEach(function (definition) {
                    definitionMap[String(definition.id)] = definition;
                });

                function wrapper() {
                    return document.querySelector('[data-subcomponent-template-rows]');
                }

                function escapeHtml(value) {
                    return String(value === null || value === undefined ? '' : value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function normalize(value) {
                    return String(value || '').trim().toLowerCase();
                }

                function canUseDefinition(definition) {
                    return !currentDefinitionId || String(definition.id) !== String(currentDefinitionId);
                }

                function matchingDefinitions(term) {
                    var query = normalize(term);
                    var matches = definitions.filter(function (definition) {
                        if (!canUseDefinition(definition)) {
                            return false;
                        }

                        if (query === '') {
                            return true;
                        }

                        return definition.search_text.indexOf(query) !== -1;
                    });

                    return matches.slice(0, 8);
                }

                function exactMatchDefinition(term) {
                    var query = normalize(term);

                    if (query === '') {
                        return null;
                    }

                    return definitions.find(function (definition) {
                        if (!canUseDefinition(definition)) {
                            return false;
                        }

                        return [definition.display, definition.name, definition.part_code, definition.model_number].some(function (candidate) {
                            return normalize(candidate) === query;
                        });
                    }) || null;
                }

                function hideAllSearchResults(exceptRow) {
                    document.querySelectorAll('[data-subcomponent-search-results]').forEach(function (results) {
                        if (exceptRow && exceptRow.contains(results)) {
                            return;
                        }

                        results.hidden = true;
                        results.innerHTML = '';
                    });
                }

                function renderSearchResults(row) {
                    var searchInput = row ? row.querySelector('[data-subcomponent-definition-search]') : null;
                    var results = row ? row.querySelector('[data-subcomponent-search-results]') : null;

                    if (!searchInput || !results) {
                        return;
                    }

                    var matches = matchingDefinitions(searchInput.value);

                    if (matches.length === 0) {
                        results.innerHTML = '<div class="list-group-item text-muted">' + escapeHtml(text.noMatchingDefinitions) + '</div>';
                        results.hidden = false;
                        return;
                    }

                    results.innerHTML = matches.map(function (definition) {
                        var meta = definition.meta
                            ? '<span class="text-muted small">' + escapeHtml(definition.meta) + '</span>'
                            : '';

                        return '<button type="button" class="list-group-item component-definition-picker-result" data-subcomponent-select-definition="' + escapeHtml(definition.id) + '">' +
                            '<strong>' + escapeHtml(definition.name) + '</strong>' +
                            (meta ? '<br>' + meta : '') +
                        '</button>';
                    }).join('');
                    results.hidden = false;
                }

                function applyDefinitionToRow(row, definition) {
                    var hiddenInput = row ? row.querySelector('[data-subcomponent-definition-id]') : null;
                    var searchInput = row ? row.querySelector('[data-subcomponent-definition-search]') : null;

                    if (!hiddenInput || !searchInput) {
                        return;
                    }

                    if (!definition) {
                        hiddenInput.value = '';
                        return;
                    }

                    hiddenInput.value = String(definition.id);
                    searchInput.value = definition.display;
                    hideAllSearchResults();
                }

                function selectDefinitionFromButton(button) {
                    var row = button ? button.closest('[data-subcomponent-template-row]') : null;
                    var definition = definitionMap[String(button ? (button.getAttribute('data-subcomponent-select-definition') || '') : '')];

                    if (!row || !definition) {
                        return;
                    }

                    applyDefinitionToRow(row, definition);

                    var expectedNameInput = row.querySelector('input[name$="[expected_name]"]');
                    if (expectedNameInput) {
                        expectedNameInput.focus();
                    }
                }

                function selectedMatchesInput(definition, value) {
                    return [definition.display, definition.name, definition.part_code, definition.model_number].some(function (candidate) {
                        return normalize(candidate) === normalize(value);
                    });
                }

                function wireRow(row) {
                    if (!row || row.dataset.subcomponentTemplateReady === '1') {
                        return;
                    }

                    row.dataset.subcomponentTemplateReady = '1';

                    var hiddenInput = row.querySelector('[data-subcomponent-definition-id]');
                    var searchInput = row.querySelector('[data-subcomponent-definition-search]');
                    var selectedDefinition = hiddenInput ? definitionMap[String(hiddenInput.value || '')] : null;

                    if (selectedDefinition && searchInput && normalize(searchInput.value) === '') {
                        searchInput.value = selectedDefinition.display;
                    }

                    if (!hiddenInput || !searchInput) {
                        return;
                    }

                    searchInput.addEventListener('focus', function () {
                        hideAllSearchResults(row);
                        renderSearchResults(row);
                    });

                    searchInput.addEventListener('input', function () {
                        if (normalize(searchInput.value) === '') {
                            applyDefinitionToRow(row, null);
                        }

                        renderSearchResults(row);
                    });

                    searchInput.addEventListener('search', function () {
                        if (normalize(searchInput.value) === '') {
                            applyDefinitionToRow(row, null);
                        }

                        renderSearchResults(row);
                    });

                    searchInput.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter') {
                            return;
                        }

                        var firstResult = row.querySelector('[data-subcomponent-select-definition]');
                        if (!firstResult) {
                            return;
                        }

                        event.preventDefault();
                        firstResult.click();
                    });

                    searchInput.addEventListener('blur', function () {
                        window.setTimeout(function () {
                            var matched = exactMatchDefinition(searchInput.value);
                            if (matched) {
                                applyDefinitionToRow(row, matched);
                                return;
                            }

                            var selected = definitionMap[String(hiddenInput.value || '')];
                            if (selected && selectedMatchesInput(selected, searchInput.value)) {
                                searchInput.value = selected.display;
                                hideAllSearchResults();
                                return;
                            }

                            if (normalize(searchInput.value) === '') {
                                applyDefinitionToRow(row, null);
                            } else {
                                hiddenInput.value = '';
                            }

                            hideAllSearchResults();
                        }, 150);
                    });
                }

                function ensureOneRow(container) {
                    if (!container || container.querySelector('[data-subcomponent-template-row]')) {
                        return;
                    }

                    var template = document.querySelector('[data-subcomponent-template]');
                    if (!template) {
                        return;
                    }

                    var nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
                    container.dataset.nextIndex = String(nextIndex + 1);
                    container.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
                    wireRow(container.querySelector('[data-subcomponent-template-row]:last-child'));
                }

                document.querySelectorAll('[data-subcomponent-template-row]').forEach(wireRow);

                document.addEventListener('mousedown', function (event) {
                    var definitionButton = event.target.closest('[data-subcomponent-select-definition]');
                    if (!definitionButton) {
                        return;
                    }

                    event.preventDefault();
                    selectDefinitionFromButton(definitionButton);
                });

                document.addEventListener('click', function (event) {
                    var definitionButton = event.target.closest('[data-subcomponent-select-definition]');
                    if (definitionButton) {
                        event.preventDefault();
                        selectDefinitionFromButton(definitionButton);
                        return;
                    }

                    var addButton = event.target.closest('[data-add-subcomponent-template]');
                    if (addButton) {
                        var container = wrapper();
                        var template = document.querySelector('[data-subcomponent-template]');

                        if (!container || !template) {
                            return;
                        }

                        var nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
                        container.dataset.nextIndex = String(nextIndex + 1);
                        container.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
                        wireRow(container.querySelector('[data-subcomponent-template-row]:last-child'));
                        return;
                    }

                    var removeButton = event.target.closest('[data-remove-subcomponent-template]');
                    if (removeButton) {
                        var row = removeButton.closest('[data-subcomponent-template-row]');
                        var containerForRemove = wrapper();

                        if (row) {
                            row.remove();
                        }

                        ensureOneRow(containerForRemove);
                        return;
                    }

                    var moveButton = event.target.closest('[data-move-subcomponent-template]');
                    if (!moveButton) {
                        if (!event.target.closest('[data-subcomponent-template-row]')) {
                            hideAllSearchResults();
                        }
                        return;
                    }

                    event.preventDefault();

                    var movingRow = moveButton.closest('[data-subcomponent-template-row]');
                    if (!movingRow) {
                        return;
                    }

                    if (moveButton.getAttribute('data-move-subcomponent-template') === 'up') {
                        var previous = movingRow.previousElementSibling;
                        if (previous) {
                            movingRow.parentNode.insertBefore(movingRow, previous);
                        }
                        return;
                    }

                    var next = movingRow.nextElementSibling;
                    if (next) {
                        movingRow.parentNode.insertBefore(next, movingRow);
                    }
                });
            })();

            (function () {
                var definitions = @json($attributeDefinitionPayload);
                var definitionMap = {};
                var text = @json($contributionText);

                definitions.forEach(function (definition) {
                    definitionMap[String(definition.id)] = definition;
                });

                function escapeHtml(value) {
                    return String(value === null || value === undefined ? '' : value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function normalize(value) {
                    return String(value || '').trim().toLowerCase();
                }

                function findContributionWrapper() {
                    return document.querySelector('[data-contribution-rows]');
                }

                function getValueInput(row) {
                    return row ? row.querySelector('[data-contribution-value-input]') : null;
                }

                function readCurrentValue(row) {
                    var input = getValueInput(row);
                    if (input) {
                        return input.value || '';
                    }

                    var field = row ? row.querySelector('[data-contribution-value-field]') : null;
                    return field ? (field.dataset.currentValue || '') : '';
                }

                function buildConstraintSummary(definition) {
                    var constraints = definition && definition.constraints ? definition.constraints : {};
                    var hints = [];

                    if (constraints.min !== undefined && constraints.min !== null && constraints.min !== '') {
                        hints.push(text.min + ': ' + constraints.min);
                    }

                    if (constraints.max !== undefined && constraints.max !== null && constraints.max !== '') {
                        hints.push(text.max + ': ' + constraints.max);
                    }

                    if (constraints.step !== undefined && constraints.step !== null && constraints.step !== '') {
                        hints.push(text.step + ': ' + constraints.step);
                    }

                    if (constraints.regex) {
                        hints.push(text.pattern + ': ' + constraints.regex);
                    }

                    return hints;
                }

                function renderValueField(row, definition, currentValue) {
                    var field = row ? row.querySelector('[data-contribution-value-field]') : null;
                    if (!field) {
                        return;
                    }

                    var index = field.dataset.contributionIndex;
                    var fieldName = 'attribute_contributions[' + index + '][value]';
                    var fieldId = 'attribute_contributions_' + index + '_value';
                    var resolveFieldName = 'attribute_contributions[' + index + '][resolves_to_spec]';
                    var value = String(currentValue || '');
                    var resolveChecked = field.dataset.currentResolves === '1';
                    var html = '';

                    if (!definition) {
                        field.dataset.currentValue = '';
                        field.dataset.currentResolves = '0';
                        field.innerHTML =
                            '<input type="text" class="form-control" id="' + escapeHtml(fieldId) + '" name="' + escapeHtml(fieldName) + '" value="" placeholder="' + escapeHtml(text.selectAttributeFirst) + '" disabled data-contribution-value-input>';
                        return;
                    }

                    switch (definition.datatype) {
                        case 'bool':
                            html += '<select name="' + escapeHtml(fieldName) + '" id="' + escapeHtml(fieldId) + '" class="form-control" data-contribution-value-input>';
                            html += '<option value=""' + (value === '' ? ' selected' : '') + '>' + escapeHtml(text.selectYesNo) + '</option>';
                            html += '<option value="1"' + (value === '1' ? ' selected' : '') + '>' + escapeHtml(text.yes) + '</option>';
                            html += '<option value="0"' + (value === '0' ? ' selected' : '') + '>' + escapeHtml(text.no) + '</option>';
                            html += '</select>';
                            break;

                        case 'int':
                        case 'decimal':
                            html += '<input type="number" class="form-control" name="' + escapeHtml(fieldName) + '" id="' + escapeHtml(fieldId) + '" value="' + escapeHtml(value) + '"';
                            if (definition.constraints && definition.constraints.min !== undefined && definition.constraints.min !== null && definition.constraints.min !== '') {
                                html += ' min="' + escapeHtml(definition.constraints.min) + '"';
                            }
                            if (definition.constraints && definition.constraints.max !== undefined && definition.constraints.max !== null && definition.constraints.max !== '') {
                                html += ' max="' + escapeHtml(definition.constraints.max) + '"';
                            }
                            if (definition.constraints && definition.constraints.step !== undefined && definition.constraints.step !== null && definition.constraints.step !== '') {
                                html += ' step="' + escapeHtml(definition.constraints.step) + '"';
                            } else if (definition.datatype === 'int') {
                                html += ' step="1"';
                            } else {
                                html += ' step="any"';
                            }
                            html += ' data-contribution-value-input>';
                            break;

                        case 'enum':
                            if (definition.allow_custom_values) {
                                html += '<input type="text" class="form-control" name="' + escapeHtml(fieldName) + '" id="' + escapeHtml(fieldId) + '" value="' + escapeHtml(value) + '" list="' + escapeHtml(fieldId + '_options') + '" data-contribution-value-input>';
                                html += '<datalist id="' + escapeHtml(fieldId + '_options') + '">';
                                (definition.options || []).forEach(function (option) {
                                    html += '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label || option.value) + '</option>';
                                });
                                html += '</datalist>';
                                html += '<span class="help-block">' + escapeHtml(text.customEnumHelp) + '</span>';
                            } else {
                                html += '<select name="' + escapeHtml(fieldName) + '" id="' + escapeHtml(fieldId) + '" class="form-control" data-contribution-value-input>';
                                html += '<option value=""' + (value === '' ? ' selected' : '') + '>' + escapeHtml(text.selectOption) + '</option>';
                                (definition.options || []).forEach(function (option) {
                                    html += '<option value="' + escapeHtml(option.value) + '"' + (value === String(option.value) ? ' selected' : '') + '>' + escapeHtml(option.label || option.value) + '</option>';
                                });
                                html += '</select>';
                                html += '<span class="help-block">' + escapeHtml(text.strictEnumHelp) + '</span>';
                            }
                            break;

                        default:
                            html += '<input type="text" class="form-control" name="' + escapeHtml(fieldName) + '" id="' + escapeHtml(fieldId) + '" value="' + escapeHtml(value) + '" data-contribution-value-input>';
                            break;
                    }

                    var summary = [text.datatype + ': ' + definition.datatype.charAt(0).toUpperCase() + definition.datatype.slice(1)];
                    if (definition.unit) {
                        summary.push(text.unit + ': ' + definition.unit);
                    }
                    summary = summary.concat(buildConstraintSummary(definition));

                    if (definition.is_numeric) {
                        html += '<div class="checkbox" style="margin-top:10px; margin-bottom:6px;">';
                        html += '<label><input type="checkbox" name="' + escapeHtml(resolveFieldName) + '" value="1"' + (resolveChecked ? ' checked' : '') + '> ' + escapeHtml(text.resolveToSpec) + '</label>';
                        html += '</div>';
                        html += '<p class="help-block text-muted" style="margin-top:0;">' + escapeHtml(text.resolveToSpecHelp) + '</p>';
                    }

                    html += '<p class="help-block text-muted" style="margin-bottom:0;">' + escapeHtml(summary.join(' - ')) + '</p>';

                    field.dataset.currentValue = value;
                    field.dataset.currentResolves = definition.is_numeric && resolveChecked ? '1' : '0';
                    field.innerHTML = html;
                }

                function matchingDefinitions(term) {
                    var query = normalize(term);
                    var matches = definitions.filter(function (definition) {
                        if (query === '') {
                            return true;
                        }

                        return definition.search_text.indexOf(query) !== -1;
                    });

                    return matches.slice(0, 8);
                }

                function exactMatchDefinition(term) {
                    var query = normalize(term);

                    if (query === '') {
                        return null;
                    }

                    return definitions.find(function (definition) {
                        return [definition.display, definition.label, definition.key].some(function (candidate) {
                            return normalize(candidate) === query;
                        });
                    }) || null;
                }

                function hideAllSearchResults(exceptRow) {
                    document.querySelectorAll('[data-contribution-search-results]').forEach(function (results) {
                        if (exceptRow && exceptRow.contains(results)) {
                            return;
                        }

                        results.hidden = true;
                        results.innerHTML = '';
                    });
                }

                function renderSearchResults(row) {
                    var searchInput = row ? row.querySelector('[data-contribution-attribute-search]') : null;
                    var results = row ? row.querySelector('[data-contribution-search-results]') : null;

                    if (!searchInput || !results) {
                        return;
                    }

                    var matches = matchingDefinitions(searchInput.value);

                    if (matches.length === 0) {
                        results.innerHTML = '<div class="list-group-item text-muted">' + escapeHtml(text.noMatchingAttributes) + '</div>';
                        results.hidden = false;
                        return;
                    }

                    results.innerHTML = matches.map(function (definition) {
                        return '<button type="button" class="list-group-item component-definition-attribute-result" data-contribution-select-definition="' + escapeHtml(definition.id) + '">' +
                            '<strong>' + escapeHtml(definition.label) + '</strong> ' +
                            '<span class="text-muted small">(' + escapeHtml(definition.key) + ')</span>' +
                        '</button>';
                    }).join('');
                    results.hidden = false;
                }

                function handleSearchTermChange(row, searchInput) {
                    if (normalize(searchInput.value) === '') {
                        applyDefinitionToRow(row, null, false);
                        hideAllSearchResults();
                        return;
                    }

                    renderSearchResults(row);
                }

                function applyDefinitionToRow(row, definition, preserveCurrentValue) {
                    var hiddenInput = row ? row.querySelector('[data-contribution-attribute-id]') : null;
                    var searchInput = row ? row.querySelector('[data-contribution-attribute-search]') : null;
                    var field = row ? row.querySelector('[data-contribution-value-field]') : null;
                    var previousId = hiddenInput ? String(hiddenInput.value || '') : '';
                    var currentValue = preserveCurrentValue ? readCurrentValue(row) : '';

                    if (!hiddenInput || !searchInput) {
                        return;
                    }

                    if (!definition) {
                        hiddenInput.value = '';
                        field.dataset.currentResolves = '0';
                        renderValueField(row, null, '');
                        return;
                    }

                    hiddenInput.value = String(definition.id);
                    searchInput.value = definition.display;
                    if (!preserveCurrentValue || previousId !== String(definition.id)) {
                        field.dataset.currentResolves = '0';
                    }
                    renderValueField(row, definition, preserveCurrentValue && previousId === String(definition.id) ? currentValue : '');
                    hideAllSearchResults();
                }

                function selectDefinitionFromButton(button) {
                    var row = button ? button.closest('[data-contribution-row]') : null;
                    var definition = definitionMap[String(button ? (button.getAttribute('data-contribution-select-definition') || '') : '')];

                    if (!row || !definition) {
                        return;
                    }

                    applyDefinitionToRow(row, definition, false);

                    var valueInput = getValueInput(row);
                    if (valueInput) {
                        valueInput.focus();
                    }
                }

                function wireContributionRow(row) {
                    if (!row || row.dataset.contributionReady === '1') {
                        return;
                    }

                    row.dataset.contributionReady = '1';

                    var hiddenInput = row.querySelector('[data-contribution-attribute-id]');
                    var searchInput = row.querySelector('[data-contribution-attribute-search]');
                    var definition = hiddenInput ? definitionMap[String(hiddenInput.value || '')] : null;

                    renderValueField(row, definition || null, readCurrentValue(row));

                    if (!searchInput || !hiddenInput) {
                        return;
                    }

                    searchInput.addEventListener('focus', function () {
                        hideAllSearchResults(row);
                        renderSearchResults(row);
                    });

                    searchInput.addEventListener('input', function () {
                        handleSearchTermChange(row, searchInput);
                    });

                    searchInput.addEventListener('search', function () {
                        handleSearchTermChange(row, searchInput);
                    });

                    searchInput.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter') {
                            return;
                        }

                        var firstResult = row.querySelector('[data-contribution-select-definition]');
                        if (!firstResult) {
                            return;
                        }

                        event.preventDefault();
                        firstResult.click();
                    });

                    searchInput.addEventListener('blur', function () {
                        window.setTimeout(function () {
                            var matched = exactMatchDefinition(searchInput.value);
                            if (matched) {
                                applyDefinitionToRow(row, matched, true);
                                return;
                            }

                            var selected = definitionMap[String(hiddenInput.value || '')];
                            if (selected && [selected.display, selected.label, selected.key].some(function (candidate) {
                                return normalize(candidate) === normalize(searchInput.value);
                            })) {
                                searchInput.value = selected.display;
                                hideAllSearchResults();
                                return;
                            }

                            if (normalize(searchInput.value) === '') {
                                applyDefinitionToRow(row, null, false);
                            } else {
                                hiddenInput.value = '';
                                renderValueField(row, null, '');
                            }

                            hideAllSearchResults();
                        }, 150);
                    });
                }

                function ensureOneRow(wrapper) {
                    if (!wrapper) {
                        return;
                    }

                    if (wrapper.querySelectorAll('[data-contribution-row]').length > 0) {
                        return;
                    }

                    var template = document.querySelector('[data-contribution-template]');
                    if (!template) {
                        return;
                    }

                    var nextIndex = parseInt(wrapper.dataset.nextIndex || '0', 10);
                    wrapper.dataset.nextIndex = String(nextIndex + 1);
                    wrapper.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
                    wireContributionRow(wrapper.querySelector('[data-contribution-row]:last-child'));
                }

                document.querySelectorAll('[data-contribution-row]').forEach(function (row) {
                    wireContributionRow(row);
                });

                document.addEventListener('mousedown', function (event) {
                    var definitionButton = event.target.closest('[data-contribution-select-definition]');
                    if (!definitionButton) {
                        return;
                    }

                    event.preventDefault();
                    selectDefinitionFromButton(definitionButton);
                });

                document.addEventListener('click', function (event) {
                    var definitionButton = event.target.closest('[data-contribution-select-definition]');
                    if (definitionButton) {
                        event.preventDefault();
                        selectDefinitionFromButton(definitionButton);
                        return;
                    }

                    var addButton = event.target.closest('[data-add-contribution]');
                    if (addButton) {
                        var wrapper = findContributionWrapper();
                        var template = document.querySelector('[data-contribution-template]');

                        if (!wrapper || !template) {
                            return;
                        }

                        var nextIndex = parseInt(wrapper.dataset.nextIndex || '0', 10);
                        wrapper.dataset.nextIndex = String(nextIndex + 1);
                        wrapper.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
                        wireContributionRow(wrapper.querySelector('[data-contribution-row]:last-child'));
                        return;
                    }

                    var removeButton = event.target.closest('[data-remove-contribution]');
                    if (!removeButton) {
                        if (!event.target.closest('[data-contribution-row]')) {
                            hideAllSearchResults();
                        }
                        return;
                    }

                    var row = removeButton.closest('[data-contribution-row]');
                    var wrapper = findContributionWrapper();

                    if (!row || !wrapper) {
                        return;
                    }

                    row.remove();
                    ensureOneRow(wrapper);
                });
            })();
        </script>
    @endpush
@endonce
