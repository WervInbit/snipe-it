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
    </style>
@endpush

@section('inputFields')
    @php($assignedDefinitionIds = $selectedDefinitionIds ?? [])

    @if(($modelNumbers ?? collect())->isEmpty() || !$modelNumber)
        <div class="alert alert-info">
            {{ __('Add a model number to this model to edit its specification.') }}
        </div>
        <a href="{{ route('models.numbers.create', $model) }}" class="btn btn-primary">{{ __('Create Model Number') }}</a>
    @else
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

        <div class="model-attributes-builder row">
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

            var builder = document.querySelector('.model-attributes-builder');
            if (!builder) {
                return;
            }

            var availableList = builder.querySelector('.js-available-list');
            var selectedList = builder.querySelector('.js-selected-list');
            var detailContainer = builder.querySelector('.attribute-detail-container');
            var detailEmpty = detailContainer ? detailContainer.querySelector('.attribute-detail-empty') : null;
            var templateRoot = document.querySelector('.attribute-template-cache');

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

            refreshReorderState();
            selectFirstAttribute();
        });
    </script>
@endsection





