@php
    $definition = $template->childComponentDefinition;
    $depth = (int) ($depth ?? 1);
    $nameCellStyle = 'padding-left: '.(20 + ($depth * 18)).'px;';
    $definitionDetailUrl = $definition ? route('settings.component_definitions.edit', $definition) : null;
    $canViewDefinition = $definition && auth()->user()?->can('update', $definition);
@endphp

<tr data-testid="asset-component-expected-child-row" data-component-depth="{{ $depth }}">
    <td>
        <span class="label label-primary">{{ __('Expected Child') }}</span>
        <div class="text-muted small">{{ __('Assumed child') }}</div>
    </td>
    <td style="{{ $nameCellStyle }}">
        {{ $template->expected_name ?: ($definition?->name ?? __('Expected subcomponent')) }}
        @if($definition)
            <div class="text-muted small">
                @if($canViewDefinition)
                    <a href="{{ $definitionDetailUrl }}">{{ $definition->name }}</a>
                @else
                    {{ $definition->name }}
                @endif
                @if($definition->part_code)
                    | {{ $definition->part_code }}
                @endif
            </div>
        @else
            <div class="text-muted small">{{ __('Freeform') }}</div>
        @endif
        <div class="text-muted small">
            {{ __('Expected') }}: {{ $expectedQty }} |
            {{ __('Tracked') }}: {{ $materializedQty }} |
            {{ __('Removed') }}: {{ $removedQty }} |
            {{ __('Remaining') }}: {{ $remainingQty }}
        </div>
    </td>
    <td><span class="text-muted">{{ __('Assumed') }}</span></td>
    <td>{{ trans('general.none') }}</td>
    <td>{{ $definition?->category?->name ?: trans('general.none') }}</td>
    <td>{{ $definition?->manufacturer?->name ?: trans('general.none') }}</td>
    <td class="text-nowrap">
        @if($parentComponent)
            <a href="{{ route('components.show', $parentComponent) }}" class="btn btn-xs btn-default">{{ __('Open Parent') }}</a>
        @elseif($definition && $canViewDefinition)
            <a href="{{ $definitionDetailUrl }}" class="btn btn-xs btn-default">{{ __('Open Definition') }}</a>
        @endif
    </td>
</tr>
