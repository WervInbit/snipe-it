@extends('layouts/default')

@section('title')
    {{ trans('tests.tests') }}
@endsection

@push('css')
<style>
    .test-photo-strip {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.5rem;
        overflow-x: auto;
        padding-top: 0.25rem;
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
</style>
@endpush

@section('content')
<div class="container">
    @can('tests.execute')
        <form method="POST" action="{{ route('test-runs.store', $asset->id) }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ trans('tests.start_new_run') }}</button>
        </form>
    @endcan

    @foreach ($runs as $run)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-xs-7">
                        <div>{{ optional($run->created_at)->format('Y-m-d H:i') }}</div>
                        <div class="text-muted">{{ optional($run->user)->name }}</div>
                    </div>
                    <div class="col-xs-5 text-right">
                        @can('update', $run)
                            <a href="{{ route('test-results.edit', [$asset->id, $run->id]) }}" class="btn btn-default btn-sm mb-1">{{ trans('button.edit') }}</a>
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
                <ul class="list-unstyled mt-2 mb-0">
                    @foreach ($run->results as $result)
                        @php
                            $definition = $result->attributeDefinition;
                            $label = $definition?->label ?? optional($result->type)->name;
                            $instructions = trim((string) (optional($result->type)->instructions ?: ($definition?->instructions ?? $definition?->help_text)));
                        @endphp
                        <li>
                            {{ $label }}
                            @if($instructions !== '')
                                <i class="fas fa-info-circle" data-tooltip="true" title="{{ $instructions }}"></i>
                            @endif:
                            {{ trans('tests.' . $result->status) }}
                            @if ($result->note)
                                <span class="text-muted">{{ $result->note }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @php
                    $photoItems = $run->results->flatMap(function ($result) {
                        $items = $result->photos->map(function ($photo) {
                            return ['url' => url($photo->path)];
                        });

                        if ($items->isEmpty() && $result->photo_path) {
                            $items = collect([['url' => url($result->photo_path)]]);
                        }

                        return $items;
                    });
                @endphp
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
            </div>
        </div>
    @endforeach

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

        modalEl.addEventListener('hidden.bs.modal', function () {
            viewerImg.src = '';
        });
    })();
</script>
@endsection
