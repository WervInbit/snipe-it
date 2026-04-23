@php
    use App\Models\AttributeDefinition;

    $isEdit = $definition->exists;
    $existingOptions = $definition->relationLoaded('options') ? $definition->options : $definition->options;
    $pendingOptions = collect(old('options.new', []))
        ->filter(fn ($option) => is_array($option) && ($option['value'] ?? '') !== '' && ($option['label'] ?? '') !== '');
    $shouldShow = old('datatype', $definition->datatype ?? AttributeDefinition::DATATYPE_TEXT) === AttributeDefinition::DATATYPE_ENUM;
    $nextIndex = $pendingOptions->keys()->map(fn ($key) => (int) $key)->max();
    $nextIndex = is_null($nextIndex) ? 0 : $nextIndex + 1;
@endphp

@if($isEdit)
    <div class="form-group" data-attribute-options-wrapper style="{{ $definition->isEnum() ? '' : 'display:none;' }}">
        <label class="col-md-3 control-label">{{ __('Options') }}</label>
        <div class="col-md-9" style="border-top: 1px solid #f4f4f4; padding-top: 15px; margin-top: 10px;">
            <p class="help-block">
                {{ __('Enum options are editable in place. Changing an option value updates current model specs, asset overrides, and component definition contributions that reference it.') }}
            </p>

            @if($definition->isEnum())
                <table class="table table-condensed">
                    <thead>
                    <tr>
                        <th>{{ __('Value') }}</th>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Sort') }}</th>
                        <th>{{ __('Active') }}</th>
                        <th>{{ __('Remove') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($existingOptions as $option)
                        <tr>
                            <td>
                                <input type="text" name="options[existing][{{ $option->id }}][value]" class="form-control input-sm" value="{{ $option->value }}" required>
                            </td>
                            <td>
                                <input type="text" name="options[existing][{{ $option->id }}][label]" class="form-control input-sm" value="{{ $option->label }}" required>
                            </td>
                            <td style="width:90px;">
                                <input type="number" name="options[existing][{{ $option->id }}][sort_order]" class="form-control input-sm" min="0" value="{{ $option->sort_order }}">
                            </td>
                            <td style="width:90px;">
                                <label class="checkbox-inline" style="margin:0;">
                                    <input type="hidden" name="options[existing][{{ $option->id }}][active]" value="0">
                                    <input type="checkbox" name="options[existing][{{ $option->id }}][active]" value="1" {{ $option->active ? 'checked' : '' }}>
                                </label>
                            </td>
                            <td style="width:90px;">
                                <label class="checkbox-inline" style="margin:0;">
                                    <input type="hidden" name="options[existing][{{ $option->id }}][delete]" value="0">
                                    <input type="checkbox" name="options[existing][{{ $option->id }}][delete]" value="1">
                                </label>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">{{ __('No options yet.') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="panel panel-default">
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
                            <div class="col-sm-2 form-group">
                                <label for="new_option_sort" class="control-label">{{ __('Sort') }}</label>
                                <input type="number" id="new_option_sort" class="form-control" min="0">
                            </div>
                            <div class="col-sm-2 form-group">
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
@else
    <div
        class="form-group"
        data-attribute-options-wrapper
        style="{{ $shouldShow ? '' : 'display:none;' }}"
        data-next-index="{{ $nextIndex }}"
    >
        <label class="col-md-3 control-label">{{ __('Options') }}</label>
        <div class="col-md-9" style="border-top: 1px solid #f4f4f4; padding-top: 15px; margin-top: 10px;">
            <p class="help-block">{{ __('Define selectable enum values before saving the attribute.') }}</p>

            <table class="table table-condensed">
                <thead>
                <tr>
                    <th>{{ __('Value') }}</th>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Sort') }}</th>
                    <th>{{ __('Active') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody data-option-rows>
                @forelse($pendingOptions as $index => $option)
                    <tr data-pending-row>
                        <td>
                            <input type="text" name="options[new][{{ $index }}][value]" value="{{ $option['value'] ?? '' }}" class="form-control input-sm" required>
                        </td>
                        <td>
                            <input type="text" name="options[new][{{ $index }}][label]" value="{{ $option['label'] ?? '' }}" class="form-control input-sm" required>
                        </td>
                        <td style="width:90px;">
                            <input type="number" name="options[new][{{ $index }}][sort_order]" value="{{ $option['sort_order'] ?? $loop->index }}" class="form-control input-sm" min="0">
                        </td>
                        <td style="width:90px;">
                            <label class="checkbox-inline" style="margin:0;">
                                <input type="hidden" name="options[new][{{ $index }}][active]" value="0">
                                <input type="checkbox" name="options[new][{{ $index }}][active]" value="1" {{ !array_key_exists('active', $option) || $option['active'] ? 'checked' : '' }}>
                            </label>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>{{ __('Remove') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="text-muted">{{ __('No options yet.') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <div class="panel panel-default">
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
                        <div class="col-sm-2 form-group">
                            <label for="new_option_sort" class="control-label">{{ __('Sort') }}</label>
                            <input type="number" id="new_option_sort" class="form-control" min="0">
                        </div>
                        <div class="col-sm-2 form-group">
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
        </div>
    </div>
@endif

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
                function toggleEnumOptions() {
                    var select = document.getElementById('datatype');
                    var wrapper = document.querySelector('[data-attribute-options-wrapper]');

                    if (!select || !wrapper || select.disabled) {
                        return;
                    }

                    wrapper.style.display = select.value === 'enum' ? '' : 'none';
                }

                function ensurePlaceholder(tbody) {
                    if (!tbody) {
                        return;
                    }

                    var placeholder = tbody.querySelector('[data-empty-row]');
                    var hasRows = tbody.querySelector('[data-pending-row]');

                    if (hasRows && placeholder) {
                        placeholder.remove();
                        return;
                    }

                    if (!hasRows && !placeholder) {
                        var row = document.createElement('tr');
                        row.setAttribute('data-empty-row', '');
                        row.innerHTML = '<td colspan="5" class="text-muted">{{ __('No options yet.') }}</td>';
                        tbody.appendChild(row);
                    }
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
                        var tbody = removeButton.closest('[data-option-rows]');

                        if (row) {
                            row.remove();
                            ensurePlaceholder(tbody);
                        }

                        return;
                    }

                    var addButton = event.target.closest('[data-option-add]');
                    if (!addButton) {
                        return;
                    }

                    var wrapper = document.querySelector('[data-attribute-options-wrapper]');
                    var tbody = wrapper ? wrapper.querySelector('[data-option-rows]') : null;
                    var valueField = document.getElementById('new_option_value');
                    var labelField = document.getElementById('new_option_label');
                    var sortField = document.getElementById('new_option_sort');
                    var activeField = document.getElementById('new_option_active');

                    if (!wrapper || !tbody || !valueField || !labelField) {
                        return;
                    }

                    var value = valueField.value.trim();
                    var label = labelField.value.trim();

                    if (!value) {
                        valueField.focus();
                        return;
                    }

                    if (!label) {
                        labelField.focus();
                        return;
                    }

                    var index = parseInt(wrapper.dataset.nextIndex || '0', 10);
                    wrapper.dataset.nextIndex = String(index + 1);
                    var sortValue = sortField && sortField.value !== '' ? sortField.value : String(index);

                    var row = document.createElement('tr');
                    row.setAttribute('data-pending-row', '');
                    row.innerHTML =
                        '<td><input type="text" name="options[new][' + index + '][value]" value="' + value.replace(/"/g, '&quot;') + '" class="form-control input-sm" required></td>' +
                        '<td><input type="text" name="options[new][' + index + '][label]" value="' + label.replace(/"/g, '&quot;') + '" class="form-control input-sm" required></td>' +
                        '<td style="width:90px;"><input type="number" name="options[new][' + index + '][sort_order]" value="' + sortValue + '" class="form-control input-sm" min="0"></td>' +
                        '<td style="width:90px;"><label class="checkbox-inline" style="margin:0;"><input type="hidden" name="options[new][' + index + '][active]" value="0"><input type="checkbox" name="options[new][' + index + '][active]" value="1"' + (activeField && activeField.checked ? ' checked' : '') + '></label></td>' +
                        '<td class="text-right"><button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>{{ __('Remove') }}</button></td>';

                    var placeholder = tbody.querySelector('[data-empty-row]');
                    if (placeholder) {
                        placeholder.remove();
                    }

                    tbody.appendChild(row);

                    valueField.value = '';
                    labelField.value = '';
                    if (sortField) {
                        sortField.value = '';
                    }
                    if (activeField) {
                        activeField.checked = true;
                    }
                });

                toggleEnumOptions();
            })();
        </script>
    @endpush
@endonce
