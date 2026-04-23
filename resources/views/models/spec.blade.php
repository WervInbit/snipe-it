@extends('layouts/edit-form', [
    'updateText' => __('Save Specification'),
    'helpText' => __('Fill in the final specifications for this model. Assets inherit these values by default.'),
    'helpPosition' => 'right',
    'formAction' => route('models.spec.update', $model),
    'method' => 'PUT',
    'showSubmit' => (bool) $modelNumber,
])

@push('css')
    <style nonce="{{ csrf_token() }}">
        .model-attributes-builder .list-group {
            max-height: 360px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .available-attribute {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .available-attribute__info {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .selected-attribute-item__info {
            flex: 1;
            min-width: 0;
            padding-right: 10px;
        }

        .available-attribute.is-assigned {
            opacity: 0.45;
        }

        .available-attribute.is-assigned .js-add-attribute {
            pointer-events: none;
        }

        .available-attribute__actions {
            flex-shrink: 0;
        }

        .selected-attribute-item.active {
            background-color: #f5f5f5;
            border-left: 3px solid #337ab7;
            color: #222;
        }

        .selected-attribute-item.active .selected-attribute-item__info strong {
            color: #000;
        }

        .selected-attribute-item.active small {
            color: #555;
        }

        .selected-attribute-item--error {
            border-left: 3px solid #d9534f;
            background-color: #fff5f5;
        }

        .selected-attribute-item__error-badge {
            margin-left: 6px;
        }

        .selected-attribute-item__body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .selected-attribute-item__actions {
            display: flex;
            flex-shrink: 0;
        }

        .attribute-detail-panel {
            margin-bottom: 20px;
        }

        .attribute-detail-panel--error {
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 10px;
            background-color: #fff9f9;
        }

        .attribute-detail-empty {
            margin-top: 15px;
        }

        .attribute-column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .attribute-column-header h4 {
            margin: 0;
        }

        .model-spec-error-list {
            margin: 10px 0 0;
            padding: 0;
            list-style: none;
        }

        .model-spec-error-list li + li {
            margin-top: 6px;
        }

        .model-spec-error-link {
            display: block;
            width: 100%;
            text-align: left;
            white-space: normal;
            padding: 8px 10px;
        }

        .model-spec-error-link__label {
            font-weight: 700;
            margin-right: 6px;
        }

        .model-spec-preview-table td,
        .model-spec-preview-table th {
            vertical-align: top;
        }

        .component-template-row + .component-template-row {
            margin-top: 12px;
        }

        .component-template-row.is-dragging {
            opacity: 0.65;
        }

        .component-template-row__main {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .component-template-row__drag {
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            width: 44px;
        }

        .component-template-row__drag-handle {
            cursor: move;
        }

        .component-template-row__actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .component-template-empty {
            margin-bottom: 15px;
        }
    </style>
@endpush

@section('inputFields')
    @php
        $assignedDefinitionIds = $selectedDefinitionIds ?? [];
        $componentDefinitions = $componentDefinitions ?? collect();
        $componentDefinitionsById = $componentDefinitions->keyBy('id');
    @endphp
    @php
        $attributeErrorItems = collect();
        $componentTemplateErrorItems = collect();
        $invalidAttributeIds = collect();
        $generalSpecErrors = collect();
        $componentTemplateFieldLabels = [
            'component_definition_id' => __('Catalog Definition'),
            'expected_qty' => trans('general.qty'),
        ];

        foreach ($errors->getMessages() as $errorKey => $messages) {
            $messages = collect($messages)->filter()->values();
            if ($messages->isEmpty()) {
                continue;
            }

            if (preg_match('/^attributes\.(\d+)$/', $errorKey, $matches)) {
                $definitionId = (int) $matches[1];
                $definition = $definitionsById->get($definitionId);

                $attributeErrorItems->push([
                    'id' => $definitionId,
                    'label' => $definition?->label ?: __('Attribute #:id', ['id' => $definitionId]),
                    'message' => $messages->first(),
                ]);
                $invalidAttributeIds->push($definitionId);
                continue;
            }

            if (preg_match('/^component_templates\.(\d+)\.(.+)$/', $errorKey, $matches)) {
                $rowIndex = (int) $matches[1];
                $fieldKey = $matches[2];

                $componentTemplateErrorItems->push([
                    'row' => $rowIndex,
                    'field' => $fieldKey,
                    'label' => __('Expected component #:row - :field', [
                        'row' => $rowIndex + 1,
                        'field' => $componentTemplateFieldLabels[$fieldKey] ?? $fieldKey,
                    ]),
                    'message' => $messages->first(),
                ]);
                continue;
            }

            $generalSpecErrors = $generalSpecErrors->merge($messages);
        }

        $invalidAttributeIds = $invalidAttributeIds->unique()->values();
        $attributeErrorItems = $attributeErrorItems->unique('id')->values();
        $componentTemplateErrorItems = $componentTemplateErrorItems
            ->unique(fn ($item) => $item['row'] . ':' . $item['field'])
            ->values();
        $componentTemplateRows = collect();

        if (($modelNumber ?? null) !== null) {
            $componentTemplateRows = collect(old('component_templates', []));

            if ($componentTemplateRows->isEmpty()) {
                $componentTemplateRows = $modelNumber->componentTemplates->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'component_definition_id' => $template->component_definition_id,
                        'expected_qty' => $template->expected_qty,
                    ];
                });
            }

        }
    @endphp

    @if(($modelNumbers ?? collect())->isEmpty() || !$modelNumber)
        <div class="alert alert-info">
            {{ __('Add a model number to this model to edit its specification.') }}
        </div>
        <a href="{{ route('models.numbers.create', $model) }}" class="btn btn-primary">{{ __('Create Model Number') }}</a>
    @else
        @if($errors->any())
            <div class="col-md-12">
                <div class="alert alert-danger">
                    <strong>{{ __('Unable to save the specification.') }}</strong>
                    <p class="help-block">{{ __('Review the highlighted fields below. Allowed formats, ranges, and units are noted alongside each attribute when validation fails.') }}</p>
                    @if($attributeErrorItems->isNotEmpty())
                        <div data-testid="model-spec-error-navigator">
                            <p class="help-block"><strong>{{ __('Fields with issues') }}</strong></p>
                            <ul class="model-spec-error-list">
                                @foreach($attributeErrorItems as $errorItem)
                                    <li>
                                        <button type="button"
                                                class="btn btn-default btn-sm model-spec-error-link js-attribute-error-link"
                                                data-testid="model-spec-error-link"
                                                data-attribute-id="{{ $errorItem['id'] }}">
                                            <span class="model-spec-error-link__label">{{ $errorItem['label'] }}</span>
                                            <span>{{ $errorItem['message'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($componentTemplateErrorItems->isNotEmpty())
                        <div data-testid="component-template-error-navigator">
                            <p class="help-block"><strong>{{ __('Expected component rows with issues') }}</strong></p>
                            <ul class="model-spec-error-list">
                                @foreach($componentTemplateErrorItems as $errorItem)
                                    <li>
                                        <button type="button"
                                                class="btn btn-default btn-sm model-spec-error-link js-component-template-error-link"
                                                data-component-template-row="{{ $errorItem['row'] }}">
                                            <span class="model-spec-error-link__label">{{ $errorItem['label'] }}</span>
                                            <span>{{ $errorItem['message'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($generalSpecErrors->isNotEmpty())
                        <ul class="mb-0">
                            @foreach($generalSpecErrors as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        <input type="hidden" name="model_number_id" id="model_spec_model_number_id" value="{{ $modelNumber->id }}">

        <div class="form-group">
            <label class="col-md-3 control-label" for="model_spec_model_number_selector">{{ __('Model Number') }}</label>
            <div class="col-md-7">
                <select id="model_spec_model_number_selector" class="form-control">
                    @foreach($modelNumbers as $number)
                        <option value="{{ $number->id }}" {{ $number->id === $modelNumber->id ? 'selected' : '' }}>
                            {{ $number->label ?: $number->code }}@if($number->isDeprecated()) ({{ __('deprecated') }})@endif
                        </option>
                    @endforeach
                </select>
                <p class="help-block">
                    {{ __('Switch presets to review or edit their specification values.') }}
                </p>
                @if($modelNumber && $modelNumber->isDeprecated())
                    <p class="help-block text-warning">{{ __('This preset is deprecated and hidden from new assets.') }}</p>
                @endif
            </div>
        </div>

        <div class="model-attributes-builder row"
             data-testid="model-attributes-builder"
             data-invalid-attribute-ids="{{ $invalidAttributeIds->implode(',') }}">
            <div class="col-md-4 attribute-column attribute-column--available">
                <div class="attribute-column-header">
                    <h4>{{ __('Available Attributes') }}</h4>
                </div>
                <input type="search" class="form-control input-sm js-attribute-search" placeholder="{{ __('Search attributes...') }}" data-target="#available-attributes-list">
                <ul class="list-group js-available-list" id="available-attributes-list">
                    @forelse($availableAttributes as $definition)
                        @php($isAssigned = in_array($definition->id, $assignedDefinitionIds, true))
                        @php($searchText = strtolower($definition->label.' '.$definition->key))
                        <li class="list-group-item available-attribute{{ $isAssigned ? ' is-assigned' : '' }}" data-attribute-id="{{ $definition->id }}" data-search-text="{{ $searchText }}">
                            <div class="available-attribute__info">
                                <strong>{{ $definition->label }}</strong>
                                <span class="text-muted small">({{ $definition->key }})</span>
                            </div>
                            <div class="available-attribute__actions">
                                <button type="button" class="btn btn-xs btn-primary js-add-attribute" data-definition-id="{{ $definition->id }}" {{ $isAssigned ? 'disabled' : '' }}>
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('No attributes are available for this category yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="col-md-4 attribute-column attribute-column--selected">
                <div class="attribute-column-header">
                    <h4>{{ __('Selected Attributes') }}</h4>
                </div>
                <input type="search" class="form-control input-sm js-attribute-search" placeholder="{{ __('Search attributes...') }}" data-target="#selected-attributes-list">
                <ul class="list-group js-selected-list" id="selected-attributes-list">
                    @forelse($selectedDefinitionIds as $definitionId)
                        @php($definition = $definitionsById->get($definitionId))
                        @if(!$definition)
                            @continue
                        @endif
                        @include('models.model_numbers.partials.selected-attribute-item', ['definition' => $definition])
                    @empty
                        <li class="list-group-item text-muted js-selected-empty">{{ __('No attributes selected.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="col-md-4 attribute-column attribute-column--details">
                <div class="attribute-column-header">
                    <h4>{{ __('Attribute Details') }}</h4>
                </div>
                <div class="attribute-detail-container">
                    @foreach($selectedDefinitionIds as $definitionId)
                        @php($resolved = $resolvedAttributes->get($definitionId))
                        @if(!$resolved)
                            @continue
                        @endif
                        @include('models.model_numbers.partials.attribute-detail', ['resolved' => $resolved])
                    @endforeach
                    <div class="attribute-detail-empty text-muted" {{ empty($selectedDefinitionIds) ? '' : 'hidden' }}>
                        <p>{{ __('Select an attribute to configure its default value.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="expected-components">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Expected Components') }}</h3>
                    </div>
                    <div class="box-body">
                        <p class="text-muted">
                            {{ __('Expected components stay separate from manual attributes and define the baseline component set for assets using this model number.') }}
                        </p>
                        <p class="text-muted small">
                            {{ __('Expected components added here are required by default. Drag rows to reorder them, or use the Up and Down buttons.') }}
                        </p>

                        <div data-component-template-rows data-next-index="{{ $componentTemplateRows->count() }}">
                            <div class="alert alert-info component-template-empty{{ $componentTemplateRows->isNotEmpty() ? ' hidden' : '' }}" data-component-template-empty>
                                {{ __('No expected components added yet.') }}
                            </div>
                            @foreach($componentTemplateRows->values() as $index => $row)
                                @php($selectedComponentDefinition = $componentDefinitionsById->get((int) ($row['component_definition_id'] ?? 0)))
                                @php($componentRowHasError = collect(array_keys($errors->getMessages()))->contains(fn ($key) => str_starts_with($key, 'component_templates.' . $index . '.')))
                                <div class="panel panel-default component-template-row{{ $componentRowHasError ? ' has-error' : '' }}" data-component-template-row data-component-template-row-index="{{ $index }}">
                                    <div class="panel-body">
                                        <input type="hidden" name="component_templates[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                        <div class="component-template-row__main">
                                            <div class="component-template-row__drag">
                                                <button type="button" class="btn btn-default btn-sm component-template-row__drag-handle" data-component-template-drag-handle draggable="true" title="{{ __('Drag to reorder') }}">
                                                    <i class="fa fa-bars" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <div class="row" style="flex:1; margin-left:0; margin-right:0;">
                                                <div class="col-md-8 form-group {{ $errors->has('component_templates.' . $index . '.component_definition_id') ? 'has-error' : '' }}">
                                                    <label>{{ __('Catalog Definition') }}</label>
                                                    <select name="component_templates[{{ $index }}][component_definition_id]" class="form-control">
                                                        <option value="">{{ __('Select a component definition') }}</option>
                                                        @foreach($componentDefinitions as $componentDefinition)
                                                            <option value="{{ $componentDefinition->id }}" @selected((string) ($row['component_definition_id'] ?? '') === (string) $componentDefinition->id)>
                                                                {{ $componentDefinition->name }}
                                                                @if($componentDefinition->manufacturer)
                                                                    - {{ $componentDefinition->manufacturer->name }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    {!! $errors->first('component_templates.' . $index . '.component_definition_id', '<span class="help-block">:message</span>') !!}
                                                </div>
                                                <div class="col-md-2 form-group {{ $errors->has('component_templates.' . $index . '.expected_qty') ? 'has-error' : '' }}">
                                                    <label>{{ trans('general.qty') }}</label>
                                                    <input type="number" min="1" class="form-control" name="component_templates[{{ $index }}][expected_qty]" value="{{ $row['expected_qty'] ?? 1 }}">
                                                    {!! $errors->first('component_templates.' . $index . '.expected_qty', '<span class="help-block">:message</span>') !!}
                                                </div>
                                                <div class="col-md-2 form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="component-template-row__actions">
                                                        <button type="button" class="btn btn-default btn-sm js-component-template-move-up">{{ __('Up') }}</button>
                                                        <button type="button" class="btn btn-default btn-sm js-component-template-move-down">{{ __('Down') }}</button>
                                                        <button type="button" class="btn btn-default btn-sm js-component-template-remove">{{ __('Remove') }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if($selectedComponentDefinition && $selectedComponentDefinition->attributeContributions->isNotEmpty())
                                            <div class="text-muted small">
                                                {{ __('Derived attributes: :attributes', [
                                                    'attributes' => $selectedComponentDefinition->attributeContributions->map(function ($contribution) {
                                                        $label = $contribution->definition?->label ?: __('Unknown attribute');
                                                        $value = $contribution->value;

                                                        return $label . ': ' . $value;
                                                    })->implode(', '),
                                                ]) }}
                                            </div>
                                        @elseif($selectedComponentDefinition)
                                            <div class="text-muted small">{{ __('This definition does not contribute any shared attributes yet.') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <template data-component-template-template>
                            <div class="panel panel-default component-template-row" data-component-template-row>
                                <div class="panel-body">
                                    <input type="hidden" name="component_templates[__INDEX__][id]" value="">
                                    <div class="component-template-row__main">
                                        <div class="component-template-row__drag">
                                            <button type="button" class="btn btn-default btn-sm component-template-row__drag-handle" data-component-template-drag-handle draggable="true" title="{{ __('Drag to reorder') }}">
                                                <i class="fa fa-bars" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="row" style="flex:1; margin-left:0; margin-right:0;">
                                            <div class="col-md-8 form-group">
                                                <label>{{ __('Catalog Definition') }}</label>
                                                <select name="component_templates[__INDEX__][component_definition_id]" class="form-control">
                                                    <option value="">{{ __('Select a component definition') }}</option>
                                                    @foreach($componentDefinitions as $componentDefinition)
                                                        <option value="{{ $componentDefinition->id }}">
                                                            {{ $componentDefinition->name }}
                                                            @if($componentDefinition->manufacturer)
                                                                - {{ $componentDefinition->manufacturer->name }}
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 form-group">
                                                <label>{{ trans('general.qty') }}</label>
                                                <input type="number" min="1" class="form-control" name="component_templates[__INDEX__][expected_qty]" value="1">
                                            </div>
                                            <div class="col-md-2 form-group">
                                                <label>&nbsp;</label>
                                                <div class="component-template-row__actions">
                                                    <button type="button" class="btn btn-default btn-sm js-component-template-move-up">{{ __('Up') }}</button>
                                                    <button type="button" class="btn btn-default btn-sm js-component-template-move-down">{{ __('Down') }}</button>
                                                    <button type="button" class="btn btn-default btn-sm js-component-template-remove">{{ __('Remove') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" class="btn btn-default" data-add-component-template>{{ __('Add Expected Component') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="attribute-template-cache" hidden>
            @foreach($definitionsById as $definition)
                <template class="js-selected-template" data-attribute-id="{{ $definition->id }}">
                    @include('models.model_numbers.partials.selected-attribute-item', ['definition' => $definition])
                </template>
                @php($templateResolved = $resolvedAttributes->get($definition->id))
                @if($templateResolved)
                    <template class="js-detail-template" data-attribute-id="{{ $definition->id }}">
                        @include('models.model_numbers.partials.attribute-detail', ['resolved' => $templateResolved])
                    </template>
                @endif
            @endforeach
        </div>
    @endif
@endsection

@section('moar_scripts')
    @parent
        <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            var selector = document.getElementById('model_spec_model_number_selector');
            var hidden = document.getElementById('model_spec_model_number_id');

            if (selector) {
                selector.addEventListener('change', function () {
                    if (hidden) {
                        hidden.value = this.value;
                    }

                    var url = new URL(window.location.href);
                    url.searchParams.set('model_number_id', this.value);
                    window.location.href = url.toString();
                });
            }

            var componentTemplateRoot = document.querySelector('[data-component-template-rows]');
            var componentTemplateTemplate = document.querySelector('[data-component-template-template]');
            var componentTemplateEmpty = document.querySelector('[data-component-template-empty]');
            var draggedComponentTemplateRow = null;

            function getComponentTemplateRows() {
                if (!componentTemplateRoot) {
                    return [];
                }

                return Array.from(componentTemplateRoot.querySelectorAll('[data-component-template-row]'));
            }

            function refreshComponentTemplateState() {
                var rows = getComponentTemplateRows();

                if (componentTemplateEmpty) {
                    componentTemplateEmpty.classList.toggle('hidden', rows.length > 0);
                }

                rows.forEach(function (row, index) {
                    var up = row.querySelector('.js-component-template-move-up');
                    var down = row.querySelector('.js-component-template-move-down');

                    if (up) {
                        up.disabled = index === 0;
                    }

                    if (down) {
                        down.disabled = index === rows.length - 1;
                    }
                });
            }

            function moveComponentTemplateRow(row, sibling, insertBefore) {
                if (!componentTemplateRoot || !row || !sibling) {
                    return;
                }

                if (insertBefore) {
                    componentTemplateRoot.insertBefore(row, sibling);
                } else {
                    componentTemplateRoot.insertBefore(sibling, row);
                }

                refreshComponentTemplateState();
            }

            function appendBlankComponentTemplateRow() {
                if (!componentTemplateRoot || !componentTemplateTemplate) {
                    return;
                }

                var nextIndex = parseInt(componentTemplateRoot.dataset.nextIndex || '0', 10);
                componentTemplateRoot.dataset.nextIndex = String(nextIndex + 1);
                componentTemplateRoot.insertAdjacentHTML('beforeend', componentTemplateTemplate.innerHTML.replace(/__INDEX__/g, String(nextIndex)));
                refreshComponentTemplateState();
            }

            document.addEventListener('click', function (event) {
                var addComponentTemplateButton = event.target.closest('[data-add-component-template]');
                if (addComponentTemplateButton) {
                    appendBlankComponentTemplateRow();
                    return;
                }

                var componentTemplateAction = event.target.closest('.js-component-template-remove, .js-component-template-move-up, .js-component-template-move-down');
                if (!componentTemplateAction || !componentTemplateRoot) {
                    return;
                }

                var row = componentTemplateAction.closest('[data-component-template-row]');
                if (!row) {
                    return;
                }

                if (componentTemplateAction.classList.contains('js-component-template-remove')) {
                    row.remove();
                    refreshComponentTemplateState();
                    return;
                }

                var sibling = componentTemplateAction.classList.contains('js-component-template-move-up')
                    ? row.previousElementSibling
                    : row.nextElementSibling;

                while (sibling && !sibling.hasAttribute('data-component-template-row')) {
                    sibling = componentTemplateAction.classList.contains('js-component-template-move-up')
                        ? sibling.previousElementSibling
                        : sibling.nextElementSibling;
                }

                if (!sibling) {
                    return;
                }

                if (componentTemplateAction.classList.contains('js-component-template-move-up')) {
                    moveComponentTemplateRow(row, sibling, true);
                } else {
                    moveComponentTemplateRow(row, sibling, false);
                }
            });
            if (componentTemplateRoot) {
                componentTemplateRoot.addEventListener('dragstart', function (event) {
                    var handle = event.target.closest('[data-component-template-drag-handle]');
                    var row = handle ? handle.closest('[data-component-template-row]') : null;
                    if (!row || !handle) {
                        event.preventDefault();
                        return;
                    }

                    draggedComponentTemplateRow = row;
                    row.classList.add('is-dragging');

                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', row.dataset.componentTemplateRowIndex || '');
                    }
                });

                componentTemplateRoot.addEventListener('dragover', function (event) {
                    var row = event.target.closest('[data-component-template-row]');
                    if (!draggedComponentTemplateRow || !row || row === draggedComponentTemplateRow) {
                        return;
                    }

                    event.preventDefault();
                    if (event.dataTransfer) {
                        event.dataTransfer.dropEffect = 'move';
                    }
                });

                componentTemplateRoot.addEventListener('drop', function (event) {
                    var row = event.target.closest('[data-component-template-row]');
                    if (!draggedComponentTemplateRow || !row || row === draggedComponentTemplateRow) {
                        return;
                    }

                    event.preventDefault();

                    var bounds = row.getBoundingClientRect();
                    var insertBefore = event.clientY < (bounds.top + bounds.height / 2);
                    moveComponentTemplateRow(draggedComponentTemplateRow, row, insertBefore);
                });

                componentTemplateRoot.addEventListener('dragend', function () {
                    if (draggedComponentTemplateRow) {
                        draggedComponentTemplateRow.classList.remove('is-dragging');
                    }

                    draggedComponentTemplateRow = null;
                });
            }

            refreshComponentTemplateState();

            var builder = document.querySelector('.model-attributes-builder');
            if (!builder) {
                return;
            }

            var availableList = builder.querySelector('.js-available-list');
            var selectedList = builder.querySelector('.js-selected-list');
            var detailContainer = builder.querySelector('.attribute-detail-container');
            var detailEmpty = detailContainer ? detailContainer.querySelector('.attribute-detail-empty') : null;
            var templateRoot = document.querySelector('.attribute-template-cache');
            var invalidAttributeIds = (builder.dataset.invalidAttributeIds || '')
                .split(',')
                .map(function (id) { return id.trim(); })
                .filter(function (id) { return id !== ''; });

            if (!selectedList || !detailContainer) {
                return;
            }

            function cloneTemplate(selector) {
                if (!templateRoot) {
                    return null;
                }

                var template = templateRoot.querySelector(selector);
                if (!template || !template.content.firstElementChild) {
                    return null;
                }

                return template.content.firstElementChild.cloneNode(true);
            }

            function getSelectedItems() {
                return Array.from(selectedList.querySelectorAll('.selected-attribute-item'));
            }

            function refreshReorderState() {
                var items = getSelectedItems();

                items.forEach(function (item, index) {
                    var up = item.querySelector('.js-move-up');
                    var down = item.querySelector('.js-move-down');

                    if (up) {
                        up.disabled = index === 0;
                    }

                    if (down) {
                        down.disabled = index === items.length - 1;
                    }
                });
            }

            function markAvailable(attributeId, assigned) {
                if (!availableList) {
                    return;
                }

                var row = availableList.querySelector('.available-attribute[data-attribute-id="' + attributeId + '"]');
                if (!row) {
                    return;
                }

                row.classList.toggle('is-assigned', assigned);

                var addButton = row.querySelector('.js-add-attribute');
                if (addButton) {
                    addButton.disabled = assigned;
                }
            }

            function selectAttribute(attributeId) {
                var items = getSelectedItems();
                var hasSelection = false;

                items.forEach(function (item) {
                    if (item.dataset.attributeId === attributeId) {
                        item.classList.add('active');
                        hasSelection = true;
                    } else {
                        item.classList.remove('active');
                    }
                });

                detailContainer.querySelectorAll('.attribute-detail-panel').forEach(function (panel) {
                    panel.hidden = panel.dataset.attributeId !== attributeId;
                });

                if (detailEmpty) {
                    detailEmpty.hidden = hasSelection;
                }
            }

            function selectAndFocusAttribute(attributeId) {
                if (!attributeId) {
                    return;
                }

                selectAttribute(attributeId);

                var panel = detailContainer.querySelector('.attribute-detail-panel[data-attribute-id="' + attributeId + '"]');
                if (panel && typeof panel.scrollIntoView === 'function') {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                focusDetailInput(attributeId);
            }

            function focusDetailInput(attributeId) {
                var panel = detailContainer.querySelector('.attribute-detail-panel[data-attribute-id="' + attributeId + '"]');
                if (!panel) {
                    return;
                }

                var input = panel.querySelector('input, select, textarea');
                if (input) {
                    input.focus();
                }
            }

            function selectFirstAttribute() {
                var first = getSelectedItems()[0];
                if (first) {
                    selectAttribute(first.dataset.attributeId);
                } else if (detailEmpty) {
                    detailEmpty.hidden = false;
                }
            }

            function selectFirstInvalidOrFirstAttribute() {
                for (var i = 0; i < invalidAttributeIds.length; i += 1) {
                    var invalidId = invalidAttributeIds[i];
                    var invalidItem = selectedList.querySelector('.selected-attribute-item[data-attribute-id="' + invalidId + '"]');
                    if (invalidItem) {
                        selectAndFocusAttribute(invalidId);
                        return;
                    }
                }

                selectFirstAttribute();
            }

            function filterList(input) {
                var target = input.dataset.target;
                if (!target) {
                    return;
                }

                var list = document.querySelector(target);
                if (!list) {
                    return;
                }

                var term = input.value.trim().toLowerCase();

                Array.from(list.querySelectorAll('[data-search-text]')).forEach(function (item) {
                    var text = item.dataset.searchText || '';
                    item.hidden = term !== '' && text.indexOf(term) === -1;
                });
            }

            builder.querySelectorAll('.js-attribute-search').forEach(function (input) {
                input.addEventListener('input', function () {
                    filterList(input);
                });
            });

            if (availableList) {
                availableList.querySelectorAll('.js-add-attribute').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var attributeId = button.dataset.definitionId;
                        if (!attributeId) {
                            return;
                        }

                        var existing = selectedList.querySelector('.selected-attribute-item[data-attribute-id="' + attributeId + '"]');
                        if (existing) {
                            selectAttribute(attributeId);
                            focusDetailInput(attributeId);
                            return;
                        }

                        var selectedEmpty = selectedList.querySelector('.js-selected-empty');
                        if (selectedEmpty) {
                            selectedEmpty.remove();
                        }

                        var selectedNode = cloneTemplate('template.js-selected-template[data-attribute-id="' + attributeId + '"]');
                        if (!selectedNode) {
                            console.warn('Missing selected template for attribute', attributeId);
                            return;
                        }

                        selectedList.appendChild(selectedNode);

                        var detailNode = cloneTemplate('template.js-detail-template[data-attribute-id="' + attributeId + '"]');
                        if (detailNode) {
                            detailContainer.insertBefore(detailNode, detailEmpty);
                        }

                        if (detailEmpty) {
                            detailEmpty.hidden = true;
                        }

                        markAvailable(attributeId, true);
                        selectAttribute(attributeId);
                        refreshReorderState();
                        focusDetailInput(attributeId);
                    });
                });
            }

            if (selectedList) {
                selectedList.addEventListener('click', function (event) {
                    var item = event.target.closest('.selected-attribute-item');
                    if (!item) {
                        return;
                    }

                    var attributeId = item.dataset.attributeId;
                    var removeBtn = event.target.closest('.js-remove-assigned');
                    var moveUpBtn = event.target.closest('.js-move-up');
                    var moveDownBtn = event.target.closest('.js-move-down');

                    if (removeBtn) {
                        var detailPanel = detailContainer.querySelector('.attribute-detail-panel[data-attribute-id="' + attributeId + '"]');
                        if (detailPanel) {
                            detailPanel.remove();
                        }

                        markAvailable(attributeId, false);

                        item.remove();

                        if (getSelectedItems().length === 0) {
                            var placeholder = document.createElement('li');
                            placeholder.className = 'list-group-item text-muted js-selected-empty';
                            placeholder.textContent = "{{ __('No attributes selected.') }}";
                            selectedList.appendChild(placeholder);

                            if (detailEmpty) {
                                detailEmpty.hidden = false;
                            }
                        } else {
                            selectFirstAttribute();
                        }

                        refreshReorderState();
                        return;
                    }

                    if (moveUpBtn || moveDownBtn) {
                        var sibling = moveUpBtn ? item.previousElementSibling : item.nextElementSibling;

                        while (sibling && !sibling.classList.contains('selected-attribute-item')) {
                            sibling = moveUpBtn ? sibling.previousElementSibling : sibling.nextElementSibling;
                        }

                        if (sibling) {
                            if (moveUpBtn) {
                                selectedList.insertBefore(item, sibling);
                            } else {
                                selectedList.insertBefore(sibling, item);
                            }
                        }

                        refreshReorderState();
                        return;
                    }

                    selectAttribute(attributeId);
                    focusDetailInput(attributeId);
                });
            }

            document.querySelectorAll('.js-attribute-error-link').forEach(function (button) {
                button.addEventListener('click', function () {
                    var attributeId = button.dataset.attributeId;
                    if (!attributeId) {
                        return;
                    }

                    selectAndFocusAttribute(attributeId);
                });
            });

            document.querySelectorAll('.js-component-template-error-link').forEach(function (button) {
                button.addEventListener('click', function () {
                    var rowIndex = button.dataset.componentTemplateRow;
                    if (rowIndex === undefined) {
                        return;
                    }

                    var row = document.querySelector('[data-component-template-row-index="' + rowIndex + '"]');
                    if (!row) {
                        return;
                    }

                    if (typeof row.scrollIntoView === 'function') {
                        row.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    var input = row.querySelector('.has-error input, .has-error select, .has-error textarea, input, select, textarea');
                    if (input) {
                        input.focus();
                    }
                });
            });

            refreshReorderState();
            selectFirstInvalidOrFirstAttribute();
        });
    </script>
@endsection





