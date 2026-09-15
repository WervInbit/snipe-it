@extends('layouts/default')

@section('title')
    {{ __('Workflow Profiles') }}
@parent
@stop

@push('css')
<style nonce="{{ csrf_token() }}">
    .workflow-profile-flag {
        white-space: nowrap;
    }

    .workflow-profile-row.dragging {
        opacity: 0.6;
        background: #f8fafc;
    }

    .workflow-profile-drag-handle {
        border: 0;
        background: transparent;
        color: #6b7280;
        cursor: grab;
        padding: 6px 8px;
        touch-action: none;
    }

    .workflow-profile-drag-handle:active {
        cursor: grabbing;
    }

    .workflow-profile-position {
        display: inline-block;
        min-width: 2.5em;
    }

    body.workflow-profile-reordering {
        cursor: grabbing;
        user-select: none;
    }
</style>
@endpush

@section('header_right')
    <a href="{{ route('settings.testtypes.index') }}" class="btn btn-default">
        <x-icon type="tasks" /> {{ __('Workflow Items') }}
    </a>
    @can('create', AppModelsTestType::class)
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#create-workflow-profile-modal">
            <x-icon type="plus" /> {{ __('Create Profile') }}
        </button>
    @endcan
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Workflow Profiles') }}</h3>
                    @can('update', AppModelsTestType::class)
                        <div class="text-muted">{{ __('Drag profiles, or focus a handle and use the arrow keys. Changes are saved immediately.') }}</div>
                    @endcan
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width:48px;">{{ __('Move') }}</th>
                                <th>{{ __('Order') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Categories') }}</th>
                                <th>{{ __('Items') }}</th>
                                <th>{{ __('Dependencies') }}</th>
                                <th>{{ __('Repeats') }}</th>
                                <th>{{ __('Flags') }}</th>
                                <th class="text-right">{{ trans('button.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody data-workflow-profile-reorder-body
                               data-reorder-url="{{ route('settings.workflow-profiles.reorder') }}"
                               data-reorder-failed="{{ __('Failed to reorder workflow profiles.') }}">
                            @forelse($profiles as $profile)
                                <tr class="workflow-profile-row"
                                    data-workflow-profile-id="{{ $profile->id }}"
                                    data-testid="workflow-profile-row">
                                    <td>
                                        @can('update', AppModelsTestType::class)
                                            <button type="button"
                                                    class="workflow-profile-drag-handle"
                                                    data-workflow-profile-drag-handle
                                                    title="{{ trans('admin/testtypes/general.drag_to_reorder') }}"
                                                    aria-label="{{ trans('admin/testtypes/general.drag_to_reorder') }}">
                                                <i class="fas fa-grip-vertical" aria-hidden="true"></i>
                                            </button>
                                        @endcan
                                    </td>
                                    <td><span class="workflow-profile-position" data-workflow-profile-position>{{ $loop->iteration }}</span></td>
                                    <td>
                                        <strong>{{ $profile->name }}</strong>
                                        @if($profile->description)
                                            <div class="text-muted">{{ \Illuminate\Support\Str::limit($profile->description, 90) }}</div>
                                        @endif
                                    </td>
                                    <td class="monospace text-muted">{{ $profile->slug }}</td>
                                    <td>{{ $profile->categories->pluck('name')->implode(', ') ?: trans('general.all') }}</td>
                                    <td>{{ $profile->items_count }}</td>
                                    <td>
                                        @forelse($profile->prerequisites as $dependency)
                                            <div>
                                                {{ $dependency->name }}
                                                <span class="label label-success">
                                                    {{ __('required items pass / done') }}
                                                </span>
                                            </div>
                                        @empty
                                            <span class="text-muted">{{ __('None') }}</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @switch($profile->repeat_policy)
                                            @case(\App\Models\WorkflowProfile::REPEAT_NEVER)
                                                {{ __('Never') }}
                                                @break
                                            @default
                                                {{ __('Override required') }}
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($profile->is_active)
                                            <span class="label label-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('Inactive') }}</span>
                                        @endif
                                        @if($profile->is_default)
                                            <span class="label label-primary">{{ __('Default') }}</span>
                                        @endif
                                        @if($profile->blocks_sale_readiness)
                                            <span class="label label-warning">{{ __('Required before sale') }}</span>
                                        @endif
                                        @if($profile->execution_level !== \App\Models\WorkflowProfile::EXECUTION_OPERATOR)
                                            <span class="label label-info">{{ ucfirst($profile->execution_level) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="{{ trans('button.actions') }}">
                                            @can('update', AppModelsTestType::class)
                                                <a href="{{ route('settings.workflow-profiles.items.edit', $profile) }}" class="btn btn-default">
                                                    {{ __('Items') }}
                                                </a>
                                                <button type="button"
                                                        class="btn btn-default"
                                                        data-toggle="modal"
                                                        data-target="#edit-workflow-profile-{{ $profile->id }}-modal">
                                                    {{ trans('button.edit') }}
                                                </button>
                                            @endcan
                                            @can('delete', AppModelsTestType::class)
                                                <form method="POST"
                                                      action="{{ route('settings.workflow-profiles.destroy', $profile) }}"
                                                      style="display:inline-block"
                                                      onsubmit="return confirm('{{ __('Delete this workflow profile? Historical runs keep their saved profile snapshot.') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">
                                                        {{ trans('button.delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('create', AppModelsTestType::class)
    <div class="modal fade" id="create-workflow-profile-modal" tabindex="-1" role="dialog" aria-labelledby="create-workflow-profile-label">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('settings.workflow-profiles.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="create-workflow-profile-label">{{ __('Create Workflow Profile') }}</h4>
                    </div>
                    <div class="modal-body">
                        @include('settings.partials.workflow-profile-form', ['profile' => null, 'categories' => $categories, 'dependencyProfiles' => $dependencyProfiles])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ trans('button.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @can('update', AppModelsTestType::class)
    @foreach($profiles as $profile)
        <div class="modal fade" id="edit-workflow-profile-{{ $profile->id }}-modal" tabindex="-1" role="dialog" aria-labelledby="edit-workflow-profile-{{ $profile->id }}-label">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('settings.workflow-profiles.update', $profile) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="edit-workflow-profile-{{ $profile->id }}-label">{{ __('Edit :name', ['name' => $profile->name]) }}</h4>
                        </div>
                        <div class="modal-body">
                            @include('settings.partials.workflow-profile-form', ['profile' => $profile, 'categories' => $categories, 'dependencyProfiles' => $dependencyProfiles])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
    @endcan
@endsection

@can('update', AppModelsTestType::class)
@push('js')
<script nonce="{{ csrf_token() }}">
    document.addEventListener('DOMContentLoaded', function () {
        $('.modal').on('shown.bs.modal', function () {
            var modal = $(this);
            modal.find('[data-workflow-dependency-select]').each(function () {
                var select = $(this);
                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }
                select.select2({
                    dropdownParent: modal,
                    placeholder: select.data('placeholder'),
                    width: '100%'
                });
            });
        });

        var reorderBody = document.querySelector('[data-workflow-profile-reorder-body]');
        if (!reorderBody) {
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        var supportsPointerEvents = typeof window.PointerEvent !== 'undefined';
        var draggingRow = null;
        var activePointerId = null;
        var activeHandle = null;
        var originalOrder = [];

        function rows() {
            return Array.from(reorderBody.querySelectorAll('tr[data-workflow-profile-id]'));
        }

        function readOrder() {
            return rows().map(function (row) {
                return Number(row.dataset.workflowProfileId);
            });
        }

        function refreshPositions() {
            rows().forEach(function (row, index) {
                var position = row.querySelector('[data-workflow-profile-position]');
                if (position) {
                    position.textContent = String(index + 1);
                }
            });
        }

        function applyOrder(order) {
            var rowMap = new Map(rows().map(function (row) {
                return [Number(row.dataset.workflowProfileId), row];
            }));

            order.forEach(function (id) {
                var row = rowMap.get(Number(id));
                if (row) {
                    reorderBody.appendChild(row);
                }
            });

            refreshPositions();
        }

        function sendOrder(order) {
            var tokenValue = csrfToken ? csrfToken.getAttribute('content') : '';
            if (typeof window.fetch === 'function') {
                return fetch(reorderBody.dataset.reorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': tokenValue
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ order: order })
                });
            }

            return new Promise(function (resolve, reject) {
                window.jQuery.ajax({
                    url: reorderBody.dataset.reorderUrl,
                    method: 'PATCH',
                    dataType: 'json',
                    data: {
                        order: order,
                        _token: tokenValue
                    }
                }).done(function () {
                    resolve({ ok: true });
                }).fail(function () {
                    reject();
                });
            });
        }

        function moveRowToPoint(clientX, clientY) {
            if (!draggingRow) {
                return;
            }

            var target = document.elementFromPoint(clientX, clientY);
            var targetRow = target ? target.closest('tr[data-workflow-profile-id]') : null;
            if (!targetRow || targetRow === draggingRow || targetRow.parentElement !== reorderBody) {
                return;
            }

            var rect = targetRow.getBoundingClientRect();
            var insertAfter = clientY > (rect.top + (rect.height / 2));

            if (insertAfter) {
                if (targetRow.nextSibling !== draggingRow) {
                    targetRow.parentNode.insertBefore(draggingRow, targetRow.nextSibling);
                }
            } else if (targetRow.previousSibling !== draggingRow) {
                targetRow.parentNode.insertBefore(draggingRow, targetRow);
            }

            refreshPositions();
        }

        function beginDrag(row, handle, pointerId) {
            draggingRow = row;
            activeHandle = handle;
            activePointerId = typeof pointerId === 'number' ? pointerId : null;
            originalOrder = readOrder();
            draggingRow.classList.add('dragging');
            document.body.classList.add('workflow-profile-reordering');
        }

        function finishDrag() {
            if (!draggingRow) {
                return;
            }

            draggingRow.classList.remove('dragging');
            draggingRow = null;
            document.body.classList.remove('workflow-profile-reordering');

            if (activeHandle && activePointerId !== null && typeof activeHandle.releasePointerCapture === 'function') {
                try {
                    activeHandle.releasePointerCapture(activePointerId);
                } catch (e) {
                    // Ignore release errors from browsers that already released capture.
                }
            }

            activeHandle = null;
            activePointerId = null;

            var newOrder = readOrder();
            if (JSON.stringify(newOrder) === JSON.stringify(originalOrder)) {
                return;
            }

            sendOrder(newOrder).then(function (response) {
                if (!response.ok) {
                    applyOrder(originalOrder);
                    window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profiles.');
                }
            }).catch(function () {
                applyOrder(originalOrder);
                window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profiles.');
            });
        }

        function findHandleFromEvent(event) {
            return event.target && event.target.closest
                ? event.target.closest('[data-workflow-profile-drag-handle]')
                : null;
        }

        function persistOrRestore(previousOrder) {
            sendOrder(readOrder()).then(function (response) {
                if (!response.ok) {
                    applyOrder(previousOrder);
                    window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profiles.');
                }
            }).catch(function () {
                applyOrder(previousOrder);
                window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profiles.');
            });
        }

        function startFromHandle(handle, pointerId) {
            var row = handle.closest('tr[data-workflow-profile-id]');
            if (row) {
                beginDrag(row, handle, pointerId);
            }
        }

        function isPrimaryPointerDown(event) {
            return event.pointerType !== 'mouse' || event.button === 0 || event.buttons === 1;
        }

        reorderBody.addEventListener('keydown', function (event) {
            var handle = findHandleFromEvent(event);
            if (!handle || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) {
                return;
            }

            var row = handle.closest('tr[data-workflow-profile-id]');
            if (!row) {
                return;
            }
            var sibling = event.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling || !sibling.matches('tr[data-workflow-profile-id]')) {
                return;
            }

            event.preventDefault();
            var previousOrder = readOrder();
            if (event.key === 'ArrowUp') {
                reorderBody.insertBefore(row, sibling);
            } else {
                reorderBody.insertBefore(sibling, row);
            }
            refreshPositions();
            handle.focus();
            persistOrRestore(previousOrder);
        });

        if (supportsPointerEvents) {
            reorderBody.addEventListener('pointerdown', function (event) {
                var handle = findHandleFromEvent(event);
                if (!handle || !isPrimaryPointerDown(event)) {
                    return;
                }

                event.preventDefault();
                startFromHandle(handle, event.pointerId);
                if (event.pointerId !== undefined && typeof handle.setPointerCapture === 'function') {
                    try {
                        handle.setPointerCapture(event.pointerId);
                    } catch (e) {
                        // Continue without pointer capture when unsupported by the element.
                    }
                }
            });

            document.addEventListener('pointermove', function (event) {
                if (!draggingRow || (activePointerId !== null && event.pointerId !== activePointerId)) {
                    return;
                }
                event.preventDefault();
                moveRowToPoint(event.clientX, event.clientY);
            }, { passive: false });

            document.addEventListener('pointerup', function (event) {
                if (draggingRow && (activePointerId === null || event.pointerId === activePointerId)) {
                    finishDrag();
                }
            });

            document.addEventListener('pointercancel', function (event) {
                if (draggingRow && (activePointerId === null || event.pointerId === activePointerId)) {
                    finishDrag();
                }
            });
        } else {
            reorderBody.addEventListener('mousedown', function (event) {
                var handle = findHandleFromEvent(event);
                if (handle && event.button === 0) {
                    event.preventDefault();
                    startFromHandle(handle, null);
                }
            });

            document.addEventListener('mousemove', function (event) {
                if (draggingRow) {
                    event.preventDefault();
                    moveRowToPoint(event.clientX, event.clientY);
                }
            });

            document.addEventListener('mouseup', finishDrag);

            reorderBody.addEventListener('touchstart', function (event) {
                var handle = findHandleFromEvent(event);
                if (handle && event.touches && event.touches.length) {
                    event.preventDefault();
                    startFromHandle(handle, null);
                }
            }, { passive: false });

            document.addEventListener('touchmove', function (event) {
                if (draggingRow && event.touches && event.touches.length) {
                    event.preventDefault();
                    moveRowToPoint(event.touches[0].clientX, event.touches[0].clientY);
                }
            }, { passive: false });

            document.addEventListener('touchend', finishDrag);
            document.addEventListener('touchcancel', finishDrag);
        }

        refreshPositions();
    });
</script>
@endpush
@endcan
