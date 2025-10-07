<div class="form-group">
    <label class="col-md-3 control-label">{{ __('Options') }}</label>
    <div class="col-md-9">
        <p class="help-block">{{ __('Manage the allowed values for this enum attribute.') }}</p>
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
            <tbody>
            @forelse($definition->options as $option)
                <tr>
                    <td>{{ $option->value }}</td>
                    <td>{{ $option->label }}</td>
                    <td>{{ $option->sort_order }}</td>
                    <td>{!! $option->active ? '<i class="fas fa-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('attributes.options.destroy', [$definition, $option]) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('{{ __('Delete this option?') }}');">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-muted">{{ __('No options yet.') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <hr>

        @php($optionFormDomId = 'attribute-option-form-' . $definition->id)
        <div id="{{ $optionFormDomId }}" data-action="{{ route('attributes.options.store', $definition) }}">
            <div class="form-group{{ $errors->has('value') ? ' has-error' : '' }}">
                <label for="new_option_value" class="control-label">{{ __('Value') }}</label>
                <input type="text" id="new_option_value" class="form-control" value="{{ old('value') }}" required>
                {!! $errors->first('value', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
                <label for="new_option_label" class="control-label">{{ __('Label') }}</label>
                <input type="text" id="new_option_label" class="form-control" value="{{ old('label') }}" required>
                {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="form-group{{ $errors->has('sort_order') ? ' has-error' : '' }}">
                <label for="new_option_sort" class="control-label">{{ __('Sort order') }}</label>
                <input type="number" id="new_option_sort" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                {!! $errors->first('sort_order', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="new_option_active" value="1" {{ old('active', true) ? 'checked' : '' }}> {{ __('Active') }}
                </label>
            </div>
            <button type="button" class="btn btn-sm btn-primary" data-option-submit>{{ __('Save Option') }}</button>
        </div>
    </div>
</div>

@push('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            var container = document.getElementById('{{ $optionFormDomId }}');
            if (!container) {
                return;
            }

            var submit = container.querySelector('[data-option-submit]');
            var valueField = container.querySelector('#new_option_value');
            var labelField = container.querySelector('#new_option_label');
            var sortField = container.querySelector('#new_option_sort');
            var activeField = container.querySelector('#new_option_active');

            submit.addEventListener('click', function () {
                if (!valueField.value.trim()) {
                    valueField.focus();
                    return;
                }

                if (!labelField.value.trim()) {
                    labelField.focus();
                    return;
                }

                var action = container.dataset.action;
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = action;

                var addField = function (name, value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                };

                addField('_token', '{{ csrf_token() }}');
                addField('value', valueField.value.trim());
                addField('label', labelField.value.trim());
                addField('sort_order', sortField.value);

                if (activeField.checked) {
                    addField('active', '1');
                }

                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
@endpush
