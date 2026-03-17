@php
    $images = $modelNumber->images()->get()->values();
    $oldOrder = collect(old('image_order', []))
        ->map(fn ($id) => (int) $id)
        ->values();

    if ($oldOrder->isNotEmpty()) {
        $orderMap = array_flip($oldOrder->all());
        $images = $images->sortBy(function ($image) use ($orderMap) {
            return $orderMap[$image->id] ?? (count($orderMap) + (int) $image->sort_order);
        })->values();
    }
@endphp

@push('css')
<style>
    .model-number-image-upload-preview,
    .model-number-image-replace-preview {
        display: none;
        margin-top: 8px;
        max-height: 90px;
        max-width: 140px;
    }
    .model-number-image-drag-cell {
        white-space: nowrap;
        width: 140px;
        vertical-align: middle;
    }
    .model-number-image-drag-content {
        display: flex;
        align-items: center;
        min-height: 72px;
    }
    .model-number-image-drag-handle {
        cursor: grab;
        font-weight: 700;
        letter-spacing: 1px;
        margin-right: 10px;
        user-select: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        font-size: 22px;
        line-height: 1;
        border: 1px solid #cfd8e3;
        border-radius: 8px;
        background: #f8fafc;
        color: #4b5563;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        touch-action: none;
    }
    .model-number-image-drag-handle:hover {
        background: #eef5ff;
        border-color: #9ec5fe;
        color: #1f4f82;
    }
    .model-number-image-drag-handle:active,
    .model-number-image-row.dragging .model-number-image-drag-handle {
        cursor: grabbing;
        background: #dceeff;
        border-color: #3c8dbc;
        color: #1f4f82;
    }
    .model-number-image-drag-handle[disabled] {
        cursor: not-allowed;
        opacity: 0.45;
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #9ca3af;
    }
    .model-number-image-order-label {
        font-size: 12px;
        color: #6b7280;
    }
    .model-number-image-row.drag-over {
        outline: 2px dashed #3c8dbc;
        outline-offset: -2px;
    }
    .model-number-image-row.dragging {
        background: #f5fbff;
        opacity: 0.9;
    }
    .model-number-image-row.is-pending-removal {
        opacity: 0.55;
        background: #fbf5f5;
    }
    .model-number-image-row.is-pending-removal .img-thumbnail {
        filter: grayscale(1);
    }
    .model-number-image-sync-note {
        margin-bottom: 16px;
    }
</style>
@endpush

