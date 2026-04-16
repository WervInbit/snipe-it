@extends('layouts/edit-form', [
    'createText' => trans('admin/models/table.create') ,
    'updateText' => trans('admin/models/table.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('admin/models/general.about_models_text'),
    'formAction' => (isset($item->id)) ? route('models.update', ['model' => $item->id]) : route('models.store'),
])

{{-- Page content --}}
@section('inputFields')
@include ('partials.forms.edit.name', ['translated_name' => trans('admin/models/table.name'), 'required' => 'true'])
@include ('partials.forms.edit.category-select', ['translated_name' => trans('admin/categories/general.category_name'), 'fieldname' => 'category_id', 'required' => 'true', 'category_type' => 'asset'])
@include ('partials.forms.edit.manufacturer-select', ['translated_name' => trans('general.manufacturer'), 'fieldname' => 'manufacturer_id'])
@include ('partials.forms.edit.depreciation')
{{-- Deprecated legacy model fields (`eol`, `min_amt`, `requestable`) are intentionally hidden in the refurb UI.
     They remain in the data model for backward compatibility and future removal planning. --}}

<!-- Custom Fieldset -->
<!-- If $item->id is null we are cloning the model and we need the $model_id variable -->
@livewire('custom-field-set-default-values-for-model', ["model_id" => $item->id ?? $model_id ?? null])

@include ('partials.forms.edit.notes')
@include ('partials.forms.edit.image-upload', ['image_path' => app('models_upload_path')])


@stop

