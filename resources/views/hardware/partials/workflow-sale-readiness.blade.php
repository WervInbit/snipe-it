@php
    use App\Services\WorkflowProgressionService;

    $saleRows = $workflowSaleProgression ?? collect();
    $visibleRows = $saleRows->take(6);
    $hiddenRows = $saleRows->slice(6);
    $renderSaleRow = function (array $row) {
        $state = $row['state'];
        if ($row['configuration_blocked']) {
            return [
                'label' => __('Needs configuration'),
                'class' => 'label-default',
            ];
        }
        if (!$row['can_continue'] && $row['blockers']->isNotEmpty()) {
            return [
                'label' => $state === WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY
                    ? __('Completed; prerequisites pending')
                    : __('Waiting for prerequisites'),
                'class' => 'label-default',
            ];
        }
        $label = match ($state) {
            WorkflowProgressionService::STATE_IN_PROGRESS => __('In progress'),
            WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES => __('Completed with issues'),
            WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY => __('Completed successfully'),
            WorkflowProgressionService::STATE_STALE => __('Changed - new run needed'),
            default => __('Not started'),
        };
        $class = match ($state) {
            WorkflowProgressionService::STATE_COMPLETED_SUCCESSFULLY => 'label-success',
            WorkflowProgressionService::STATE_COMPLETED_WITH_ISSUES => 'label-warning',
            default => 'label-default',
        };

        return compact('label', 'class');
    };
@endphp

@if($saleRows->isNotEmpty())
    <div class="row" data-testid="required-workflow-summary">
        <div class="col-md-3">
            <strong>{{ __('Required workflows') }}</strong>
        </div>
        <div class="col-md-9">
            <ol class="list-unstyled mb-0">
                @foreach($visibleRows as $row)
                    @php
                        $status = $renderSaleRow($row);
                    @endphp
                    <li>
                        <strong>{{ $row['position'] }}. {{ $row['profile']->name }}</strong>
                        <span class="label {{ $status['class'] }}">{{ $status['label'] }}</span>
                        @if(!$row['profile']->blocks_sale_readiness)
                            <small class="text-muted">{{ __('prerequisite') }}</small>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if($hiddenRows->isNotEmpty())
                <div class="collapse" id="required-workflows-more-{{ $asset->id }}">
                    <ol class="list-unstyled mb-0">
                        @foreach($hiddenRows as $row)
                            @php
                                $status = $renderSaleRow($row);
                            @endphp
                            <li>
                                <strong>{{ $row['position'] }}. {{ $row['profile']->name }}</strong>
                                <span class="label {{ $status['class'] }}">{{ $status['label'] }}</span>
                                @if(!$row['profile']->blocks_sale_readiness)
                                    <small class="text-muted">{{ __('prerequisite') }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
                <button type="button"
                        class="btn btn-link btn-xs"
                        data-toggle="collapse"
                        data-target="#required-workflows-more-{{ $asset->id }}">
                    {{ __('Show :count more', ['count' => $hiddenRows->count()]) }}
                </button>
            @endif

            <a href="#tests" data-toggle="tab" class="btn btn-link btn-xs">{{ __('Open Workflows') }}</a>
        </div>
    </div>
@endif
