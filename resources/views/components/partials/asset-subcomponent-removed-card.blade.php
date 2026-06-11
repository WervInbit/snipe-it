@php
    $definition = $child->componentDefinition;
    $depth = (int) ($depth ?? 1);
    $conditionBadgeClass = $child->conditionBadgeClass();
    $conditionBadgeLabel = $child->conditionBadgeLabel();
@endphp

<article class="asset-component-card asset-component-card--child asset-component-card--removed"
         data-testid="asset-component-removed-child-card"
         data-component-depth="{{ $depth }}">
    <div class="asset-component-card__body">
        <div class="asset-component-card__badges">
            <span class="label label-default" data-component-card-badge="removed-child">{{ __('Removed Child') }}</span>
            @if($conditionBadgeClass && $conditionBadgeLabel)
                <span class="label {{ $conditionBadgeClass }}" data-component-card-badge="condition">{{ $conditionBadgeLabel }}</span>
            @endif
        </div>

        <div class="asset-component-card__name">
            @can('view', $child)
                <a href="{{ route('components.show', $child) }}">{{ $child->display_name }}</a>
            @else
                {{ $child->display_name }}
            @endcan
        </div>
        <div class="asset-component-card__subtitle">
            {{ __('Expected child') }} | {{ $child->component_tag }}
            @if($definition?->category?->name)
                | {{ $definition->category->name }}
            @endif
        </div>

        @if($definition)
            <div class="asset-component-card__note">{{ $definition->name }}</div>
        @endif
        <div class="asset-component-card__note">{{ __('Removed from this parent component') }}</div>
    </div>

    <div class="asset-component-card__actions asset-component-card__actions--single">
        <a href="{{ route('components.show', $child) }}" class="btn btn-default btn-sm btn-block">{{ __('Open') }}</a>
    </div>
</article>
