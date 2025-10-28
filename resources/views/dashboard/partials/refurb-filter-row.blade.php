@php
    $filters = collect($filters ?? [])->filter(fn ($filter) => is_array($filter));
@endphp

@if($filters->isNotEmpty())
    @once
        @push('css')
            <style>
                .dashboard-refurb-filter-row {
                    margin: 10px 0 25px;
                }

                .dashboard-refurb-filter-row .refurb-filter {
                    margin-right: 0.5rem;
                    margin-bottom: 0.5rem;
                    border-width: 2px;
                    font-weight: 600;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                    padding: 0.45rem 0.85rem;
                    border-radius: 999px;
                    transition: all 0.15s ease-in-out;
                    text-transform: none;
                }

                .dashboard-refurb-filter-row .refurb-filter .refurb-filter-icon {
                    font-size: 0.9rem;
                }

                .dashboard-refurb-filter-row .refurb-filter.refurb-filter--disabled {
                    opacity: 0.45;
                    cursor: not-allowed;
                }

                .dashboard-refurb-filter-row .refurb-filter:not(.refurb-filter--disabled):hover,
                .dashboard-refurb-filter-row .refurb-filter:not(.refurb-filter--disabled):focus {
                    text-decoration: none;
                    transform: translateY(-1px);
                }

                @media (max-width: 768px) {
                    .dashboard-refurb-filter-row {
                        display: flex;
                        overflow-x: auto;
                        padding-bottom: 4px;
                        scroll-snap-type: x mandatory;
                    }

                    .dashboard-refurb-filter-row .refurb-filter {
                        scroll-snap-align: start;
                        white-space: nowrap;
                    }
                }
            </style>
        @endpush
    @endonce

    <div class="row">
        <div class="col-md-12">
            <div class="dashboard-refurb-filter-row">
                @foreach($filters as $filter)
                    @php
                        $isEnabled = $filter['available'];
                        $linkUrl = $isEnabled
                            ? route('hardware.index', ['status_id' => $filter['status_id']])
                            : '#';
                        $borderColor = $filter['color'] ?? '#6c757d';
                    @endphp
                    <a
                        @if($isEnabled)
                            href="{{ $linkUrl }}"
                        @else
                            tabindex="-1"
                        @endif
                        class="btn btn-outline-secondary refurb-filter {{ $isEnabled ? '' : 'refurb-filter--disabled' }}"
                        style="border-color: {{ $borderColor }}; color: {{ $borderColor }};"
                        @if(!empty($filter['description']))
                            data-toggle="tooltip"
                            title="{{ $filter['description'] }}"
                        @endif
                        @if(! $isEnabled)
                            aria-disabled="true"
                        @endif
                    >
                        <i class="fa fa-{{ $filter['icon'] }} refurb-filter-icon" aria-hidden="true"></i>
                        <span>{{ $filter['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
