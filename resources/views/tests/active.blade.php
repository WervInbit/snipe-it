@extends('layouts/default')

@php
    use App\Models\TestResult;
@endphp

@section('title')
    {{ trans('tests.tests') }}
@endsection

@push('css')
<style>
    .testing-page {
        --testing-bg: #ecf2fb;
        --testing-card: #fdfefe;
        --testing-border: rgba(15, 23, 42, 0.08);
        --testing-shadow: 0 20px 40px rgba(15, 23, 42, 0.08), 0 6px 18px rgba(15, 23, 42, 0.05);
        --testing-radius: 18px;
        --testing-space-sm: 0.75rem;
        --testing-space-md: 1.25rem;
        --testing-space-lg: 1.75rem;
        background: radial-gradient(circle at top, #f8fbff 0%, var(--testing-bg) 70%);
        min-height: calc(100vh - 80px);
        padding-bottom: 7rem;
    }

    .testing-shell {
        max-width: 1280px;
        margin: 0 auto;
        padding: var(--testing-space-lg) clamp(0.75rem, 3vw, 1.75rem) 3rem;
    }

    .testing-header {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid var(--testing-border);
        border-radius: var(--testing-radius);
        box-shadow: var(--testing-shadow);
        padding: var(--testing-space-md);
        position: sticky;
        top: 70px;
        z-index: 1030;
        display: flex;
        flex-direction: column;
        gap: var(--testing-space-md);
    }

    .testing-header__primary {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: var(--testing-space-md);
        align-items: flex-start;
    }

    .testing-asset__model {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .testing-asset__meta {
        font-size: 0.9rem;
        color: #64748b;
    }

    .testing-save-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.85rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(14, 165, 233, 0.07);
        color: #0284c7;
    }

    .testing-save-indicator [data-state] {
        font-size: 1rem;
    }

    .testing-header__secondary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: var(--testing-space-sm);
        align-items: center;
    }

    .testing-header__actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        flex-wrap: wrap;
        grid-column: 1 / -1;
    }

    .testing-progress-chip {
        padding: 0.65rem 0.9rem;
        background: rgba(15, 23, 42, 0.03);
        border-radius: 12px;
        border: 1px solid var(--testing-border);
        font-size: 0.85rem;
    }

    .testing-layout-toggle .form-check-input {
        pointer-events: none;
    }

    .testing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: var(--testing-space-lg);
    }

    .testing-grid.compact-2col {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    }

    .testing-card {
        background: var(--testing-card);
        border-radius: var(--testing-radius);
        border: 1px solid var(--testing-border);
        box-shadow: var(--testing-shadow);
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }

    .testing-card__body {
        padding: var(--testing-space-md);
        display: flex;
        flex-direction: column;
        gap: var(--testing-space-md);
        flex: 1 1 auto;
    }

    .testing-card__head {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .testing-card__title {
        font-weight: 700;
        font-size: 1.25rem;
    }

    .testing-card__attribute {
        font-size: 0.85rem;
    }

    .testing-card__toggle {
        gap: 1rem;
        justify-content: center;
    }

    .testing-card__toggle .btn {
        font-weight: 600;
        padding: 1rem 1.25rem;
        font-size: 1.05rem;
        min-width: clamp(7rem, 40%, 11rem);
        justify-content: center;
        flex: 1 1 45%;
    }

    .testing-card__toggle--center {
        display: flex;
        justify-content: center;
    }

    .testing-card__toggle .btn[aria-pressed="true"] {
        color: #fff;
    }

    .testing-card__toggle .btn[data-action="set-pass"][aria-pressed="true"] {
        background: #16a34a;
        border-color: #15803d;
    }

    .testing-card__toggle .btn[data-action="set-fail"][aria-pressed="true"] {
        background: #dc2626;
        border-color: #b91c1c;
    }

    .testing-card__footer {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        border-top: 1px solid var(--testing-border);
    }

    .testing-card__cta {
        border: none;
        background: transparent;
        padding: 0.85rem;
        font-weight: 600;
        font-size: 0.95rem;
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 0.75rem;
    }

    .testing-card__cta:not(:last-child) {
        border-right: 1px solid var(--testing-border);
    }

    .testing-card__cta--center {
        justify-content: center;
    }

    .testing-card__cta-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #cbd5f5;
        transition: background 0.2s ease, border-color 0.2s ease;
        justify-self: flex-end;
    }

    .testing-card__cta-indicator.is-active {
        background: #2563eb;
        border-color: #2563eb;
    }

    .testing-card__cta-content {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-size: 0.95rem;
    }

    .testing-card__cta-label {
        font-weight: 600;
    }
    .testing-drawer {
        border-top: 1px solid var(--testing-border);
        background: rgba(248, 250, 252, 0.9);
    }

    .testing-drawer__body {
        padding: 1rem;
    }

    .testing-photos-thumb img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid var(--testing-border);
    }

    #photoViewerModal img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
    }

    [data-photo-gallery] {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.75rem;
        overflow-x: auto;
    }

    .testing-floating-bar {
        position: sticky;
        bottom: 1rem;
        margin-top: 2rem;
        display: flex;
        justify-content: center;
        pointer-events: none;
    }

    .testing-floating-bar__inner {
        width: min(960px, 100%);
        background: rgba(15, 23, 42, 0.92);
        border-radius: 999px;
        padding: 0.75rem 1.5rem;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        pointer-events: all;
        color: #f1f5f9;
    }

    .testing-floating-bar__progress {
        flex: 1 1 220px;
    }

    .testing-floating-bar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .testing-floating-bar .progress {
        height: 0.35rem;
        border-radius: 999px;
    }

    @media (max-width: 576px) {
        .testing-shell {
            padding-inline: 1rem;
        }

        .testing-header {
            top: 60px;
        }

        .testing-card__footer {
            grid-template-columns: 1fr;
        }

        .testing-card__cta:not(:last-child) {
            border-right: none;
            border-bottom: 1px solid var(--testing-border);
        }

        .testing-grid {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: var(--testing-space-md);
        }

        .testing-grid.compact-2col {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .testing-floating-bar__inner {
            border-radius: 24px;
        }
    }
</style>
@endpush

@section('content')
<div class="testing-page">
    <div class="testing-shell">
        @if (!$run)
            <div class="card border-0 shadow-sm p-5 text-center mx-auto" style="max-width: 520px;">
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
            <header class="testing-header">
                <div class="testing-header__primary">
                    <div class="testing-asset text-truncate">
                        <div class="testing-asset__model" data-testid="asset-header">
                            {{ optional($asset->model)->name ?? trans('general.unknown') }}
                            @if(optional($asset->modelNumber)->label)
                                <span class="text-muted fw-normal">&mdash; {{ $asset->modelNumber->label }}</span>
                            @endif
                        </div>
                        <div class="testing-asset__meta">
                            {{ $asset->asset_tag }} &middot; {{ trans('tests.last_run_with_user', [
                                'date' => optional($run->created_at)->format('Y-m-d'),
                                'user' => optional($run->user)->name ?? trans('general.unknown')
                            ]) }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span id="saveIndicator" class="testing-save-indicator" aria-live="polite">
                            <i class="fas fa-rotate fa-spin d-none" data-state="saving" aria-hidden="true"></i>
                            <i class="fas fa-check d-none" data-state="clean" aria-hidden="true"></i>
                            <i class="fas fa-triangle-exclamation d-none" data-state="error" aria-hidden="true"></i>
                            <span class="small">{{ trans('general.status') }}</span>
                        </span>
                        <div class="btn-group testing-layout-toggle">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    id="toggleTwoCol">
                                <i class="fas fa-layer-group me-1" aria-hidden="true"></i>{{ trans('tests.two_column_toggle') }}
                                <input type="checkbox" class="form-check-input ms-2" id="twoColChk">
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('test-runs.index', $asset->id) }}">
                                        <i class="fas fa-clock me-2" aria-hidden="true"></i>{{ trans('tests.view_history') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="testing-header__secondary">
                    <div class="testing-progress-chip"
                         data-progress-completed
                         data-template="{{ trans('tests.completed_count', ['completed' => ':completed', 'total' => ':total']) }}">
                        {{ trans('tests.completed_count', ['completed' => $progress['completed'], 'total' => $progress['total']]) }}
                    </div>
                    <div class="testing-progress-chip"
                         data-progress-remaining
                         data-template="{{ trans('tests.remaining_count', ['remaining' => ':remaining']) }}">
                        {{ trans('tests.remaining_count', ['remaining' => $progress['remaining']]) }}
                    </div>
                    <div class="testing-progress-chip"
                         data-progress-failures
                         data-template="{{ trans('tests.failure_count', ['failures' => ':failures']) }}">
                        {{ trans('tests.failure_count', ['failures' => $progress['failures']]) }}
                    </div>
                    <div class="testing-header__actions">
                        @if($canViewAudit ?? false)
                            <a href="{{ route('test-runs.index', $asset->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-history me-1" aria-hidden="true"></i>{{ trans('tests.view_history') }}
                            </a>
                        @endif
                        @if($canStartRun ?? false)
                            <form method="POST" action="{{ route('test-runs.store', $asset->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-redo me-1" aria-hidden="true"></i>{{ trans('tests.start_new_run') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </header>

            <main class="mt-4">
                <div id="testGrid" class="testing-grid" data-mode="one">
                    @forelse ($results as $result)
                        @include('tests.partials.active-card', ['result' => $result, 'canUpdate' => $canUpdate])
                    @empty
                        <div class="alert alert-info mb-0">
                            {{ trans('tests.no_results_in_group') }}
                        </div>
                    @endforelse
                </div>
            </main>

            <div class="testing-floating-bar">
                <div class="testing-floating-bar__inner">
                    <div class="testing-floating-bar__progress">
                        <div class="d-flex justify-content-between align-items-center text-uppercase small fw-semibold mb-2">
                            <span>{{ trans('general.progress') }}</span>
                            <span class="text-muted">{{ trans('general.total') }}: {{ $progress['total'] }}</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success"
                                 role="progressbar"
                                 style="width: {{ $progress['total'] ? ($progress['completed'] / $progress['total']) * 100 : 0 }}%"
                                 aria-valuenow="{{ $progress['completed'] }}"
                                 aria-valuemin="0"
                                 aria-valuemax="{{ $progress['total'] }}"
                                 data-progress-bar></div>
                        </div>
                    </div>
                    <div class="testing-floating-bar__actions">
                        <button type="button"
                                class="btn btn-success btn-sm {{ $progress['remaining'] === 0 && $progress['failures'] === 0 ? '' : 'disabled' }}"
                                id="tests-complete-btn"
                                data-testid="tests-complete-btn"
                                {{ $progress['remaining'] === 0 && $progress['failures'] === 0 ? '' : 'disabled' }}>
                            <i class="fas fa-check me-2" aria-hidden="true"></i>{{ trans('tests.cta_complete_ok') }}
                        </button>
                        <button type="button"
                                class="btn btn-outline-light btn-sm text-white border {{ $progress['failures'] > 0 ? '' : 'disabled' }}"
                                id="tests-repair-btn"
                                data-testid="tests-repair-btn"
                                {{ $progress['failures'] > 0 ? '' : 'disabled' }}>
                            <i class="fas fa-tools me-2" aria-hidden="true"></i>{{ trans('tests.cta_send_repair') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="photoDeleteModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ trans('tests.photo_delete_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('general.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            {{ trans('tests.photo_delete_body') }}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('general.cancel') }}</button>
                            <button type="button" class="btn btn-danger" id="confirmPhotoDeleteBtn">{{ trans('tests.photo_delete_confirm') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content bg-black">
                        <button type="button" class="btn-close btn-close-white position-absolute end-0 m-3" data-bs-dismiss="modal" aria-label="{{ trans('general.close') }}"></button>
                        <div class="modal-body d-flex justify-content-center align-items-center">
                            <img id="viewerImg" src="" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
@section('moar_scripts')
<script>
    window.TestsActiveConfig = {
        assetId: @json($asset->id),
        runId: @json(optional($run)->id),
        endpoints: {
            partialUpdate: '{{ $run ? route('test-results.partial-update', [$asset->id, $run->id, 'result' => 'RESULT_ID']) : '' }}',
        },
        canUpdate: {{ ($canUpdate ?? false) ? 'true' : 'false' }},
        progress: @json($progress),
        actions: {
            completeUrl: '{{ $run ? route('hardware.show', $asset->id) : '' }}',
            repairUrl: '{{ $run ? route('hardware.edit', $asset->id) : '' }}',
        },
        messages: {
            noteSaved: @json(trans('tests.note_saved_at', ['time' => ':time'])),
            photoDrawerEmpty: @json(trans('tests.photo_drawer_empty')),
            removePhoto: @json(trans('tests.remove_photo')),
        },
        layoutKey: 'tests.layout.twoCol',
    };
</script>
@php($testsActiveAsset = public_path('js/dist/tests-active.js'))
@if (file_exists($testsActiveAsset))
    <script src="{{ asset('js/dist/tests-active.js') }}?v={{ filemtime($testsActiveAsset) }}"></script>
@endif
@endsection


