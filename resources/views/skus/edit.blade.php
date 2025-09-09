@extends('layouts/edit-form', [
    'createText' => trans('admin/skus/table.create'),
    'updateText' => trans('admin/skus/table.update'),
    'helpText' => trans('admin/skus/general.about_skus_text'),
    'formAction' => (isset($item->id)) ? route('skus.update', ['sku' => $item->id]) : route('skus.store'),
])

@section('inputFields')
@include('partials.forms.edit.name', ['translated_name' => trans('admin/skus/table.name'), 'required' => 'true'])
@include('partials.forms.edit.model-select', ['translated_name' => trans('general.asset_model'), 'fieldname' => 'model_id', 'required' => 'true'])
@stop
