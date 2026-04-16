@php
    use App\Models\AttributeDefinition;

    $versionSource = $versionSource ?? null;
    $isVersion = (bool) $versionSource;

    $existingOptions = $definition->relationLoaded('options') ? $definition->options : $definition->options;
    $versionOptions = $isVersion
        ? ($versionSource->relationLoaded('options') ? $versionSource->options : $versionSource->options)
        : collect();

    $pendingOptions = collect(old('options.new', []));
    if ($isVersion && $pendingOptions->isEmpty()) {
        $pendingOptions = $versionOptions->map(fn ($option) => [
            'value' => $option->value,
            'label' => $option->label,
            'sort_order' => $option->sort_order,
            'active' => $option->active ? 1 : 0,
        ]);
    }
    $pendingOptions = $pendingOptions
        ->filter(fn ($option) => is_array($option) && ($option['value'] ?? '') !== '' && ($option['label'] ?? '') !== '');

    $hasExisting = $existingOptions->count() > 0;
    $hasPending = $pendingOptions->count() > 0;

    $nextIndex = $pendingOptions->keys()->map(fn ($key) => (int) $key)->max();
    $nextIndex = is_null($nextIndex) ? 0 : $nextIndex + 1;

    $initialDatatype = old('datatype', $definition->datatype ?? ($versionSource->datatype ?? AttributeDefinition::DATATYPE_TEXT));
    $shouldShow = $initialDatatype === AttributeDefinition::DATATYPE_ENUM;
    $emptyColspan = $isVersion ? 5 : 4;
@endphp

@once
    @push('css')
        <style>
            .attribute-option-drag-cell {
                white-space: nowrap;
                width: 120px;
                vertical-align: middle;
            }
            .attribute-option-drag-content {
                display: flex;
                align-items: center;
                min-height: 34px;
            }
            .attribute-option-drag-handle {
                cursor: grab;
                font-weight: 700;
                letter-spacing: 1px;
                margin-right: 8px;
                user-select: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                font-size: 18px;
                line-height: 1;
                border: 1px solid #cfd8e3;
                border-radius: 6px;
                background: #f8fafc;
                color: #4b5563;
                touch-action: none;
            }
            .attribute-option-drag-handle:hover {
                background: #eef5ff;
                border-color: #9ec5fe;
                color: #1f4f82;
            }
            .attribute-option-drag-handle:active,
            .attribute-option-row.dragging .attribute-option-drag-handle {
                cursor: grabbing;
                background: #dceeff;
                border-color: #3c8dbc;
                color: #1f4f82;
            }
            .attribute-option-order-label {
                font-size: 12px;
                color: #6b7280;
            }
            .attribute-option-row.drag-over {
                outline: 2px dashed #3c8dbc;
                outline-offset: -2px;
            }
            .attribute-option-row.dragging {
                background: #f5fbff;
                opacity: 0.9;
            }
        </style>
    @endpush
@endonce

<div
    class="form-group"
    data-attribute-options-wrapper
    style="{{ $shouldShow ? '' : 'display:none;' }}"
    data-next-index="{{ $nextIndex }}"
    data-empty-colspan="{{ $emptyColspan }}"
