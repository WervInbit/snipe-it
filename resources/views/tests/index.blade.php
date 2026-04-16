@extends('layouts/default')

@section('title')
    {{ trans('tests.tests') }}
@endsection

@push('css')
<style>
    .tests-run-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .test-run-row {
        border: 1px solid #d7dee8;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .test-run-row__header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #edf1f6;
    }

    .test-run-row__summary {
        flex: 1 1 auto;
        min-width: 0;
        border: 0;
        padding: 0;
        background: transparent;
        text-align: left;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 0.75rem;
        align-items: center;
    }

    .test-run-row__summary-main {
        min-width: 0;
    }

    .test-run-row__primary {
        display: block;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.3;
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .test-run-row__stats {
        font-size: 0.85rem;
        color: #374151;
        white-space: nowrap;
    }

    .test-run-row__chevron {
        color: #4b5563;
        transition: transform 0.2s ease;
        font-size: 0.9rem;
    }

    .test-run-row__summary[aria-expanded="true"] .test-run-row__chevron {
        transform: rotate(180deg);
    }

    .test-run-row__actions {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        flex: 0 0 auto;
    }

    .test-run-row__actions form {
        margin: 0;
    }

    .test-run-row__details {
        background: #f8fafc;
    }

    .test-run-row__details .panel-body {
        padding: 0.85rem 1rem;
    }

    .test-photo-strip {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.5rem;
        overflow-x: auto;
        padding-top: 0.25rem;
    }

    .test-result-item {
        padding: 0.45rem 0;
    }

    .test-result-item + .test-result-item {
        border-top: 1px solid #e5e7eb;
    }

    .test-result-meta {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.25rem 0.75rem;
        align-items: start;
    }

    .test-result-label {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
        min-width: 0;
        font-weight: 600;
    }

    .test-result-status {
        white-space: nowrap;
    }

    .test-result-note {
        grid-column: 1 / -1;
        color: #6b7280;
        line-height: 1.4;
    }

    @media (max-width: 480px) {
        .test-result-meta {
            grid-template-columns: 1fr;
            gap: 0.2rem;
        }

        .test-result-status {
            white-space: normal;
        }
    }

    .test-photo-strip img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
    }

    #photoViewerModal img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
    }

    @media (max-width: 767px) {
        .test-run-row__header {
            align-items: center;
            flex-wrap: nowrap;
        }

        .test-run-row__actions {
            flex-wrap: nowrap;
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    @can('tests.execute')
        <form method="POST"
              action="{{ route('test-runs.store', $asset->id) }}"
              class="mb-3"
              data-testid="tests-index-start-run-form">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ trans('tests.start_new_run') }}</button>
        </form>
    @endcan

    <div class="tests-run-list">
        @foreach ($runs as $run)
            @php
                $detailId = 'test-run-details-' . $run->id;
                $passes = $run->results->where('status', 'pass')->count();
                $fails = $run->results->where('status', 'fail')->count();
                $nvts = $run->results->where('status', 'nvt')->count();
            @endphp
            <div class="test-run-row" data-testid="test-run-row">
                <div class="test-run-row__header" data-test-run-header>
                    <button type="button"
                            class="test-run-row__summary"
                            data-testid="test-run-toggle"
                            data-test-run-toggle
                            data-toggle="collapse"
                            data-target="#{{ $detailId }}"
                            aria-expanded="false"
                            aria-controls="{{ $detailId }}">
                        <span class="test-run-row__summary-main">
                            <span class="test-run-row__primary">
                                {{ trans('tests.test_run') }} #{{ $run->id }} &middot; {{ optional($run->created_at)->format('Y-m-d H:i') }} &middot; {{ optional($run->user)->name }}
                            </span>
                        </span>
                        <span class="test-run-row__stats">
                            {{ $passes }} {{ trans('tests.pass') }} &middot;
                            {{ $fails }} {{ trans('tests.fail') }}
                            @if($nvts)
                                &middot; {{ $nvts }} {{ trans('tests.nvt') }}
                            @endif
                        </span>
                        <span class="test-run-row__chevron" aria-hidden="true">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </button>
                    <div class="test-run-row__actions">
                        @can('update', $run)
                            <a href="{{ route('test-results.active', ['asset' => $asset->id, 'run' => $run->id]) }}" class="btn btn-default btn-sm">{{ trans('button.edit') }}</a>
                        @endcan
                        @can('delete', $run)
                            <form method="POST" action="{{ route('test-runs.destroy', [$asset->id, $run->id]) }}" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                            </form>
                        @endcan
                    </div>
                </div>

                <div id="{{ $detailId }}" class="collapse test-run-row__details" data-testid="test-run-details">
                    <div class="panel-body">
                        <ul class="list-unstyled mb-0">
                            @forelse ($run->results as $result)
                                @php
                                    $definition = $result->attributeDefinition;
                                    $label = $definition?->label ?? optional($result->type)->name;
                                    $instructions = trim((string) (optional($result->type)->instructions ?: ($definition?->instructions ?? $definition?->help_text)));
                                    $photoItems = $result->photos->map(function ($photo) {
                                        return ['url' => url($photo->path)];
                                    });

                                    if ($photoItems->isEmpty() && $result->photo_path) {
                                        $photoItems = collect([['url' => url($result->photo_path)]]);
                                    }
                                @endphp
                                <li class="test-result-item">
                                    <div class="test-result-meta">
                                        <div class="test-result-label">
                                            <span>{{ $label }}</span>
                                            @if($instructions !== '')
                                                <i class="fas fa-info-circle" data-tooltip="true" title="{{ $instructions }}"></i>
                                            @endif
                                        </div>
                                        <div class="test-result-status">{{ trans('tests.' . $result->status) }}</div>
                                        @if ($result->note)
                                            <div class="test-result-note">{{ $result->note }}</div>
                                        @endif
                                    </div>
                                    @if ($photoItems->isNotEmpty())
                                        <div class="test-photo-strip">
                                            @foreach ($photoItems as $photo)
                                                <img src="{{ $photo['url'] }}"
                                                     alt="{{ trans('tests.photo_thumbnail_alt') }}"
                                                     data-action="open-photo"
                                                     data-photo-url="{{ $photo['url'] }}">
                                            @endforeach
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li class="text-muted">{{ trans('tests.no_results_in_group') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @can('audits.view')
        @php
            $audits = $runs->flatMap->audits
                ->merge($runs->flatMap->results->flatMap->audits)
                ->sortByDesc('created_at');
        @endphp
        @if($audits->isNotEmpty())
            <button class="btn btn-default mb-2" type="button" data-toggle="collapse" data-target="#test-audit-trail">
                {{ trans('tests.view_audit_trail') }}
            </button>
            <div id="test-audit-trail" class="collapse">
                @include('tests.partials.audit-history', ['audits' => $audits])
            </div>
        @endif
    @endcan
</div>
@endsection

@section('moar_scripts')
<div class="modal fade" id="photoViewerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img id="viewerImg" src="" alt="{{ trans('tests.photo_thumbnail_alt') }}">
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const modalEl = document.getElementById('photoViewerModal');
        const viewerImg = document.getElementById('viewerImg');
        if (!modalEl || !viewerImg) {
            return;
        }

        const getBootstrapNamespace = () => window.bootstrap || null;
        const getJquery = () => window.jQuery || window.$ || null;

        const createModalController = (element) => {
            const bootstrapNs = getBootstrapNamespace();
            if (bootstrapNs?.Modal && typeof bootstrapNs.Modal === 'function') {
                try {
                    return new bootstrapNs.Modal(element);
                } catch (error) {
                    // fallback to jQuery
                }
            }
            const $ = getJquery();
            if ($?.fn?.modal) {
                const $el = $(element);
                return {
                    show: () => $el.modal('show'),
                    hide: () => $el.modal('hide'),
                };
            }
            return null;
        };

        const modal = createModalController(modalEl);

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-action="open-photo"]');
            if (!trigger) {
                return;
            }
            const src = trigger.dataset.photoUrl || trigger.getAttribute('src');
            if (!src) {
                return;
            }
            viewerImg.src = src;
            modal?.show();
        });

        document.addEventListener('click', function (event) {
            const header = event.target.closest('[data-test-run-header]');
            if (!header) {
                return;
            }
            if (event.target.closest('.test-run-row__actions')) {
                return;
            }
            if (event.target.closest('[data-test-run-toggle]')) {
                return;
            }
            const toggle = header.querySelector('[data-test-run-toggle]');
            if (!toggle) {
                return;
            }
            toggle.click();
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            viewerImg.src = '';
        });
    })();
</script>
@endsection
