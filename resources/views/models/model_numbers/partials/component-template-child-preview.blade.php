@php
    $componentDefinition = $componentDefinition ?? null;
    $childTemplates = $componentDefinition?->subcomponentTemplates ?? collect();
@endphp

@if($componentDefinition && $childTemplates->isNotEmpty())
    <div class="well well-sm" data-testid="model-number-expected-child-preview" style="margin:10px 0 0;">
        <strong>{{ __('Expected child structure') }}</strong>
        <ul class="list-unstyled" style="margin:6px 0 0;">
            @foreach($childTemplates as $childTemplate)
                @php($childDefinition = $childTemplate->childComponentDefinition)
                <li>
                    <span class="label label-default">{{ __('Child') }}</span>
                    {{ $childTemplate->expected_name ?: ($childDefinition?->name ?? __('Freeform expected subcomponent')) }}
                    <span class="text-muted">x{{ max(1, (int) $childTemplate->expected_qty) }}</span>
                    @if($childDefinition)
                        <span class="text-muted">
                            -
                            @can('update', $childDefinition)
                                <a href="{{ route('settings.component_definitions.edit', $childDefinition) }}">{{ $childDefinition->name }}</a>
                            @else
                                {{ $childDefinition->name }}
                            @endcan
                            @if($childDefinition->part_code)
                                ({{ $childDefinition->part_code }})
                            @endif
                        </span>
                    @else
                        <span class="text-muted">- {{ __('Freeform') }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