>
    <label class="col-md-3 control-label">{{ __('Options') }}</label>
    <div class="col-md-9" style="border-top: 1px solid #f4f4f4; padding-top: 15px; margin-top: 10px;">
        @php
            $optionErrors = collect($errors->get('options.new.*.value'))
                ->merge($errors->get('options.new.*.label'));
        @endphp

        @if($optionErrors->isNotEmpty())
            <div class="alert alert-danger">
                @foreach($optionErrors as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif
        @if($isVersion)
            <p class="help-block">{{ __('Define the selectable values for this version. They are saved with the new version when you click Save.') }}</p>
            <p class="help-block">{{ __('Drag rows to reorder options.') }}</p>
        @else
            <p class="help-block">{{ __('Options are versioned. Create a new version to add, remove, or edit enum values.') }}</p>
        @endif

        <table class="table table-condensed">
            <thead>
            <tr>
                @if($isVersion)
                    <th>{{ __('Order') }}</th>
                @endif
                <th>{{ __('Value') }}</th>
                <th>{{ __('Label') }}</th>
                @if($isVersion)
                    <th>{{ __('Active') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                @else
                    <th>{{ __('Sort') }}</th>
                    <th>{{ __('Status') }}</th>
                @endif
            </tr>
            </thead>
            <tbody data-option-rows>
            @if($isVersion)
                @foreach($pendingOptions as $index => $option)
                    @php
                        $value = $option['value'] ?? '';
                        $label = $option['label'] ?? '';
                        $sort = isset($option['sort_order']) && $option['sort_order'] !== ''
                            ? (int) $option['sort_order']
                            : $loop->index;
                        $active = array_key_exists('active', $option) ? (bool) $option['active'] : true;
                    @endphp
                    <tr class="attribute-option-row" data-pending-row>
                        <td class="attribute-option-drag-cell">
                            <div class="attribute-option-drag-content">
                                <button
                                    type="button"
                                    class="attribute-option-drag-handle"
                                    data-option-drag-handle
                                    title="{{ __('Drag to reorder') }}"
                                    aria-label="{{ __('Drag to reorder') }}"
                                >::</button>
                                <span class="attribute-option-order-label" data-option-order-label>{{ $loop->iteration }}</span>
                            </div>
                            <input type="hidden" name="options[new][{{ $index }}][sort_order]" value="{{ $sort }}" data-option-sort-input>
                        </td>
                        <td>
                            <input type="text" name="options[new][{{ $index }}][value]" value="{{ e($value) }}" class="form-control input-sm" required>
                        </td>
                        <td>
                            <input type="text" name="options[new][{{ $index }}][label]" value="{{ e($label) }}" class="form-control input-sm" required>
                        </td>
                        <td>
                            <label class="checkbox-inline" style="margin:0;">
                                <input type="hidden" name="options[new][{{ $index }}][active]" value="0">
                                <input type="checkbox" name="options[new][{{ $index }}][active]" value="1" {{ $active ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>&times;</button>
                        </td>
                    </tr>
                @endforeach
            @else
                @foreach($existingOptions as $option)
                    <tr data-existing-option>
                        <td>{{ $option->value }}</td>
                        <td>{{ $option->label }}</td>
                        <td>{{ $option->sort_order }}</td>
                        <td>
                            @if($option->active)
                                <span class="label label-success">{{ __('Active') }}</span>
                            @else
                                <span class="label label-default">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endif

            @if(!$hasExisting && !$hasPending)
                <tr data-empty-row>
                    <td colspan="{{ $emptyColspan }}" class="text-muted">{{ __('No options yet.') }}</td>
                </tr>
            @endif
            </tbody>
        </table>

        @if($isVersion)
            <div class="panel panel-default" data-option-entry>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-4 form-group">
                            <label for="new_option_value" class="control-label">{{ __('Value') }}</label>
                            <input type="text" id="new_option_value" class="form-control">
                        </div>
                        <div class="col-sm-4 form-group">
                            <label for="new_option_label" class="control-label">{{ __('Label') }}</label>
                            <input type="text" id="new_option_label" class="form-control">
                        </div>
                        <div class="col-sm-4 form-group">
                            <label class="control-label">{{ __('Active') }}</label>
                            <div>
                                <label class="checkbox-inline" style="margin:0;">
                                    <input type="checkbox" id="new_option_active" checked>
                                    {{ __('Active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-option-add>{{ __('Add to list') }}</button>
                </div>
            </div>
        @endif
    </div>
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                var noOptionsText = @json(__('No options yet.'));
                var dragToReorderText = @json(__('Drag to reorder'));
                var unsavedOptionConfirmText = @json(__('attribute_definitions.unsaved_option_confirm'));

                function escapeHtml(str) {
                    return str.replace(/[&<>'"]/g, function (char) {
                        switch (char) {
                            case '&': return '&amp;';
                            case '<': return '&lt;';
                            case '>': return '&gt;';
                            case '\'': return '&#39;';
                            case '"': return '&quot;';
                            default: return char;
                        }
                    });
                }

                function toggleEnumOptions() {
                    var select = document.getElementById('datatype');
                    var wrappers = document.querySelectorAll('[data-attribute-options-wrapper]');

                    if (!select || wrappers.length === 0) {
                        return;
                    }

                    wrappers.forEach(function (wrapper) {
                        wrapper.style.display = select.value === 'enum' ? '' : 'none';
                    });
                }

                function getOptionRows(wrapper) {
                    var tbody = wrapper.querySelector('[data-option-rows]');
                    if (!tbody) {
                        return [];
                    }

                    return Array.from(tbody.querySelectorAll('[data-pending-row]'));
                }

                function refreshPendingSortOrder(wrapper) {
                    var rows = getOptionRows(wrapper);
                    rows.forEach(function (row, index) {
                        var orderLabel = row.querySelector('[data-option-order-label]');
                        var sortInput = row.querySelector('[data-option-sort-input]');

                        if (orderLabel) {
                            orderLabel.textContent = String(index + 1);
                        }

                        if (sortInput) {
                            sortInput.value = String(index);
                        }
                    });
                }

                function ensurePlaceholder(wrapper) {
                    var tbody = wrapper.querySelector('[data-option-rows]');
                    if (!tbody) {
                        return;
                    }

                    var hasExisting = tbody.querySelector('[data-existing-option]');
                    var hasPending = tbody.querySelector('[data-pending-row]');
                    var placeholder = tbody.querySelector('[data-empty-row]');

                    if (!hasExisting && !hasPending) {
                        if (placeholder) {
                            return;
                        }

                        var row = document.createElement('tr');
                        row.setAttribute('data-empty-row', '');
                        row.innerHTML = '<td colspan="' + escapeHtml(wrapper.dataset.emptyColspan || '4') + '" class="text-muted">' + escapeHtml(noOptionsText) + '</td>';
                        tbody.appendChild(row);
                        return;
                    }

                    if (placeholder) {
                        placeholder.remove();
                    }
                }

                function clearDragMarkers(tbody) {
                    if (!tbody) {
                        return;
                    }

                    tbody.querySelectorAll('[data-pending-row]').forEach(function (row) {
                        row.classList.remove('drag-over');
                        row.classList.remove('dragging');
                    });
                }

                function rowFromPoint(clientX, clientY, tbody) {
                    if (!tbody) {
                        return null;
                    }

                    var element = document.elementFromPoint(clientX, clientY);
                    var row = element ? element.closest('[data-pending-row]') : null;

                    if (!row || row.parentElement !== tbody) {
                        return null;
                    }

                    return row;
                }

                function buildPendingRow(index, value, label, active) {
                    var row = document.createElement('tr');
                    row.className = 'attribute-option-row';
                    row.setAttribute('data-pending-row', '');
                    row.innerHTML =
                        '<td class="attribute-option-drag-cell">' +
                            '<div class="attribute-option-drag-content">' +
                                '<button type="button" class="attribute-option-drag-handle" data-option-drag-handle title="' + escapeHtml(dragToReorderText) + '" aria-label="' + escapeHtml(dragToReorderText) + '">::</button>' +
                                '<span class="attribute-option-order-label" data-option-order-label></span>' +
                            '</div>' +
                            '<input type="hidden" name="options[new][' + index + '][sort_order]" value="0" data-option-sort-input>' +
                        '</td>' +
                        '<td><input type="text" name="options[new][' + index + '][value]" value="' + escapeHtml(value) + '" class="form-control input-sm" required></td>' +
                        '<td><input type="text" name="options[new][' + index + '][label]" value="' + escapeHtml(label) + '" class="form-control input-sm" required></td>' +
                        '<td><label class="checkbox-inline" style="margin:0;">' +
                            '<input type="hidden" name="options[new][' + index + '][active]" value="0">' +
                            '<input type="checkbox" name="options[new][' + index + '][active]" value="1"' + (active ? ' checked' : '') + '>' +
                        '</label></td>' +
                        '<td class="text-right"><button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>&times;</button></td>';

                    return row;
                }

                function hasUnsavedOptionDraft(wrapper) {
                    if (!wrapper || wrapper.style.display === 'none') {
                        return false;
                    }

                    var valueField = wrapper.querySelector('#new_option_value');
                    var labelField = wrapper.querySelector('#new_option_label');

                    if (!valueField || !labelField) {
                        return false;
                    }

                    return valueField.value.trim() !== '' || labelField.value.trim() !== '';
                }

                document.addEventListener('change', function (event) {
                    if (event.target && event.target.id === 'datatype') {
                        toggleEnumOptions();
                    }
                });

                document.addEventListener('click', function (event) {
                    var removeButton = event.target.closest('[data-option-remove]');
                    if (removeButton) {
                        var row = removeButton.closest('[data-pending-row]');
                        var wrapper = removeButton.closest('[data-attribute-options-wrapper]');

                        if (row && wrapper) {
                            row.remove();
                            refreshPendingSortOrder(wrapper);
                            ensurePlaceholder(wrapper);
                        }

                        return;
                    }

                    var addButton = event.target.closest('[data-option-add]');
                    if (!addButton) {
                        return;
                    }

                    var wrapper = addButton.closest('[data-attribute-options-wrapper]');
                    if (!wrapper) {
                        return;
                    }

                    var valueField = wrapper.querySelector('#new_option_value');
                    var labelField = wrapper.querySelector('#new_option_label');
                    var activeField = wrapper.querySelector('#new_option_active');
                    var tbody = wrapper.querySelector('[data-option-rows]');

                    if (!valueField || !labelField || !tbody) {
                        return;
                    }

                    if (!valueField.value.trim()) {
                        valueField.focus();
                        return;
                    }

                    if (!labelField.value.trim()) {
                        labelField.focus();
                        return;
                    }

                    var value = valueField.value.trim();
                    var label = labelField.value.trim();
                    var active = activeField ? activeField.checked : true;

                    var index = parseInt(wrapper.dataset.nextIndex || '0', 10);
                    wrapper.dataset.nextIndex = String(index + 1);

                    var row = buildPendingRow(index, value, label, active);
                    var placeholderRow = tbody.querySelector('[data-empty-row]');
                    if (placeholderRow) {
                        placeholderRow.remove();
                    }

                    tbody.appendChild(row);
                    refreshPendingSortOrder(wrapper);

                    valueField.value = '';
                    labelField.value = '';
                    if (activeField) {
                        activeField.checked = true;
                    }

                    valueField.focus();
                });

                var dragState = null;

                document.addEventListener('pointerdown', function (event) {
                    var handle = event.target.closest('[data-option-drag-handle]');
                    if (!handle) {
                        return;
                    }

                    if (event.button !== 0 && event.pointerType !== 'touch') {
                        return;
                    }

                    var row = handle.closest('[data-pending-row]');
                    var wrapper = handle.closest('[data-attribute-options-wrapper]');
                    var tbody = wrapper ? wrapper.querySelector('[data-option-rows]') : null;

                    if (!row || !wrapper || !tbody) {
                        return;
                    }

                    event.preventDefault();
                    dragState = {
                        pointerId: event.pointerId,
                        row: row,
                        handle: handle,
                        wrapper: wrapper,
                        tbody: tbody,
                        startX: event.clientX,
                        startY: event.clientY,
                        moved: false,
                    };
                    handle.setPointerCapture?.(event.pointerId);
                });

                document.addEventListener('pointermove', function (event) {
                    if (!dragState || event.pointerId !== dragState.pointerId) {
                        return;
                    }

                    if (!dragState.moved) {
                        var movedEnough = Math.abs(event.clientY - dragState.startY) > 4 || Math.abs(event.clientX - dragState.startX) > 4;
                        if (!movedEnough) {
                            return;
                        }
                        dragState.moved = true;
                        dragState.row.classList.add('dragging');
                    }

                    event.preventDefault();
                    clearDragMarkers(dragState.tbody);
                    dragState.row.classList.add('dragging');

                    var targetRow = rowFromPoint(event.clientX, event.clientY, dragState.tbody);
                    if (!targetRow || targetRow === dragState.row) {
                        return;
                    }

                    var rect = targetRow.getBoundingClientRect();
                    var insertAfter = (event.clientY - rect.top) > (rect.height / 2);
                    targetRow.classList.add('drag-over');

                    if (insertAfter) {
                        dragState.tbody.insertBefore(dragState.row, targetRow.nextSibling);
                    } else {
                        dragState.tbody.insertBefore(dragState.row, targetRow);
                    }

                    refreshPendingSortOrder(dragState.wrapper);
                }, { passive: false });

                function finishPointerDrag(event) {
                    if (!dragState || event.pointerId !== dragState.pointerId) {
                        return;
                    }

                    clearDragMarkers(dragState.tbody);
                    refreshPendingSortOrder(dragState.wrapper);
                    dragState.handle.releasePointerCapture?.(dragState.pointerId);
                    dragState = null;
                }

                document.addEventListener('pointerup', finishPointerDrag);
                document.addEventListener('pointercancel', finishPointerDrag);

                document.querySelectorAll('[data-attribute-options-wrapper]').forEach(function (wrapper) {
                    refreshPendingSortOrder(wrapper);
                    ensurePlaceholder(wrapper);
                });

                var form = document.getElementById('create-form');
                if (form) {
                    form.addEventListener('submit', function (event) {
                        var wrappers = document.querySelectorAll('[data-attribute-options-wrapper]');
                        var hasDraft = Array.prototype.some.call(wrappers, function (wrapper) {
                            return hasUnsavedOptionDraft(wrapper);
                        });

                        if (!hasDraft) {
                            return;
                        }

                        if (!window.confirm(unsavedOptionConfirmText)) {
                            event.preventDefault();
                        }
                    });
                }

                toggleEnumOptions();
            })();
        </script>
    @endpush
@endonce
