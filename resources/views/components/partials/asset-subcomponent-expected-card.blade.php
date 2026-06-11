@php
    $definition = $template->childComponentDefinition;
    $depth = (int) ($depth ?? 1);
    $definitionDetailUrl = $definition ? route('settings.component_definitions.edit', $definition) : null;
    $canViewDefinition = $definition && auth()->user()?->can('update', $definition);
    $displayName = $template->expected_name ?: ($definition?->name ?? __('Expected subcomponent'));
    $hasTrackedOrRemoved = $materializedQty > 0 || $removedQty > 0;
@endphp

<article class="asset-component-card asset-component-card--child"
         data-testid="asset-component-expected-child-card"
         data-component-depth="{{ $depth }}">
    <div class="asset-component-card__body">
        <div class="asset-component-card__name">
            {{ $displayName }}
        </div>
        <div class="asset-component-card__subtitle">
            {{ __('Model baseline child') }}@if($expectedQty > 1) | {{ __('Assumed x:count', ['count' => $expectedQty]) }}@endif
            @if($definition?->category?->name)
                | {{ $definition->category->name }}
            @endif
        </div>

        @if($definition)
            <div class="asset-component-card__note">
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
            <div class="asset-component-card__note">{{ __('Freeform') }}</div>
        @endif

        <div class="asset-component-card__baseline-note">
            @if($hasTrackedOrRemoved)
                {{ __('Tracked') }}: {{ $materializedQty }} |
                {{ __('Removed') }}: {{ $removedQty }} |
                {{ __('Remaining') }}: {{ $remainingQty }}
            @else
                {{ __('Part of the model baseline. Track it only when this child component is removed, replaced, or needs its own label.') }}
            @endif
        </div>
    </div>

    <div class="asset-component-card__actions asset-component-card__actions--single">
        @if($parentComponent)
            <a href="{{ route('components.show', $parentComponent) }}" class="btn btn-default btn-sm btn-block">{{ __('Open Parent') }}</a>
        @elseif($definition && $canViewDefinition)
            <a href="{{ $definitionDetailUrl }}" class="btn btn-default btn-sm btn-block">{{ __('Open Definition') }}</a>
        @endif
    </div>
</article>
