@php
    $component = $row->component;
    $template = $row->template;
    $definition = $component?->componentDefinition ?? $template?->componentDefinition;
    $depth = (int) ($depth ?? 0);
    $rowDisplayName = $row->displayName();
    $componentDetailUrl = $component ? route('components.show', $component) : null;
    $canViewComponent = $component && auth()->user()?->can('view', $component);
    $definitionDetailUrl = $definition ? route('settings.component_definitions.edit', $definition) : null;
    $canViewDefinition = $definition && auth()->user()?->can('update', $definition);
    $conditionOptions = $componentConditionOptions ?? \App\Models\ComponentInstance::conditionCodeOptions();
    $conditionBadgeClass = $component?->conditionBadgeClass();
    $conditionBadgeLabel = $component?->conditionBadgeLabel();
    $tagLabel = $component
        ? $component->component_tag
        : ($row->quantity > 1 ? __('Assumed x:count', ['count' => $row->quantity]) : __('Assumed'));
    $subtitleParts = [];

    if ($depth > 0) {
        $subtitleParts[] = __('Child component');
    } elseif ($row->isExpected()) {
        $subtitleParts[] = __('Model baseline');
    } elseif ($row->isRemoved()) {
        $subtitleParts[] = __('Removed baseline');
    } elseif ($row->isExtra()) {
        $subtitleParts[] = __('Extra component');
    } elseif ($row->isCustom()) {
        $subtitleParts[] = __('Custom component');
    }

    $subtitleParts[] = $tagLabel;

    if ($definition?->category?->name) {
        $subtitleParts[] = $definition->category->name;
    }
@endphp

<article @class([
        'asset-component-card',
        'asset-component-card--child' => $depth > 0,
        'asset-component-card--removed' => $row->isRemoved(),
    ])
    data-testid="asset-component-card"
    data-component-classification="{{ $row->classification }}"
    data-component-depth="{{ $depth }}">
    <div class="asset-component-card__body">
        @if($row->isExtra() || $row->isCustom() || $row->isRemoved() || $row->tracked || ($conditionBadgeClass && $conditionBadgeLabel))
            <div class="asset-component-card__badges">
                @if($row->isExtra())
                    <span class="label label-warning" data-component-card-badge="extra">{{ $row->label }}</span>
                @elseif($row->isCustom())
                    <span class="label label-default" data-component-card-badge="custom">{{ $row->label }}</span>
                @elseif($row->isRemoved())
                    <span class="label label-default" data-component-card-badge="removed">{{ $row->label }}</span>
                @endif
                @if($row->tracked && !$row->isRemoved())
                    <span class="label label-success" data-component-card-badge="tracked">{{ __('Tracked') }}</span>
                @endif
                @if($conditionBadgeClass && $conditionBadgeLabel)
                    <span class="label {{ $conditionBadgeClass }}" data-component-card-badge="condition">{{ $conditionBadgeLabel }}</span>
                @endif
            </div>
        @endif

        <div class="asset-component-card__name">
            @if($component && $canViewComponent)
                <a href="{{ $componentDetailUrl }}">{{ $rowDisplayName }}</a>
            @elseif(!$component && $definition && $canViewDefinition)
                <a href="{{ $definitionDetailUrl }}">{{ $rowDisplayName }}</a>
            @else
                {{ $rowDisplayName }}
            @endif
        </div>

        <div class="asset-component-card__subtitle">
            {{ implode(' | ', array_filter($subtitleParts)) }}
        </div>

        <dl class="asset-component-card__meta">
            <div>
                <dt>{{ trans('general.tag') }}</dt>
                <dd>
                    @if($component && $canViewComponent)
                        <a href="{{ $componentDetailUrl }}">{{ $component->component_tag }}</a>
                    @else
                        {{ $tagLabel }}
                    @endif
                </dd>
            </div>
            <div>
                <dt>{{ trans('admin/hardware/form.serial') }}</dt>
                <dd>{{ $component?->serial ?: trans('general.none') }}</dd>
            </div>
            <div>
                <dt>{{ trans('general.category') }}</dt>
                <dd>{{ $definition?->category?->name ?: trans('general.none') }}</dd>
            </div>
            <div>
                <dt>{{ trans('general.manufacturer') }}</dt>
                <dd>{{ $definition?->manufacturer?->name ?: trans('general.none') }}</dd>
            </div>
        </dl>

        @if($row->isRemoved())
            <div class="asset-component-card__note">{{ __('Removed from this asset') }}</div>
        @endif

        @if($component && !$row->isRemoved())
            @include('components.partials.asset-component-condition-control', [
                'component' => $component,
                'asset' => $asset,
                'conditionOptions' => $conditionOptions,
                'context' => 'mobile',
                'variant' => 'mobile',
            ])
        @endif
    </div>

    @include('components.partials.asset-component-actions', [
        'row' => $row,
        'asset' => $asset,
        'component' => $component,
        'template' => $template,
        'rowDisplayName' => $rowDisplayName,
        'variant' => 'mobile',
    ])
</article>
