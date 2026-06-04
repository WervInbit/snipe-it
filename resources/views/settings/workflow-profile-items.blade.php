@extends('layouts/default')

@section('title')
    {{ __('Workflow Profile Items') }}
@parent
@stop

@push('css')
<style nonce="{{ csrf_token() }}">
    .workflow-profile-item-row.dragging {
        opacity: 0.6;
        background: #f8fafc;
    }

    .workflow-profile-item-drag-handle {
        border: 0;
        background: transparent;
        color: #6b7280;
        cursor: grab;
        padding: 6px 8px;
    }

    .workflow-profile-item-drag-handle:active {
        cursor: grabbing;
    }

    .workflow-profile-item-position {
        display: inline-block;
        min-width: 2.5em;
    }

    .workflow-profile-item-flag {
        white-space: nowrap;
    }

    body.workflow-profile-item-reordering {
        cursor: grabbing;
        user-select: none;
    }
</style>
@endpush

@section('header_right')
    <a href="{{ route('settings.workflow-profiles.index') }}" class="btn btn-default">
        {{ __('Workflow Profiles') }}
    </a>
    <a href="{{ route('settings.testtypes.index') }}" class="btn btn-default">
        <x-icon type="tasks" /> {{ __('Workflow Items') }}
    </a>
@stop

