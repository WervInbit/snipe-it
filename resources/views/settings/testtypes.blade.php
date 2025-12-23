@extends('layouts/default')

@section('title')
    {{ trans('admin/settings/general.test_settings_title') }}
@parent
@stop

@section('header_right')
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
                                <th>{{ trans('admin/testtypes/general.name') }}</th>
                                <th>{{ trans('admin/testtypes/general.slug') }}</th>
                                <th>{{ trans('admin/testtypes/general.attribute') }}</th>
                                <th>{{ trans('admin/testtypes/general.categories') }}</th>
                                <th>{{ trans('admin/testtypes/general.required') }}</th>
                                <th>{{ trans('admin/testtypes/general.instructions') }}</th>
                                <th>{{ trans('admin/testtypes/general.tooltip') }}</th>
                                <th class="text-right">{{ trans('button.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($testTypes as $type)
                                @php
                                    $isEditContext = old('modal') === 'edit-testtype-' . $type->id . '-modal';
                                    $instructionPreview = $type->instructions ? \Illuminate\Support\Str::limit(strip_tags($type->instructions), 80) : trans('general.none');
                                    $tooltipPreview = $type->tooltip ?: trans('general.none');
                                    $categoryNames = $type->categories->pluck('name')->implode(', ');
                                    $categoryPreview = $categoryNames
                                        ?: ($type->attribute_definition_id ? trans('general.all') : trans('general.none'));
                                @endphp
                                <tr>
                                    <td>{{ $type->name }}</td>
                                    <td class="monospace text-muted">{{ $type->slug }}</td>
                                    <td>{{ optional($type->attributeDefinition)->label ?? trans('general.none') }}</td>
                                    <td>{{ $categoryPreview }}</td>
                                    <td>{!! $type->is_required ? '<i class="fas fa-check text-success"></i>' : '<span class="text-muted">--</span>' !!}</td>
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
                                    <td colspan="8" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('create', \App\Models\TestType::class)
        @php $createContext = old('modal') === 'create-testtype-modal'; @endphp
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
                                <input type="text" class="form-control" id="create-slug" name="slug" value="{{ $createContext ? old('slug') : '' }}">
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
                            <div class="form-group{{ $createContext && $errors->has('category_ids') ? ' has-error' : '' }}">
                                <label for="create-categories">{{ trans('admin/testtypes/general.categories') }}</label>
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
            @php $isEditContext = old('modal') === 'edit-testtype-' . $type->id . '-modal'; @endphp
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
                                    <input type="text"
                                           class="form-control"
                                           id="edit-slug-{{ $type->id }}"
                                           name="slug"
                                           value="{{ $isEditContext ? old('slug', $type->slug) : $type->slug }}">
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
                                <div class="form-group{{ $isEditContext && $errors->has('category_ids') ? ' has-error' : '' }}">
                                    <label for="edit-categories-{{ $type->id }}">{{ trans('admin/testtypes/general.categories') }}</label>
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

@push('scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        function initSelect2($context) {
            $context.find('.js-test-attribute').each(function () {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                var parent = $select.closest('.modal');

                $select.select2({
                    allowClear: true,
                    placeholder: function () {
                        return $select.data('placeholder');
                    },
                    dropdownParent: parent.length ? parent : $(document.body)
                });
            });

            $context.find('.js-test-category').each(function () {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                var parent = $select.closest('.modal');

                $select.select2({
                    placeholder: function () {
                        return $select.data('placeholder');
                    },
                    dropdownParent: parent.length ? parent : $(document.body)
                });
            });
        }

        initSelect2($(document));

        $('.modal').on('shown.bs.modal', function () {
            initSelect2($(this));
        });

        var modalToOpen = @json(old('modal'));
        if (modalToOpen) {
            $('#' + modalToOpen).modal('show');
        }
    });
</script>
@endpush
