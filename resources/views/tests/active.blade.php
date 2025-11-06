@extends('layouts/default')

@section('title')
    {{ trans('tests.tests') }}
@endsection

@push('styles')
<style>
    .tests-active {
        padding-bottom: 110px;
    }
.tests-header {
    position: sticky;
    top: 0;
    z-index: 1010;
        background: var(--bs-body-bg);
        box-shadow: 0 2px 4px rgba(0,0,0,.06);
    }
    .tests-header .refurb-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 12px;
    }
    .test-group + .test-group {
        margin-top: 1.25rem;
    }
    .test-group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        padding: .75rem 1rem;
        border: 0;
        border-radius: .75rem;
        background: rgba(0,0,0,.035);
    }
.test-group-header span {
        font-weight: 600;
    }
    .test-card {
        border: 0;
        border-radius: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.05);
    }
    .test-card + .test-card {
        margin-top: .75rem;
    }
    .test-card .card-body {
        padding: 1rem;
    }
    .test-card__topline {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        min-height: 56px;
    }
    .test-card__label {
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .test-card__meta {
        font-size: .8125rem;
        color: var(--bs-secondary-color);
    }
    .status-toggle {
        position: relative;
        display: flex;
        width: 100%;
        border-radius: 999px;
        background: rgba(15,23,42,.06);
        padding: 2px;
        overflow: hidden;
    }
    .status-toggle button {
        flex: 1 1 0;
        border: 0;
        background: transparent;
        font-weight: 600;
        padding: .45rem .5rem;
        border-radius: 999px;
        position: relative;
        z-index: 2;
        transition: color .2s ease;
        color: rgba(15,23,42,.65);
    }
    .status-toggle button.active {
        color: #fff;
    }
    .status-toggle .status-indicator {
        position: absolute;
        top: 2px;
        bottom: 2px;
        left: 2px;
        width: calc(33.333% - 4px);
        border-radius: 999px;
        background: var(--bs-success);
        transition: transform .25s ease, background .25s ease;
        z-index: 1;
    }
    .status-toggle[data-status="fail"] .status-indicator {
        background: var(--bs-danger);
        transform: translateX(calc(100% + 4px));
    }
    .status-toggle[data-status="nvt"] .status-indicator {
        background: var(--bs-secondary);
        transform: translateX(calc(200% + 8px));
    }
    .test-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .75rem;
    }
    .test-card__actions .chip-button {
        border-radius: 999px;
        padding: .4rem .85rem;
        font-weight: 600;
    }
    .chip-button.active-chip {
        background: var(--bs-primary);
        color: #fff;
        border-color: transparent;
    }
    .test-card__note,
    .test-card__photo {
        position: relative;
        margin-top: .75rem;
        border-radius: .75rem;
        border: 1px solid rgba(15,23,42,.08);
        padding: .75rem;
        background: rgba(248,249,250,.75);
    }
    .test-card__note textarea {
        min-height: 90px;
        background: transparent;
    }
    .test-card__photo img {
        max-width: 100%;
        border-radius: .5rem;
        margin-bottom: .5rem;
        display: block;
    }
    .tests-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1020;
        background: var(--bs-body-bg);
        box-shadow: 0 -2px 8px rgba(15,23,42,.1);
    }
    .tests-action-bar .progress {
        height: .4rem;
        border-radius: .75rem;
    }
    .toast-container {
        z-index: 2000;
    }
    @media (min-width: 768px) {
        .tests-active-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .test-group > div[data-group-body] > .test-card {
            margin-top: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="container tests-active">
    <div id="offline-banner" class="alert alert-warning d-none text-center" role="alert">
        {{ trans('general.offline_banner') }}
    </div>

    @if (!$run)
        <div class="card border-0 shadow-sm p-4 text-center">
            <h2 class="h5 mb-3">{{ trans('tests.no_active_run') }}</h2>
            <p class="text-muted mb-4">{{ trans('tests.start_run_cta') }}</p>
            @if($canStartRun ?? false)
                <form method="POST" action="{{ route('test-runs.store', $asset->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-play me-2" aria-hidden="true"></i>{{ trans('tests.start_new_run') }}
                    </button>
                </form>
            @endif
        </div>
    @else
        <div class="tests-header mb-3 py-3 px-2 px-md-3">
            <div class="d-flex flex-column gap-2 px-1">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                    <h2 class="h5 mb-1" data-testid="asset-header">
                            {{ optional($asset->model)->name }}
                            @if(optional($asset->modelNumber)->label)
                                <span class="text-muted fw-normal"> — {{ $asset->modelNumber->label }}</span>
                            @endif
                        </h2>
                        <div class="small text-muted">
                            {{ trans('tests.last_run_with_user', [
                                'date' => optional($run->created_at)->format('Y-m-d'),
                                'user' => optional($run->user)->name ?? trans('general.unknown')
                            ]) }}
                        </div>
                    </div>
                    @php
                        $statusLabel = optional($asset->status)->name;
                        $statusColor = optional($asset->status)->color;
                    @endphp
                    @if($statusLabel)
                        <span class="refurb-chip" style="{{ $statusColor ? 'background-color:'. $statusColor .';color:#fff;' : '' }}" data-testid="refurb-chip">
                            <i class="fas fa-circle" aria-hidden="true"></i>{{ \App\Support\RefurbStatus::displayName($statusLabel) }}
                        </span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2 small text-secondary">
                    <span>{{ trans('tests.completed_count', ['completed' => $progress['completed'], 'total' => $progress['total']]) }}</span>
                    @if($progress['failures'] > 0)
                        <span class="text-danger">• {{ trans_choice('tests.failures_count', $progress['failures']) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3 gap-2 px-1">
            @if($canStartRun ?? false)
                <form method="POST" action="{{ route('test-runs.store', $asset->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                        <i class="fas fa-redo me-1" aria-hidden="true"></i>{{ trans('tests.start_new_run') }}
                    </button>
                </form>
            @endif
            @if($canViewAudit ?? false)
                <a href="{{ route('test-runs.index', $asset->id) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2" data-testid="test-history-link">
                    <i class="fas fa-history me-1" aria-hidden="true"></i>{{ trans('tests.view_history') }}
                </a>
            @endif
        </div>

        @foreach (['fail' => trans('tests.group_fail'), 'open' => trans('tests.group_open'), 'pass' => trans('tests.group_pass')] as $groupKey => $groupTitle)
            @php
                $groupResults = $groups[$groupKey];
                $isCollapsed = $groupKey === 'pass';
            @endphp
            <section class="test-group" data-group="{{ $groupKey }}">
                <button type="button"
                        class="test-group-header btn btn-link text-start {{ $groupResults->isEmpty() ? 'disabled text-muted' : '' }}"
                        data-group-toggle="{{ $groupKey }}"
                        aria-expanded="{{ $groupKey === 'pass' ? 'false' : 'true' }}"
                        {{ $groupResults->isEmpty() ? 'disabled' : '' }}>
                    <span>{{ $groupTitle }}</span>
                    <span class="badge bg-secondary">{{ $groupResults->count() }}</span>
                </button>
                <div class="mt-2 {{ $isCollapsed ? 'd-none' : '' }} tests-active-grid"
                     data-group-body="{{ $groupKey }}"
                     data-empty-template="{{ trans('tests.no_results_in_group') }}">
                    @forelse ($groupResults as $result)
                        <article class="card test-card"
                                 data-result-id="{{ $result['id'] }}"
                                 data-testid="test-item-{{ $result['slug'] }}"
                                 data-initial-status="{{ $result['status'] }}">
                            <div class="card-body">
                                <div class="test-card__topline">
                                    <div>
                                        <div class="test-card__label">{{ $result['label'] }}</div>
                                        <div class="test-card__meta">
                                            @if($result['expected'])
                                                <span>{{ trans('tests.expected_value', ['value' => $result['expected']]) }}</span>
                                            @endif
                                            @if($result['attribute'])
                                                <span class="ms-2">{{ $result['attribute'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($canUpdate ?? false)
                                        <div class="status-toggle" role="radiogroup" aria-label="{{ trans('tests.status_toggle') }}">
                                            <button type="button" class="status-option text-success" data-status="pass" data-testid="test-item-{{ $result['slug'] }}-pass" role="radio">{{ trans('tests.status_pass') }}</button>
                                            <button type="button" class="status-option text-danger" data-status="fail" data-testid="test-item-{{ $result['slug'] }}-fail" role="radio">{{ trans('tests.status_fail') }}</button>
                                            <button type="button" class="status-option text-secondary" data-status="nvt" data-testid="test-item-{{ $result['slug'] }}-nvt" role="radio">{{ trans('tests.status_nvt') }}</button>
                                            <span class="status-indicator" aria-hidden="true"></span>
                                        </div>
                                    @endif
                                </div>
                                @if($result['instructions'])
                                    <button type="button"
                                            class="instruction-toggle"
                                            data-instructions-toggle="true"
                                            aria-expanded="false"
                                            aria-controls="instructions-{{ $result['id'] }}">
                                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ trans('tests.show_instructions') }}</span>
                                    </button>
                                    <div id="instructions-{{ $result['id'] }}"
                                         class="test-card__instructions small text-muted mt-2 d-none">
                                        {!! nl2br(e($result['instructions'])) !!}
                                    </div>
                                @endif

                                <div class="test-card__actions">
                                    <button type="button"
                                            class="btn btn-outline-secondary chip-button"
                                            data-comment-toggle="true"
                                            data-testid="test-item-{{ $result['slug'] }}-note">
                                        <i class="fas fa-note-sticky me-2" aria-hidden="true"></i>{{ trans('general.note') }}
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-secondary chip-button"
                                            data-photo-trigger="true"
                                            data-testid="test-item-{{ $result['slug'] }}-photo">
                                        <i class="fas fa-camera me-2" aria-hidden="true"></i>{{ trans('general.photo') }}
                                    </button>
                                    <input type="file"
                                           class="d-none"
                                           accept="image/*"
                                           capture="environment"
                                           data-photo-input="true">
                                </div>

                                <div class="test-card__note d-none" data-note-container="true">
                                    <label class="form-label small" for="note-{{ $result['id'] }}">{{ trans('general.note') }}</label>
                                    <textarea id="note-{{ $result['id'] }}"
                                              class="form-control"
                                              data-note-input="true"
                                              maxlength="2000">{{ $result['note'] }}</textarea>
                                </div>

                                <div class="test-card__photo {{ $result['photo'] ? '' : 'd-none' }}" data-photo-container="true">
                                    @if($result['photo'])
                                        <img src="{{ $result['photo'] }}" alt="{{ trans('general.photo') }}">
                                        <button type="button" class="btn btn-link text-danger p-0 small" data-photo-remove="true">
                                            {{ trans('tests.remove_photo') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="text-muted small px-1" data-empty-state="true">{{ trans('tests.no_results_in_group') }}</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    @endif
</div>

@if($run)
    <div class="tests-action-bar py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div class="flex-grow-1">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small text-muted"
                  data-progress-completed
                  data-template="{{ trans('tests.completed_count', ['completed' => ':completed', 'total' => ':total']) }}">
                {{ trans('tests.completed_count', ['completed' => $progress['completed'], 'total' => $progress['total']]) }}
            </span>
            <span class="small text-muted"
                  data-progress-remaining
                  data-template="{{ trans('tests.remaining_count', ['remaining' => ':remaining']) }}">
                {{ trans('tests.remaining_count', ['remaining' => $progress['remaining']]) }}
            </span>
        </div>
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: {{ $progress['total'] ? round(($progress['completed'] / max(1, $progress['total'])) * 100) : 0 }}%;" aria-valuenow="{{ $progress['completed'] }}" aria-valuemin="0" aria-valuemax="{{ $progress['total'] }}"></div>
        </div>
        <div class="small mt-2 text-danger fw-semibold {{ $failingLabels->isEmpty() ? 'd-none' : '' }}"
             data-progress-failures
             data-template="{{ trans('tests.failing_summary', ['list' => ':list']) }}">
            @if(!$failingLabels->isEmpty())
                {{ trans('tests.failing_summary', ['list' => $failingLabels->implode(', ')]) }}
            @endif
        </div>
    </div>
    <div class="d-flex flex-column flex-md-row gap-2">
        <button type="button"
                class="btn btn-success"
                id="tests-complete-btn"
                        data-testid="tests-complete-btn"
                        {{ $progress['remaining'] === 0 && $progress['failures'] === 0 ? '' : 'disabled' }}>
                    <i class="fas fa-check me-2" aria-hidden="true"></i>{{ trans('tests.cta_complete_ok') }}
                </button>
                <button type="button"
                        class="btn btn-outline-danger {{ $progress['failures'] > 0 ? '' : 'disabled' }}"
                        id="tests-repair-btn"
                        data-testid="tests-repair-btn"
                        {{ $progress['failures'] > 0 ? '' : 'disabled' }}>
                    <i class="fas fa-tools me-2" aria-hidden="true"></i>{{ trans('tests.cta_send_repair') }}
                </button>
            </div>
        </div>
    </div>
@endif

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="tests-toast-container"></div>
@stop

@section('moar_scripts')
<script>
    window.TestsActiveConfig = {
        assetId: {{ $asset->id }},
        runId: {{ $run->id ?? 'null' }},
        endpoints: {
            partialUpdate: '{{ $run ? route('test-results.partial-update', [$asset->id, $run->id, 'result' => 'RESULT_ID']) : null }}',
        },
        messages: {
            saved: @json(trans('general.saved')),
            queued: @json(trans('tests.update_queued')),
            retry: @json(trans('general.retry')),
            photoReplaced: @json(trans('tests.photo_replaced')),
            photoOffline: @json(trans('tests.photo_offline_error')),
        },
        canUpdate: {{ ($canUpdate ?? false) ? 'true' : 'false' }},
        progress: @json($progress),
        actions: {
            completeUrl: '{{ $run ? route('hardware.edit', $asset->id) : '' }}',
            repairUrl: '{{ $run ? route('hardware.edit', $asset->id) : '' }}',
        },
    };
</script>
@php($testsActiveAsset = public_path('js/dist/tests-active.js'))
@if (file_exists($testsActiveAsset))
    <script src="{{ asset('js/dist/tests-active.js') }}?v={{ filemtime($testsActiveAsset) }}"></script>
@endif
@endsection