@section('content')
    @php
        $profileItems = $workflowProfile->items;
        $profileItemsByItem = $profileItems->keyBy('workflow_item_id');
        $availableItems = $workflowItems
            ->reject(fn ($item) => $profileItemsByItem->has($item->id))
            ->values();
        $sourceLabel = function ($item) {
            return collect([
                $item->applies_to_all ? trans('admin/testtypes/general.always_apply_badge') : null,
                optional($item->attributeDefinition)->label,
                $item->categories->isNotEmpty() ? trans('admin/testtypes/general.asset_categories_prefix', ['list' => $item->categories->pluck('name')->implode(', ')]) : null,
                $item->componentCategories->isNotEmpty() ? trans('admin/testtypes/general.component_categories_prefix', ['list' => $item->componentCategories->pluck('name')->implode(', ')]) : null,
                $item->componentDefinitions->isNotEmpty() ? trans('admin/testtypes/general.component_definitions_prefix', ['list' => $item->componentDefinitions->pluck('name')->implode(', ')]) : null,
            ])->filter()->implode(' / ') ?: trans('general.none');
        };
        $resultModeLabel = function (?string $mode) {
            return $mode === \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE
                ? __('Done / Not Done')
                : __('Pass / Fail');
        };
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $workflowProfile->name }}</h3>
                </div>
                <div class="box-body">
                    @if($workflowProfile->description)
                        <p>{{ $workflowProfile->description }}</p>
                    @endif
                    <p class="text-muted">
                        {{ __('Categories') }}: {{ $workflowProfile->categories->pluck('name')->implode(', ') ?: trans('general.all') }}
                    </p>
                    <p class="text-muted">
                        @if($workflowProfile->is_active)
                            <span class="label label-success">{{ __('Active') }}</span>
                        @else
                            <span class="label label-default">{{ __('Inactive') }}</span>
                        @endif
                        @if($workflowProfile->is_default)
                            <span class="label label-primary">{{ __('Default') }}</span>
                        @endif
                        @if($workflowProfile->blocks_sale_readiness)
                            <span class="label label-warning">{{ __('Blocks sale') }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.workflow-profiles.items.update', $workflowProfile) }}" data-testid="workflow-profile-items-form">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Included Items') }}</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width:48px;">{{ __('Move') }}</th>
                                    <th style="width:90px;">{{ __('Order') }}</th>
                                    <th>{{ __('Workflow Item') }}</th>
                                    <th>{{ __('Source') }}</th>
                                    <th>{{ __('Defaults') }}</th>
                                    <th class="text-right">{{ trans('button.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody data-profile-item-reorder-body
                                   data-reorder-url="{{ route('settings.workflow-profiles.items.reorder', $workflowProfile) }}"
                                   data-reorder-failed="{{ __('Failed to reorder workflow profile items.') }}">
                                @forelse($profileItems as $profileItem)
                                    @php
                                        $item = $profileItem->item;
                                        $labelMode = $item?->result_label_mode
                                            ?: \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL;
                                    @endphp
                                    @if(!$item)
                                        @continue
                                    @endif
                                    <tr class="workflow-profile-item-row"
                                        data-profile-item-id="{{ $profileItem->id }}"
                                        data-testid="workflow-profile-item-row">
                                        <td>
                                            <button type="button"
                                                    class="workflow-profile-item-drag-handle"
                                                    data-profile-item-drag-handle
                                                    title="{{ trans('admin/testtypes/general.drag_to_reorder') }}"
                                                    aria-label="{{ trans('admin/testtypes/general.drag_to_reorder') }}">
                                                <i class="fas fa-grip-vertical" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="workflow-profile-item-position" data-profile-item-position>{{ $loop->iteration }}</span>
                                            <input type="hidden"
                                                   class="js-profile-item-order"
                                                   name="items[{{ $item->id }}][sort_order]"
                                                   value="{{ $loop->index }}">
                                            <input type="hidden" name="items[{{ $item->id }}][enabled]" value="1">
                                            <input type="hidden" name="items[{{ $item->id }}][is_required]" value="{{ $item->is_required ? 1 : 0 }}">
                                            <input type="hidden" name="items[{{ $item->id }}][result_label_mode]" value="{{ $labelMode }}">
                                        </td>
                                        <td>
                                            <strong>{{ $item->name }}</strong>
                                            <div class="text-muted monospace">{{ $item->slug }}</div>
                                        </td>
                                        <td>{{ $sourceLabel($item) }}</td>
                                        <td>
                                            <span class="label {{ $item->is_required ? 'label-primary' : 'label-default' }}">
                                                {{ $item->is_required ? __('Required') : __('Optional') }}
                                            </span>
                                            <span class="label label-default">{{ $resultModeLabel($labelMode) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <button type="submit"
                                                    name="items[{{ $item->id }}][remove]"
                                                    value="1"
                                                    class="btn btn-danger btn-sm">
                                                {{ trans('button.remove') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No items are included in this profile yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-right">
                        <button type="submit" class="btn btn-primary">{{ __('Save Item Order') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ __('Available Items') }}</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Add') }}</th>
                                    <th>{{ __('Workflow Item') }}</th>
                                    <th>{{ __('Source') }}</th>
                                    <th>{{ __('Defaults') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($availableItems as $item)
                                    @php
                                        $labelMode = $item->result_label_mode ?? \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL;
                                    @endphp
                                    <tr data-testid="workflow-profile-available-item-row">
                                        <td class="workflow-profile-item-flag">
                                            <input type="hidden" name="items[{{ $item->id }}][enabled]" value="0">
                                            <input type="hidden" name="items[{{ $item->id }}][sort_order]" value="{{ $profileItems->count() + $loop->index }}">
                                            <input type="hidden" name="items[{{ $item->id }}][is_required]" value="{{ $item->is_required ? 1 : 0 }}">
                                            <input type="hidden" name="items[{{ $item->id }}][result_label_mode]" value="{{ $labelMode }}">
                                            <button type="submit"
                                                    name="items[{{ $item->id }}][enabled]"
                                                    value="1"
                                                    class="btn btn-default btn-sm">
                                                <x-icon type="plus" /> {{ __('Add') }}
                                            </button>
                                        </td>
                                        <td>
                                            <strong>{{ $item->name }}</strong>
                                            <div class="text-muted monospace">{{ $item->slug }}</div>
                                        </td>
                                        <td>{{ $sourceLabel($item) }}</td>
                                        <td>
                                            <span class="label {{ $item->is_required ? 'label-primary' : 'label-default' }}">
                                                {{ $item->is_required ? __('Required') : __('Optional') }}
                                            </span>
                                            <span class="label label-default">{{ $resultModeLabel($labelMode) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('Every workflow item is already included in this profile.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
<script nonce="{{ csrf_token() }}">
    document.addEventListener('DOMContentLoaded', function () {
        var reorderBody = document.querySelector('[data-profile-item-reorder-body]');
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
            return Array.from(reorderBody.querySelectorAll('tr[data-profile-item-id]'));
        }

        function readOrder() {
            return rows().map(function (row) {
                return Number(row.dataset.profileItemId);
            });
        }

        function refreshOrderInputs() {
            rows().forEach(function (row, index) {
                var position = row.querySelector('[data-profile-item-position]');
                var input = row.querySelector('.js-profile-item-order');

                if (position) {
                    position.textContent = String(index + 1);
                }

                if (input) {
                    input.value = String(index);
                }
            });
        }

        function applyOrder(order) {
            var rowMap = new Map(rows().map(function (row) {
                return [Number(row.dataset.profileItemId), row];
            }));

            order.forEach(function (id) {
                var row = rowMap.get(Number(id));
                if (row) {
                    reorderBody.appendChild(row);
                }
            });

            refreshOrderInputs();
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
            var targetRow = target ? target.closest('tr[data-profile-item-id]') : null;
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

            refreshOrderInputs();
        }

        function beginDrag(row, handle, pointerId) {
            draggingRow = row;
            activeHandle = handle;
            activePointerId = typeof pointerId === 'number' ? pointerId : null;
            originalOrder = readOrder();
            draggingRow.classList.add('dragging');
            document.body.classList.add('workflow-profile-item-reordering');
        }

        function finishDrag() {
            if (!draggingRow) {
                return;
            }

            draggingRow.classList.remove('dragging');
            draggingRow = null;
            document.body.classList.remove('workflow-profile-item-reordering');

            if (activeHandle && activePointerId !== null && typeof activeHandle.releasePointerCapture === 'function') {
                try {
                    activeHandle.releasePointerCapture(activePointerId);
                } catch (e) {
                    // ignore release errors
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
                    window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profile items.');
                }
            }).catch(function () {
                applyOrder(originalOrder);
                window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder workflow profile items.');
            });
        }

        function findHandleFromEvent(event) {
            return event.target && event.target.closest
                ? event.target.closest('[data-profile-item-drag-handle]')
                : null;
        }

        function startFromHandle(handle, pointerId) {
            var row = handle.closest('tr[data-profile-item-id]');
            if (!row) {
                return;
            }

            beginDrag(row, handle, pointerId);
        }

        function isPrimaryPointerDown(event) {
            if (event.pointerType === 'mouse') {
                return event.button === 0 || event.buttons === 1;
            }

            return true;
        }

        if (supportsPointerEvents) {
            reorderBody.addEventListener('pointerdown', function (event) {
                if (!isPrimaryPointerDown(event)) {
                    return;
                }

                var handle = findHandleFromEvent(event);
                if (!handle) {
                    return;
                }

                event.preventDefault();
                startFromHandle(handle, event.pointerId);

                if (event.pointerId !== undefined && typeof handle.setPointerCapture === 'function') {
                    try {
                        handle.setPointerCapture(event.pointerId);
                    } catch (e) {
                        // ignore capture errors
                    }
                }
            });

            document.addEventListener('pointermove', function (event) {
                if (!draggingRow) {
                    return;
                }
                if (activePointerId !== null && event.pointerId !== activePointerId) {
                    return;
                }

                event.preventDefault();
                moveRowToPoint(event.clientX, event.clientY);
            }, { passive: false });

            document.addEventListener('pointerup', function (event) {
                if (!draggingRow) {
                    return;
                }
                if (activePointerId !== null && event.pointerId !== activePointerId) {
                    return;
                }

                finishDrag();
            });

            document.addEventListener('pointercancel', function (event) {
                if (!draggingRow) {
                    return;
                }
                if (activePointerId !== null && event.pointerId !== activePointerId) {
                    return;
                }

                finishDrag();
            });
        } else {
            reorderBody.addEventListener('mousedown', function (event) {
                if (event.button !== 0) {
                    return;
                }

                var handle = findHandleFromEvent(event);
                if (!handle) {
                    return;
                }

                event.preventDefault();
                startFromHandle(handle, null);
            });

            reorderBody.addEventListener('touchstart', function (event) {
                var handle = findHandleFromEvent(event);
                if (!handle || !event.touches || !event.touches.length) {
                    return;
                }

                event.preventDefault();
                startFromHandle(handle, null);
            }, { passive: false });

            document.addEventListener('mousemove', function (event) {
                if (!draggingRow) {
                    return;
                }

                event.preventDefault();
                moveRowToPoint(event.clientX, event.clientY);
            });

            document.addEventListener('mouseup', function () {
                finishDrag();
            });

            document.addEventListener('touchmove', function (event) {
                if (!draggingRow || !event.touches || !event.touches.length) {
                    return;
                }

                event.preventDefault();
                moveRowToPoint(event.touches[0].clientX, event.touches[0].clientY);
            }, { passive: false });

            document.addEventListener('touchend', function () {
                finishDrag();
            });

            document.addEventListener('touchcancel', function () {
                finishDrag();
            });
        }

        refreshOrderInputs();
    });
</script>
@endpush
