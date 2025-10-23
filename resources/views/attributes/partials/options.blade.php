@php
    use App\Models\AttributeDefinition;

    $existingOptions = $definition->relationLoaded('options') ? $definition->options : $definition->options;
    $pendingOptions = collect(old('options.new', []))
        ->filter(fn ($option) => is_array($option) && ($option['value'] ?? '') !== '' && ($option['label'] ?? '') !== '');

    $hasExisting = $existingOptions->count() > 0;
    $hasPending = $pendingOptions->count() > 0;

    $maxExistingSort = $existingOptions->max('sort_order') ?? -1;
    $maxPendingSort = $pendingOptions->max(fn ($option) => isset($option['sort_order']) ? (int) $option['sort_order'] : null) ?? -1;
    $nextSort = max($maxExistingSort, $maxPendingSort) + 1;

    $nextIndex = $pendingOptions->keys()->map(fn ($key) => (int) $key)->max();
    $nextIndex = is_null($nextIndex) ? 0 : $nextIndex + 1;

    $initialDatatype = old('datatype', $definition->datatype ?? ($versionSource->datatype ?? AttributeDefinition::DATATYPE_TEXT));
    $shouldShow = $initialDatatype === AttributeDefinition::DATATYPE_ENUM;
@endphp

<div
    class="form-group"
    data-attribute-options-wrapper
    style="{{ $shouldShow ? '' : 'display:none;' }}"
    data-next-index="{{ $nextIndex }}"
    data-next-sort="{{ $nextSort }}"
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
        <p class="help-block">{{ __('Define the selectable values. They are saved with the attribute when you click Save.') }}</p>

        <table class="table table-condensed">
            <thead>
            <tr>
                <th>{{ __('Value') }}</th>
                <th>{{ __('Label') }}</th>
                <th>{{ __('Sort') }}</th>
                <th class="text-right">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody data-option-rows>
            @foreach($existingOptions as $option)
                <tr data-existing-option>
                    <td>{{ $option->value }}</td>
                    <td>
                        {{ $option->label }}
                        @unless($option->active)
                            <span class="label label-default">{{ __('Inactive') }}</span>
                        @endunless
                    </td>
                    <td>{{ $option->sort_order }}</td>
                    <td class="text-right">
                        @if($definition->exists)
                            <form method="POST" action="{{ route('attributes.options.destroy', [$definition, $option]) }}" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('{{ __('Delete this option?') }}');">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach

            @foreach($pendingOptions as $index => $option)
                @php
                    $value = $option['value'] ?? '';
                    $label = $option['label'] ?? '';
                    $sort = $option['sort_order'] ?? $nextSort;
                @endphp
                <tr data-pending-row>
                    <td>
                        {{ $value }}
                        <input type="hidden" name="options[new][{{ $index }}][value]" value="{{ e($value) }}">
                    </td>
                    <td>
                        {{ $label }}
                        <input type="hidden" name="options[new][{{ $index }}][label]" value="{{ e($label) }}">
                    </td>
                    <td>
                        {{ $sort }}
                        <input type="hidden" name="options[new][{{ $index }}][sort_order]" value="{{ e($sort) }}">
                    </td>
                    <td class="text-right">
                        <button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>&times;</button>
                    </td>
                </tr>
            @endforeach

            @if(!$hasExisting && !$hasPending)
                <tr data-empty-row>
                    <td colspan="4" class="text-muted">{{ __('No options yet.') }}</td>
                </tr>
            @endif
            </tbody>
        </table>

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
                        <label for="new_option_sort" class="control-label">{{ __('Sort order') }}</label>
                        <input type="number" id="new_option_sort" class="form-control" min="0" value="{{ $nextSort }}">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-option-add>{{ __('Add to list') }}</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            (function () {
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
                    var wrapper = document.querySelector('[data-attribute-options-wrapper]');

                    if (!select || !wrapper) {
                        return;
                    }

                    wrapper.style.display = select.value === 'enum' ? '' : 'none';
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
                        if (row) {
                            var tbody = row.parentElement;
                            row.remove();

                            if (!tbody.querySelector('[data-existing-option]') && !tbody.querySelector('[data-pending-row]')) {
                                var placeholder = document.createElement('tr');
                                placeholder.setAttribute('data-empty-row', '');
                                placeholder.innerHTML = '<td colspan="4" class="text-muted">{{ __('No options yet.') }}</td>';
                                tbody.appendChild(placeholder);
                            }
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
                    var sortField = wrapper.querySelector('#new_option_sort');
                    var tbody = wrapper.querySelector('[data-option-rows]');

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
                    var sort = sortField.value.trim();

                    if (sort === '') {
                        sort = wrapper.dataset.nextSort || '0';
                    }

                    wrapper.dataset.nextSort = (parseInt(sort, 10) + 1).toString();

                    var index = parseInt(wrapper.dataset.nextIndex || '0', 10);
                    wrapper.dataset.nextIndex = (index + 1).toString();

                    var placeholderRow = tbody.querySelector('[data-empty-row]');
                    if (placeholderRow) {
                        placeholderRow.remove();
                    }

                    var row = document.createElement('tr');
                    row.setAttribute('data-pending-row', '');
                    row.innerHTML =
                        '<td>' + escapeHtml(value) + '<input type="hidden" name="options[new][' + index + '][value]" value="' + escapeHtml(value) + '"></td>' +
                        '<td>' + escapeHtml(label) + '<input type="hidden" name="options[new][' + index + '][label]" value="' + escapeHtml(label) + '"></td>' +
                        '<td>' + escapeHtml(sort) + '<input type="hidden" name="options[new][' + index + '][sort_order]" value="' + escapeHtml(sort) + '"></td>' +
                        '<td class="text-right"><button type="button" class="btn btn-xs btn-link text-danger" data-option-remove>&times;</button></td>';

                    tbody.appendChild(row);

                    valueField.value = '';
                    labelField.value = '';
                    sortField.value = wrapper.dataset.nextSort;
                    valueField.focus();
                });

                toggleEnumOptions();
            })();
        </script>
    @endpush
@endonce
