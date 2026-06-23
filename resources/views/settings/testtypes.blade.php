@extends('layouts/default')

@section('title')
    {{ __('Workflow Items') }}
@parent
@stop

@push('css')
<style nonce="{{ csrf_token() }}">
    .testtype-reorder-col {
        width: 56px;
        text-align: center;
        vertical-align: middle !important;
    }

    .testtype-reorder-handle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        margin: 0 auto;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #6b7280;
        cursor: grab;
        touch-action: none;
        user-select: none;
    }

    .testtype-reorder-handle i {
        font-size: 1.05rem;
    }

    .testtype-reorder-handle:active {
        cursor: grabbing;
    }

    .testtype-reorder-row {
        cursor: default;
    }

    .testtype-reorder-row.dragging {
        opacity: 0.6;
        background: #f8fafc;
    }

    body.testtype-reordering {
        user-select: none;
        cursor: grabbing !important;
    }

    #create-testtype-modal .select2-container,
    [id^="edit-testtype-"][id$="-modal"] .select2-container {
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
    }
</style>
@endpush

@section('header_right')
    <a href="{{ route('settings.workflow-profiles.index') }}" class="btn btn-default">
        <x-icon type="tasks" /> {{ __('Workflow Profiles') }}
    </a>
    @can('create', \App\Models\TestType::class)
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#create-testtype-modal">
            <x-icon type="plus" /> {{ trans('admin/testtypes/general.create_title') }}
        </button>
    @endcan
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ trans('admin/testtypes/general.existing_title') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="testtype-reorder-col">{{ trans('general.order') }}</th>
                                <th>{{ trans('admin/testtypes/general.name') }}</th>
                                <th>{{ trans('admin/testtypes/general.slug') }}</th>
                                <th>{{ trans('admin/testtypes/general.applicability') }}</th>
                                <th>{{ trans('admin/testtypes/general.required') }}</th>
                                <th>{{ trans('admin/testtypes/general.result_buttons') }}</th>
                                <th>{{ trans('admin/testtypes/general.instructions') }}</th>
                                <th>{{ trans('admin/testtypes/general.tooltip') }}</th>
                                <th class="text-right">{{ trans('button.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody data-testtype-reorder-body
                               data-reorder-url="{{ route('settings.testtypes.reorder') }}"
                               data-reorder-failed="{{ trans('admin/testtypes/general.reorder_failed') }}">
                            @forelse($testTypes as $type)
                                @php
                                    $isEditContext = old('modal') === 'edit-testtype-' . $type->id . '-modal';
                                    $instructionPreview = $type->instructions ? \Illuminate\Support\Str::limit(strip_tags($type->instructions), 80) : trans('general.none');
                                    $tooltipPreview = $type->tooltip ?: trans('general.none');
                                    $labelMode = $type->result_label_mode ?? \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL;
                                    $sourceParts = collect([
                                        $type->applies_to_all ? trans('admin/testtypes/general.always_apply_badge') : null,
                                        optional($type->attributeDefinition)->label,
                                        $type->categories->isNotEmpty() ? trans('admin/testtypes/general.asset_categories_prefix', ['list' => $type->categories->pluck('name')->implode(', ')]) : null,
                                        $type->componentCategories->isNotEmpty() ? trans('admin/testtypes/general.component_categories_prefix', ['list' => $type->componentCategories->pluck('name')->implode(', ')]) : null,
                                        $type->componentDefinitions->isNotEmpty() ? trans('admin/testtypes/general.component_definitions_prefix', ['list' => $type->componentDefinitions->pluck('name')->implode(', ')]) : null,
                                    ])->filter()->implode(' / ');
                                @endphp
                                <tr class="testtype-reorder-row" data-testtype-id="{{ $type->id }}">
                                    <td class="text-center">
                                        <button type="button"
                                                class="testtype-reorder-handle"
                                                data-testtype-drag-handle
                                                title="{{ trans('admin/testtypes/general.drag_to_reorder') }}"
                                                aria-label="{{ trans('admin/testtypes/general.drag_to_reorder') }}">
                                            <i class="fas fa-grip-vertical" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                    <td>{{ $type->name }}</td>
                                    <td class="monospace text-muted">{{ $type->slug }}</td>
                                    <td>{{ $sourceParts ?: trans('general.none') }}</td>
                                    <td>{!! $type->is_required ? '<i class="fas fa-check text-success"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                    <td>
                                        {{ $labelMode === \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE ? __('Done / Not Done') : __('Pass / Fail') }}
                                    </td>
                                    <td title="{{ $type->instructions ?? '' }}">{{ $instructionPreview }}</td>
                                    <td title="{{ $type->tooltip ?? '' }}">{{ $tooltipPreview }}</td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="{{ trans('button.actions') }}">
                                            @can('update', $type)
                                                <button type="button"
                                                        class="btn btn-default"
                                                        data-toggle="modal"
                                                        data-target="#edit-testtype-{{ $type->id }}-modal">
                                                    {{ trans('button.edit') }}
                                                </button>
                                            @endcan
                                            @can('delete', $type)
                                                <form method="POST"
                                                      action="{{ route('settings.testtypes.destroy', $type) }}"
                                                      style="display:inline-block"
                                                      onsubmit="return confirm('{{ trans('admin/testtypes/general.delete_confirm') }}');">
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
                                    <td colspan="9" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('create', \App\Models\TestType::class)
        @php
            $createContext = old('modal') === 'create-testtype-modal';
            $createManualSlugOverride = $createContext ? (bool) old('manual_slug_override', false) : false;
            $createOldName = $createContext ? trim((string) old('name', '')) : '';
            $createOldSlug = $createContext ? trim((string) old('slug', '')) : '';
            $createSlugValue = $createOldSlug !== ''
                ? $createOldSlug
                : ($createOldName !== '' ? \App\Models\TestType::normalizeSlugSource($createOldName) : '');
        @endphp
        <div class="modal fade" id="create-testtype-modal" tabindex="-1" role="dialog" aria-labelledby="create-testtype-modal-label">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('settings.testtypes.store') }}">
                    @csrf
                    <input type="hidden" name="modal" value="create-testtype-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="create-testtype-modal-label">{{ trans('admin/testtypes/general.create_title') }}</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group{{ $createContext && $errors->has('name') ? ' has-error' : '' }}">
                                <label for="create-name">{{ trans('admin/testtypes/general.name') }}</label>
                                <input type="text" class="form-control" id="create-name" name="name" value="{{ $createContext ? old('name') : '' }}">
                                @if($createContext && $errors->has('name'))
                                    <span class="help-block">{{ $errors->first('name') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('slug') ? ' has-error' : '' }}">
                                <label for="create-slug">{{ trans('admin/testtypes/general.slug') }}</label>
                                <input type="hidden" name="manual_slug_override" value="0">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox"
                                               name="manual_slug_override"
                                               value="1"
                                               class="js-manual-slug-toggle"
                                               data-slug-target="#create-slug"
                                               data-name-target="#create-name"
                                               {{ $createManualSlugOverride ? 'checked' : '' }}>
                                        {{ trans('admin/testtypes/general.slug_override_label') }}
                                    </label>
                                </div>
                                <input type="text"
                                       class="form-control js-slug-input"
                                       id="create-slug"
                                       name="slug"
                                       value="{{ $createSlugValue }}"
                                       data-name-target="#create-name"
                                       {{ $createManualSlugOverride ? '' : 'disabled' }}>
                                <span class="help-block">{{ trans('admin/testtypes/general.slug_hint') }}</span>
                                @if($createContext && $errors->has('slug'))
                                    <span class="help-block">{{ $errors->first('slug') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('attribute_definition_id') ? ' has-error' : '' }}">
                                <label for="create-attribute">{{ trans('admin/testtypes/general.attribute') }}</label>
                                <select name="attribute_definition_id"
                                        id="create-attribute"
                                        class="form-control js-test-attribute"
                                        data-placeholder="{{ trans('admin/testtypes/general.attribute_placeholder') }}">
                                    <option value="">{{ trans('general.none') }}</option>
                                    @foreach($attributeDefinitions as $attribute)
                                        <option value="{{ $attribute->id }}"{{ $createContext && (string)old('attribute_definition_id') === (string)$attribute->id ? ' selected' : '' }}>
                                            {{ $attribute->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($createContext && $errors->has('attribute_definition_id'))
                                    <span class="help-block">{{ $errors->first('attribute_definition_id') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('applies_to_all') ? ' has-error' : '' }}">
                                <label class="control-label">{{ trans('admin/testtypes/general.always_apply') }}</label>
                                <input type="hidden" name="applies_to_all" value="0">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="applies_to_all" value="1" {{ $createContext && old('applies_to_all') ? 'checked' : '' }}>
                                        {{ trans('admin/testtypes/general.always_apply_label') }}
                                    </label>
                                </div>
                                <span class="help-block">{{ trans('admin/testtypes/general.always_apply_help') }}</span>
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('category_ids') ? ' has-error' : '' }}">
                                <label for="create-categories">{{ trans('admin/testtypes/general.asset_categories') }}</label>
                                <select name="category_ids[]"
                                        id="create-categories"
                                        class="form-control js-test-category"
                                        data-placeholder="{{ trans('admin/testtypes/general.category_placeholder') }}"
                                        multiple>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"{{ $createContext && in_array($category->id, (array) old('category_ids', [])) ? ' selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="help-block">{{ trans('admin/testtypes/general.category_help') }}</span>
                                @if($createContext && $errors->has('category_ids'))
                                    <span class="help-block">{{ $errors->first('category_ids') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('component_category_ids') ? ' has-error' : '' }}">
                                <label for="create-component-categories">{{ trans('admin/testtypes/general.component_categories') }}</label>
                                <select name="component_category_ids[]"
                                        id="create-component-categories"
                                        class="form-control js-test-category"
                                        data-placeholder="{{ trans('admin/testtypes/general.component_category_placeholder') }}"
                                        multiple>
                                    @foreach($componentCategories as $category)
                                        <option value="{{ $category->id }}"{{ $createContext && in_array($category->id, (array) old('component_category_ids', [])) ? ' selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="help-block">{{ trans('admin/testtypes/general.component_category_help') }}</span>
                                @if($createContext && $errors->has('component_category_ids'))
                                    <span class="help-block">{{ $errors->first('component_category_ids') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('component_definition_ids') ? ' has-error' : '' }}">
                                <label for="create-component-definitions">{{ trans('admin/testtypes/general.component_definitions') }}</label>
                                <select name="component_definition_ids[]"
                                        id="create-component-definitions"
                                        class="form-control js-test-category"
                                        data-placeholder="{{ trans('admin/testtypes/general.component_definition_placeholder') }}"
                                        multiple>
                                    @foreach($componentDefinitions as $definition)
                                        <option value="{{ $definition->id }}"{{ $createContext && in_array($definition->id, (array) old('component_definition_ids', [])) ? ' selected' : '' }}>
                                            {{ $definition->name }}{{ $definition->category ? ' - '.$definition->category->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="help-block">{{ trans('admin/testtypes/general.component_definition_help') }}</span>
                                @if($createContext && $errors->has('component_definition_ids'))
                                    <span class="help-block">{{ $errors->first('component_definition_ids') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('is_required') ? ' has-error' : '' }}">
                                <label class="control-label">{{ trans('admin/testtypes/general.required') }}</label>
                                <input type="hidden" name="is_required" value="0">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="is_required" value="1" {{ $createContext ? (old('is_required', true) ? 'checked' : '') : 'checked' }}>
                                        {{ trans('admin/testtypes/general.required_label') }}
                                    </label>
                                </div>
                                <span class="help-block">{{ trans('admin/testtypes/general.required_help') }}</span>
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('result_label_mode') ? ' has-error' : '' }}">
                                <label for="create-result-label-mode">{{ trans('admin/testtypes/general.result_buttons') }}</label>
                                @php
                                    $createResultLabelMode = $createContext
                                        ? old('result_label_mode', \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL)
                                        : \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL;
                                @endphp
                                <select name="result_label_mode" id="create-result-label-mode" class="form-control">
                                    <option value="{{ \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL }}" @selected($createResultLabelMode === \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL)>
                                        {{ __('Pass / Fail') }}
                                    </option>
                                    <option value="{{ \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE }}" @selected($createResultLabelMode === \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE)>
                                        {{ __('Done / Not Done') }}
                                    </option>
                                </select>
                                <span class="help-block">{{ trans('admin/testtypes/general.result_buttons_help') }}</span>
                                @if($createContext && $errors->has('result_label_mode'))
                                    <span class="help-block">{{ $errors->first('result_label_mode') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('instructions') ? ' has-error' : '' }}">
                                <label for="create-instructions">{{ trans('admin/testtypes/general.instructions') }}</label>
                                <textarea name="instructions"
                                          id="create-instructions"
                                          class="form-control"
                                          rows="3">{{ $createContext ? old('instructions') : '' }}</textarea>
                                @if($createContext && $errors->has('instructions'))
                                    <span class="help-block">{{ $errors->first('instructions') }}</span>
                                @endif
                            </div>
                            <div class="form-group{{ $createContext && $errors->has('tooltip') ? ' has-error' : '' }}">
                                <label for="create-tooltip">{{ trans('admin/testtypes/general.tooltip') }}</label>
                                <input type="text" class="form-control" id="create-tooltip" name="tooltip" value="{{ $createContext ? old('tooltip') : '' }}">
                                @if($createContext && $errors->has('tooltip'))
                                    <span class="help-block">{{ $errors->first('tooltip') }}</span>
                                @endif
                            </div>
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

    @foreach($testTypes as $type)
        @can('update', $type)
            @php
                $isEditContext = old('modal') === 'edit-testtype-' . $type->id . '-modal';
                $editManualSlugOverride = $isEditContext
                    ? (bool) old('manual_slug_override', 0)
                    : !\App\Models\TestType::slugUsesAutomaticPattern($type->name, $type->slug);
                $editNameValue = $isEditContext ? old('name', $type->name) : $type->name;
                $editSlugValue = $isEditContext
                    ? trim((string) old('slug', $type->slug))
                    : $type->slug;

                if ($editSlugValue === '' && trim((string) $editNameValue) !== '') {
                    $editSlugValue = \App\Models\TestType::normalizeSlugSource($editNameValue);
                }
            @endphp
            <div class="modal fade" id="edit-testtype-{{ $type->id }}-modal" tabindex="-1" role="dialog" aria-labelledby="edit-testtype-{{ $type->id }}-label">
                <div class="modal-dialog" role="document">
                    <form method="POST" action="{{ route('settings.testtypes.update', $type) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="modal" value="edit-testtype-{{ $type->id }}-modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="edit-testtype-{{ $type->id }}-label">{{ trans('admin/testtypes/general.edit_title', ['name' => $type->name]) }}</h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group{{ $isEditContext && $errors->has('name') ? ' has-error' : '' }}">
                                    <label for="edit-name-{{ $type->id }}">{{ trans('admin/testtypes/general.name') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           id="edit-name-{{ $type->id }}"
                                           name="name"
                                           value="{{ $isEditContext ? old('name', $type->name) : $type->name }}">
                                    @if($isEditContext)
                                        @error('name')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('slug') ? ' has-error' : '' }}">
                                    <label for="edit-slug-{{ $type->id }}">{{ trans('admin/testtypes/general.slug') }}</label>
                                    <input type="hidden" name="manual_slug_override" value="0">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox"
                                                   name="manual_slug_override"
                                                   value="1"
                                                   class="js-manual-slug-toggle"
                                                   data-slug-target="#edit-slug-{{ $type->id }}"
                                                   data-name-target="#edit-name-{{ $type->id }}"
                                                   {{ $editManualSlugOverride ? 'checked' : '' }}>
                                            {{ trans('admin/testtypes/general.slug_override_label') }}
                                        </label>
                                    </div>
                                    <input type="text"
                                           class="form-control js-slug-input"
                                           id="edit-slug-{{ $type->id }}"
                                           name="slug"
                                           value="{{ $editSlugValue }}"
                                           data-name-target="#edit-name-{{ $type->id }}"
                                           {{ $editManualSlugOverride ? '' : 'disabled' }}>
                                    <span class="help-block">{{ trans('admin/testtypes/general.slug_hint') }}</span>
                                    @if($isEditContext)
                                        @error('slug')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('attribute_definition_id') ? ' has-error' : '' }}">
                                    <label for="edit-attribute-{{ $type->id }}">{{ trans('admin/testtypes/general.attribute') }}</label>
                                    <select name="attribute_definition_id"
                                            id="edit-attribute-{{ $type->id }}"
                                            class="form-control js-test-attribute"
                                            data-placeholder="{{ trans('admin/testtypes/general.attribute_placeholder') }}">
                                        <option value="">{{ trans('general.none') }}</option>
                                        @foreach($attributeDefinitions as $attribute)
                                            @php
                                                $selectedValue = $isEditContext ? old('attribute_definition_id', $type->attribute_definition_id) : $type->attribute_definition_id;
                                            @endphp
                                            <option value="{{ $attribute->id }}"{{ (string)$selectedValue === (string)$attribute->id ? ' selected' : '' }}>
                                                {{ $attribute->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($isEditContext)
                                        @error('attribute_definition_id')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('applies_to_all') ? ' has-error' : '' }}">
                                    <label class="control-label">{{ trans('admin/testtypes/general.always_apply') }}</label>
                                    <input type="hidden" name="applies_to_all" value="0">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="applies_to_all" value="1" {{ $isEditContext ? (old('applies_to_all', $type->applies_to_all) ? 'checked' : '') : ($type->applies_to_all ? 'checked' : '') }}>
                                            {{ trans('admin/testtypes/general.always_apply_label') }}
                                        </label>
                                    </div>
                                    <span class="help-block">{{ trans('admin/testtypes/general.always_apply_help') }}</span>
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('category_ids') ? ' has-error' : '' }}">
                                    <label for="edit-categories-{{ $type->id }}">{{ trans('admin/testtypes/general.asset_categories') }}</label>
                                    @php
                                        $selectedCategories = $isEditContext
                                            ? (array) old('category_ids', $type->categories->pluck('id')->toArray())
                                            : $type->categories->pluck('id')->toArray();
                                    @endphp
                                    <select name="category_ids[]"
                                            id="edit-categories-{{ $type->id }}"
                                            class="form-control js-test-category"
                                            data-placeholder="{{ trans('admin/testtypes/general.category_placeholder') }}"
                                            multiple>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"{{ in_array($category->id, $selectedCategories) ? ' selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="help-block">{{ trans('admin/testtypes/general.category_help') }}</span>
                                    @if($isEditContext)
                                        @error('category_ids')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('component_category_ids') ? ' has-error' : '' }}">
                                    <label for="edit-component-categories-{{ $type->id }}">{{ trans('admin/testtypes/general.component_categories') }}</label>
                                    @php
                                        $selectedComponentCategories = $isEditContext
                                            ? (array) old('component_category_ids', $type->componentCategories->pluck('id')->toArray())
                                            : $type->componentCategories->pluck('id')->toArray();
                                    @endphp
                                    <select name="component_category_ids[]"
                                            id="edit-component-categories-{{ $type->id }}"
                                            class="form-control js-test-category"
                                            data-placeholder="{{ trans('admin/testtypes/general.component_category_placeholder') }}"
                                            multiple>
                                        @foreach($componentCategories as $category)
                                            <option value="{{ $category->id }}"{{ in_array($category->id, $selectedComponentCategories) ? ' selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="help-block">{{ trans('admin/testtypes/general.component_category_help') }}</span>
                                    @if($isEditContext)
                                        @error('component_category_ids')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('component_definition_ids') ? ' has-error' : '' }}">
                                    <label for="edit-component-definitions-{{ $type->id }}">{{ trans('admin/testtypes/general.component_definitions') }}</label>
                                    @php
                                        $selectedComponentDefinitions = $isEditContext
                                            ? (array) old('component_definition_ids', $type->componentDefinitions->pluck('id')->toArray())
                                            : $type->componentDefinitions->pluck('id')->toArray();
                                    @endphp
                                    <select name="component_definition_ids[]"
                                            id="edit-component-definitions-{{ $type->id }}"
                                            class="form-control js-test-category"
                                            data-placeholder="{{ trans('admin/testtypes/general.component_definition_placeholder') }}"
                                            multiple>
                                        @foreach($componentDefinitions as $definition)
                                            <option value="{{ $definition->id }}"{{ in_array($definition->id, $selectedComponentDefinitions) ? ' selected' : '' }}>
                                                {{ $definition->name }}{{ $definition->category ? ' - '.$definition->category->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="help-block">{{ trans('admin/testtypes/general.component_definition_help') }}</span>
                                    @if($isEditContext)
                                        @error('component_definition_ids')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('is_required') ? ' has-error' : '' }}">
                                    <label class="control-label">{{ trans('admin/testtypes/general.required') }}</label>
                                    <input type="hidden" name="is_required" value="0">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="is_required" value="1" {{ $isEditContext ? (old('is_required', $type->is_required) ? 'checked' : '') : ($type->is_required ? 'checked' : '') }}>
                                            {{ trans('admin/testtypes/general.required_label') }}
                                        </label>
                                    </div>
                                    <span class="help-block">{{ trans('admin/testtypes/general.required_help') }}</span>
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('result_label_mode') ? ' has-error' : '' }}">
                                    <label for="edit-result-label-mode-{{ $type->id }}">{{ trans('admin/testtypes/general.result_buttons') }}</label>
                                    @php
                                        $editResultLabelMode = $isEditContext
                                            ? old('result_label_mode', $type->result_label_mode)
                                            : ($type->result_label_mode ?? \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL);
                                    @endphp
                                    <select name="result_label_mode" id="edit-result-label-mode-{{ $type->id }}" class="form-control">
                                        <option value="{{ \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL }}" @selected($editResultLabelMode === \App\Models\WorkflowProfileItem::LABEL_MODE_PASS_FAIL)>
                                            {{ __('Pass / Fail') }}
                                        </option>
                                        <option value="{{ \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE }}" @selected($editResultLabelMode === \App\Models\WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE)>
                                            {{ __('Done / Not Done') }}
                                        </option>
                                    </select>
                                    <span class="help-block">{{ trans('admin/testtypes/general.result_buttons_help') }}</span>
                                    @if($isEditContext)
                                        @error('result_label_mode')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('instructions') ? ' has-error' : '' }}">
                                    <label for="edit-instructions-{{ $type->id }}">{{ trans('admin/testtypes/general.instructions') }}</label>
                                    <textarea name="instructions"
                                              id="edit-instructions-{{ $type->id }}"
                                              class="form-control"
                                              rows="3">{{ $isEditContext ? old('instructions', $type->instructions) : $type->instructions }}</textarea>
                                    @if($isEditContext)
                                        @error('instructions')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                                <div class="form-group{{ $isEditContext && $errors->has('tooltip') ? ' has-error' : '' }}">
                                    <label for="edit-tooltip-{{ $type->id }}">{{ trans('admin/testtypes/general.tooltip') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           id="edit-tooltip-{{ $type->id }}"
                                           name="tooltip"
                                           value="{{ $isEditContext ? old('tooltip', $type->tooltip) : $type->tooltip }}">
                                    @if($isEditContext)
                                        @error('tooltip')
                                            <span class="help-block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endforeach
@endsection

@push('js')
<script nonce="{{ csrf_token() }}">
    $(function () {
        function slugifyValue(value, fallback) {
            var source = (value || '').toString();
            var normalizedSource = typeof source.normalize === 'function'
                ? source.normalize('NFD')
                : source;
            var slug = normalizedSource
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            if (slug) {
                return slug;
            }

            return fallback || '';
        }

        function refreshSlugState($toggle) {
            var $slugInput = $($toggle.data('slug-target'));
            var $nameInput = $($toggle.data('name-target'));
            var manualOverride = $toggle.is(':checked');
            var nameValue = $nameInput.val();

            $slugInput.prop('disabled', !manualOverride);

            if (!manualOverride) {
                $slugInput.val(slugifyValue(nameValue, $.trim(nameValue) !== '' ? 'test-type' : ''));
                return;
            }

            $slugInput.val(slugifyValue($slugInput.val(), $.trim($slugInput.val()) !== '' ? 'test-type' : ''));
        }

        function initSlugControls($context) {
            $context.find('.js-manual-slug-toggle').each(function () {
                refreshSlugState($(this));
            });

            $context.find('.js-manual-slug-toggle').off('.slugToggle').on('change.slugToggle', function () {
                refreshSlugState($(this));
            });

            $context.find('input[id^="create-name"], input[id^="edit-name-"]').off('.slugName').on('input.slugName', function () {
                var $input = $(this);
                $context.find('.js-manual-slug-toggle[data-name-target="#' + $input.attr('id') + '"]').each(function () {
                    refreshSlugState($(this));
                });
            });

            $context.find('.js-slug-input').off('.slugInput').on('input.slugInput blur.slugInput', function () {
                var $input = $(this);
                if ($input.prop('disabled')) {
                    return;
                }

                $input.val(slugifyValue($input.val(), $.trim($input.val()) !== '' ? 'test-type' : ''));
            });
        }

        function initSelect2($context) {
            $context.find('.js-test-attribute').each(function () {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.next('.select2-container').css({
                        width: '100%',
                        maxWidth: '100%'
                    });
                    return;
                }

                var parent = $select.closest('.modal');

                $select.select2({
                    allowClear: true,
                    placeholder: function () {
                        return $select.data('placeholder');
                    },
                    width: '100%',
                    dropdownParent: parent.length ? parent : $(document.body)
                });
            });

            $context.find('.js-test-category').each(function () {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.next('.select2-container').css({
                        width: '100%',
                        maxWidth: '100%'
                    });
                    return;
                }

                var parent = $select.closest('.modal');

                $select.select2({
                    placeholder: function () {
                        return $select.data('placeholder');
                    },
                    width: '100%',
                    dropdownParent: parent.length ? parent : $(document.body)
                });
            });
        }

        initSelect2($(document));
        initSlugControls($(document));

        $('.modal').on('shown.bs.modal', function () {
            initSelect2($(this));
            initSlugControls($(this));
        });

        var modalToOpen = @json(old('modal'));
        if (modalToOpen) {
            $('#' + modalToOpen).modal('show');
        }

        var $reorderBody = $('[data-testtype-reorder-body]');
        if (!$reorderBody.length) {
            return;
        }

        var reorderBody = $reorderBody.get(0);
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        var supportsPointerEvents = typeof window.PointerEvent !== 'undefined';
        var draggingRow = null;
        var activePointerId = null;
        var activeHandle = null;
        var originalOrder = [];

        function rows() {
            return Array.from(reorderBody.querySelectorAll('tr[data-testtype-id]'));
        }

        function readOrder() {
            return rows().map(function (row) {
                return Number(row.dataset.testtypeId);
            });
        }

        function applyOrder(order) {
            var rowMap = new Map(rows().map(function (row) {
                return [Number(row.dataset.testtypeId), row];
            }));

            order.forEach(function (id) {
                var row = rowMap.get(Number(id));
                if (row) {
                    reorderBody.appendChild(row);
                }
            });
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
                $.ajax({
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
            var targetRow = target ? target.closest('tr[data-testtype-id]') : null;
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
        }

        function beginDrag(row, handle, pointerId) {
            draggingRow = row;
            activeHandle = handle;
            activePointerId = typeof pointerId === 'number' ? pointerId : null;
            originalOrder = readOrder();
            draggingRow.classList.add('dragging');
            document.body.classList.add('testtype-reordering');
        }

        function finishDrag() {
            if (!draggingRow) {
                return;
            }

            draggingRow.classList.remove('dragging');
            draggingRow = null;
            document.body.classList.remove('testtype-reordering');

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
                    window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder tests.');
                }
            }).catch(function () {
                applyOrder(originalOrder);
                window.alert(reorderBody.dataset.reorderFailed || 'Failed to reorder tests.');
            });
        }

        function findHandleFromEvent(event) {
            return event.target && event.target.closest
                ? event.target.closest('[data-testtype-drag-handle]')
                : null;
        }

        function startFromHandle(handle, pointerId) {
            var row = handle.closest('tr[data-testtype-id]');
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
    });
</script>
@endpush
