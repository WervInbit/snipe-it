@php
    use App\Models\ComponentInstance;

    $context = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($context ?? 'component'));
    $variant = $variant ?? 'table';
    $conditionOptions = $conditionOptions ?? $componentConditionOptions ?? ComponentInstance::conditionCodeOptions();
    $controlId = 'asset_component_condition_' . $context . '_' . $component->id;
@endphp

@can('update', $component)
    <form method="POST"
          action="{{ route('hardware.components.condition.update', [$asset, $component]) }}"
          @class([
              'asset-component-condition-control',
              'asset-component-condition-control--mobile' => $variant === 'mobile',
              'asset-component-condition-control--table form-inline' => $variant !== 'mobile',
          ])
          @if($variant !== 'mobile') style="margin-top:6px;" @endif>
        @csrf
        <label for="{{ $controlId }}" @class([
            'small',
            'text-muted',
            'asset-component-condition-control__label' => $variant === 'mobile',
        ]) @if($variant !== 'mobile') style="margin-right:4px;" @endif>
            {{ trans('general.condition') }}
        </label>
        <select id="{{ $controlId }}"
                name="condition_code"
                @class([
                    'form-control',
                    'input-sm' => $variant !== 'mobile',
                ])>
            @foreach($conditionOptions as $value => $label)
                <option value="{{ $value }}" @selected($component->condition_code === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" @class([
            'btn',
            'btn-default',
            'btn-sm' => $variant === 'mobile',
            'btn-xs' => $variant !== 'mobile',
        ])>
            {{ trans('general.save') }}
        </button>
    </form>
@else
    <div class="text-muted small">{{ trans('general.condition') }}: {{ $component->displayConditionLabel() }}</div>
@endcan