<div class="row" id="model-number-images">
    <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-sm-offset-0">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ __('Model Number Images') }}</h2>
            </div>
            <div class="box-body">
                <p class="text-muted model-number-image-sync-note">
                    {{ __('These images are used as defaults for assets on this model number when asset-level override is disabled. Image changes are saved together with the model number.') }}
                </p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group{{ $errors->has('new_image.image') ? ' has-error' : '' }}">
                            <label for="model_number_image_upload">{{ __('Add Image') }}</label>
                            <input id="model_number_image_upload"
                                   type="file"
                                   name="new_image[image]"
                                   class="form-control"
                                   accept="image/jpeg,image/png,image/gif"
                                   form="create-form">
                            <img id="model_number_image_upload_preview"
                                 class="img-thumbnail model-number-image-upload-preview"
                                 alt="{{ __('Upload Preview') }}">
                            {!! $errors->first('new_image.image', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group{{ $errors->has('new_image.caption') ? ' has-error' : '' }}">
                            <label for="model_number_image_caption">{{ __('New Image Caption') }}</label>
                            <input id="model_number_image_caption"
                                   type="text"
                                   name="new_image[caption]"
                                   class="form-control"
                                   value="{{ old('new_image.caption') }}"
                                   form="create-form">
                            <span class="help-block">{{ __('New images are appended after the current list when you save.') }}</span>
                            {!! $errors->first('new_image.caption', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>
                </div>

                <hr>

                @if ($images->isEmpty())
                    <p class="text-muted">{{ __('No images configured for this model number yet. Add one above and save the model number.') }}</p>
                @else
                    <div class="alert alert-info">
                        {{ __('Drag rows to reorder images. Caption changes, replacements, and removals are all saved with the model number.') }}
                    </div>

                    {!! $errors->first('existing_images', '<div class="alert alert-danger">:message</div>') !!}
                    {!! $errors->first('image_order', '<div class="alert alert-danger">:message</div>') !!}

                    <div id="model-number-images-order-inputs"></div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">{{ __('Order') }}</th>
                                    <th>{{ __('Preview') }}</th>
                                    <th>{{ __('Caption') }}</th>
                                    <th>{{ __('Replace Image') }}</th>
                                    <th style="width: 180px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="model-number-image-list">
                                @foreach ($images as $image)
                                    @php
                                        $isPendingRemoval = old("existing_images.{$image->id}.delete", false);
                                    @endphp
                                    <tr class="model-number-image-row {{ $isPendingRemoval ? 'is-pending-removal' : '' }}"
                                        data-image-id="{{ $image->id }}">
                                        <td class="model-number-image-drag-cell">
                                            <div class="model-number-image-drag-content">
                                                <button type="button"
                                                        class="model-number-image-drag-handle"
                                                        title="{{ __('Drag to reorder') }}"
                                                        aria-label="{{ __('Drag to reorder') }}"
                                                        {{ $isPendingRemoval ? 'disabled' : '' }}>
                                                    ::
                                                </button>
                                                <span class="model-number-image-order-label js-order-index">{{ $loop->iteration }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <img src="{{ Storage::disk('public')->url($image->file_path) }}"
                                                 alt="{{ $image->caption ?: __('Model Number Image') }}"
                                                 style="max-height: 72px; max-width: 120px;"
                                                 class="img-thumbnail">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   name="existing_images[{{ $image->id }}][caption]"
                                                   class="form-control input-sm js-image-edit-input"
                                                   value="{{ old("existing_images.{$image->id}.caption", $image->caption) }}"
                                                   form="create-form"
                                                   style="min-width: 220px;"
                                                   {{ $isPendingRemoval ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="file"
                                                   name="existing_images[{{ $image->id }}][image]"
                                                   class="form-control input-sm js-replace-input js-image-edit-input"
                                                   accept="image/jpeg,image/png,image/gif"
                                                   data-preview-target="model-number-image-replace-preview-{{ $image->id }}"
                                                   form="create-form"
                                                   {{ $isPendingRemoval ? 'disabled' : '' }}>
                                            <img id="model-number-image-replace-preview-{{ $image->id }}"
                                                 class="img-thumbnail model-number-image-replace-preview"
                                                 alt="{{ __('Replacement Preview') }}">
                                        </td>
                                        <td>
                                            <input type="hidden"
                                                   name="existing_images[{{ $image->id }}][delete]"
                                                   value="{{ $isPendingRemoval ? '1' : '0' }}"
                                                   class="js-delete-input"
                                                   form="create-form">
                                            <button type="button"
                                                    class="btn btn-sm js-toggle-remove {{ $isPendingRemoval ? 'btn-warning' : 'btn-default' }}">
                                                {{ $isPendingRemoval ? __('Undo Remove') : __('Remove') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
<script nonce="{{ csrf_token() }}">
    (function () {
        const mainFormId = 'create-form';
        const uploadInput = document.getElementById('model_number_image_upload');
        const uploadPreview = document.getElementById('model_number_image_upload_preview');
        const reorderInputs = document.getElementById('model-number-images-order-inputs');
        const list = document.getElementById('model-number-image-list');
        const dragHandles = document.querySelectorAll('.model-number-image-drag-handle');

        const previewFile = function (input, previewElement) {
            if (!input || !previewElement || !input.files || !input.files[0]) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                previewElement.src = event.target.result;
                previewElement.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        };

        if (uploadInput && uploadPreview) {
            uploadInput.addEventListener('change', function () {
                previewFile(uploadInput, uploadPreview);
            });
        }

        document.querySelectorAll('.js-replace-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const previewTargetId = input.getAttribute('data-preview-target');
                if (!previewTargetId) {
                    return;
                }
                const preview = document.getElementById(previewTargetId);
                previewFile(input, preview);
            });
        });

        if (!list || !reorderInputs) {
            return;
        }

        const rows = function () {
            return Array.from(list.querySelectorAll('.model-number-image-row'));
        };

        const activeRows = function () {
            return rows().filter(function (row) {
                const deleteInput = row.querySelector('.js-delete-input');
                return !deleteInput || deleteInput.value !== '1';
            });
        };

        const refreshOrderForm = function () {
            reorderInputs.innerHTML = '';

            rows().forEach(function (row) {
                const orderLabel = row.querySelector('.js-order-index');
                const deleteInput = row.querySelector('.js-delete-input');
                const isRemoved = deleteInput && deleteInput.value === '1';

                if (orderLabel) {
                    orderLabel.textContent = isRemoved ? 'x' : '';
                }
            });

            activeRows().forEach(function (row, index) {
                const orderLabel = row.querySelector('.js-order-index');
                if (orderLabel) {
                    orderLabel.textContent = String(index + 1);
                }

                const id = row.getAttribute('data-image-id');
                if (!id) {
                    return;
                }

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'image_order[]';
                hidden.value = id;
                hidden.setAttribute('form', mainFormId);
                reorderInputs.appendChild(hidden);
            });
        };

        const clearDragState = function () {
            rows().forEach(function (row) {
                row.classList.remove('drag-over');
                row.classList.remove('dragging');
            });
        };

        const rowFromPoint = function (clientX, clientY) {
            const element = document.elementFromPoint(clientX, clientY);
            const row = element ? element.closest('.model-number-image-row') : null;

            if (!row) {
                return null;
            }

            const deleteInput = row.querySelector('.js-delete-input');
            return deleteInput && deleteInput.value === '1' ? null : row;
        };

        const applyRemovalState = function (row) {
            const deleteInput = row.querySelector('.js-delete-input');
            const removeButton = row.querySelector('.js-toggle-remove');
            const dragHandle = row.querySelector('.model-number-image-drag-handle');
            const editInputs = row.querySelectorAll('.js-image-edit-input');
            const isRemoved = deleteInput && deleteInput.value === '1';

            row.classList.toggle('is-pending-removal', isRemoved);

            if (dragHandle) {
                dragHandle.disabled = isRemoved;
            }

            editInputs.forEach(function (input) {
                input.disabled = isRemoved;
            });

            if (removeButton) {
                removeButton.classList.toggle('btn-warning', isRemoved);
                removeButton.classList.toggle('btn-default', !isRemoved);
                removeButton.textContent = isRemoved ? '{{ __('Undo Remove') }}' : '{{ __('Remove') }}';
            }
        };

        let dragState = null;

        const handlePointerMove = function (event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            if (!dragState.moved) {
                const movedEnough = Math.abs(event.clientY - dragState.startY) > 4 || Math.abs(event.clientX - dragState.startX) > 4;
                if (!movedEnough) {
                    return;
                }
                dragState.moved = true;
                dragState.row.classList.add('dragging');
            }

            event.preventDefault();
            clearDragState();
            dragState.row.classList.add('dragging');

            const targetRow = rowFromPoint(event.clientX, event.clientY);
            if (!targetRow || targetRow === dragState.row) {
                return;
            }

            const rect = targetRow.getBoundingClientRect();
            const insertAfter = (event.clientY - rect.top) > (rect.height / 2);

            targetRow.classList.add('drag-over');
            if (insertAfter) {
                list.insertBefore(dragState.row, targetRow.nextSibling);
            } else {
                list.insertBefore(dragState.row, targetRow);
            }

            refreshOrderForm();
        };

        const finishPointerDrag = function (event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            clearDragState();
            dragState.handle.releasePointerCapture?.(dragState.pointerId);
            dragState = null;
        };

        dragHandles.forEach(function (handle) {
            handle.addEventListener('pointerdown', function (event) {
                if (handle.disabled) {
                    return;
                }

                if (event.button !== 0 && event.pointerType !== 'touch') {
                    return;
                }

                const row = handle.closest('.model-number-image-row');
                if (!row) {
                    return;
                }

                event.preventDefault();
                dragState = {
                    pointerId: event.pointerId,
                    row: row,
                    handle: handle,
                    startX: event.clientX,
                    startY: event.clientY,
                    moved: false,
                };
                handle.setPointerCapture?.(event.pointerId);
            });
        });

        document.querySelectorAll('.js-toggle-remove').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('.model-number-image-row');
                const deleteInput = row ? row.querySelector('.js-delete-input') : null;
                if (!row || !deleteInput) {
                    return;
                }

                deleteInput.value = deleteInput.value === '1' ? '0' : '1';
                applyRemovalState(row);
                refreshOrderForm();
            });
        });

        document.addEventListener('pointermove', handlePointerMove, { passive: false });
        document.addEventListener('pointerup', finishPointerDrag);
        document.addEventListener('pointercancel', finishPointerDrag);

        rows().forEach(applyRemovalState);
        refreshOrderForm();
    })();
</script>
@endpush
