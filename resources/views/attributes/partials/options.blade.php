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

        <form method="POST" action="{{ route('attributes.options.store', $definition) }}" class="form-horizontal">
            @csrf
            <div class="form-group{{ $errors->has('value') ? ' has-error' : '' }}">
                <label for="new_option_value" class="control-label">{{ __('Value') }}</label>
                <input type="text" id="new_option_value" name="value" class="form-control" value="{{ old('value') }}" required>
                {!! $errors->first('value', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="form-group{{ $errors->has('label') ? ' has-error' : '' }}">
                <label for="new_option_label" class="control-label">{{ __('Label') }}</label>
                <input type="text" id="new_option_label" name="label" class="form-control" value="{{ old('label') }}" required>
                {!! $errors->first('label', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="form-group{{ $errors->has('sort_order') ? ' has-error' : '' }}">
                <label for="new_option_sort" class="control-label">{{ __('Sort order') }}</label>
                <input type="number" id="new_option_sort" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                {!! $errors->first('sort_order', '<span class="alert-msg">:message</span>') !!}
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}> {{ __('Active') }}
                </label>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">{{ __('Save Option') }}</button>
        </form>
    </div>
</div>
