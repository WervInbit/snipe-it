@extends('layouts/default')

{{-- Page title --}}
@php($primaryModelNumber = $model->displayPrimaryModelNumber())

@section('title')
    {{ $model->name }}
    {{ $primaryModelNumber ? '(' . $primaryModelNumber . ')' : '' }}
@parent
@stop

@section('header_right')
    @can('update', \App\Models\AssetModel::class)
        <div class="btn-group pull-right">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">{{ trans('button.actions') }}
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                @if ($model->deleted_at=='')
                    <li><a href="{{ route('models.edit', $model->id) }}">{{ trans('admin/models/table.edit') }}</a></li>
                    <li><a href="{{ route('models.spec.edit', $model) }}">{{ __('Edit Specification') }}</a></li>
                    <li><a href="{{ route('models.clone.create', $model->id) }}">{{ trans('admin/models/table.clone') }}</a></li>
                    <li><a href="{{ route('hardware.create', ['model_id' => $model->id]) }}">{{ trans('admin/hardware/form.create') }}</a></li>
                @else
                    <li><a href="{{ route('models.restore.store', $model->id) }}">{{ trans('admin/models/general.restore') }}</a></li>
                @endif
            </ul>
        </div>
    @endcan
@stop

{{-- Page content --}}
@section('content')


<div class="row">

    @if ($model->deleted_at!='')
        <div class="col-md-12">
            <div class="callout callout-warning">
                <x-icon type="warning" />
                {{ trans('admin/models/general.deleted') }}
            </div>
        </div>
    @endif

    <div class="col-md-9">

        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#assets" data-toggle="tab">

                        <span class="hidden-lg hidden-md">
                          <i class="fas fa-barcode fa-2x"></i>
                        </span>
                        <span class="hidden-xs hidden-sm">
                            {{ trans('general.assets') }}
                            {!! ($model->assets()->AssetsForShow()->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($model->assets()->AssetsForShow()->count()).'</span>' : '' !!}
                        </span>
                    </a>
                </li>

                <li>
                    <a href="#files" data-toggle="tab">

                        <span class="hidden-lg hidden-md">
                          <i class="fas fa-barcode fa-2x"></i>
                        </span>
                        <span class="hidden-xs hidden-sm">
                            {{ trans('general.files') }}
                            {!! ($model->uploads->count() > 0 ) ? '<span class="badge badge-secondary">'.number_format($model->uploads->count()).'</span>' : '' !!}
                          </span>
                    </a>
                </li>
                <li class="pull-right">
                    <a href="#" data-toggle="modal" data-target="#uploadFileModal">
                        <x-icon type="paperclip" />
                        {{ trans('button.upload') }}
                    </a>
                </li>

            </ul>

            <div class="tab-content">
                <div class="tab-pane fade in active" id="assets">

                    @include('partials.asset-bulk-actions')

                    <table
                            data-columns="{{ \App\Presenters\AssetPresenter::dataTableLayout() }}"
                            data-cookie-id-table="assetListingTable"
                            data-id-table="assetListingTable"
                            data-side-pagination="server"
                            data-toolbar="#assetsBulkEditToolbar"
                            data-bulk-button-id="#bulkAssetEditButton"
                            data-bulk-form-id="#assetsBulkForm"
                            data-sort-order="asc"
                            id="assetListingTable"
                            data-url="{{ route('api.assets.index',['model_id'=> $model->id]) }}"
                            class="table table-striped snipe-table"
                            data-export-options='{
                "fileName": "export-models-{{ str_slug($model->name) }}-assets-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                }'>
                    </table>
                </div> <!-- /.tab-pane assets -->

                <div class="tab-pane fade" id="files">

                    <div class="row">
                        <div class="col-md-12">

                            <x-filestable object_type="models" :object="$model" />

                        </div> <!-- /.col-md-12 -->
                    </div> <!-- /.row -->

                </div>


            </div> <!-- /.tab-content -->
        </div>  <!-- /.nav-tabs-custom -->
    </div><!-- /. col-md-12 -->

    <div class="col-md-3">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="box-heading">
                            <h2 class="box-title"> {{ trans('general.moreinfo') }}:</h2>
                        </div>
                    </div><!-- /.box-header -->
                    <div class="box-body">



                @if ($model->image)
                    <img src="{{ Storage::disk('public')->url(app('models_upload_path').e($model->image)) }}" class="img-responsive"></li>
                @endif


                <ul class="list-unstyled" style="line-height: 25px;">
                    @if ($model->category)
                        <li>
                            <strong>{{ trans('general.category') }}</strong>:
                            <a href="{{ route('categories.show', $model->category->id) }}">{{ $model->category->name }}</a>
                        </li>
                    @endif
                    @if ($model->deleted_at)
                        <li>
                            <strong>
                                <span class="text-danger">
                                {{ trans('general.deleted') }}:
                                {{ Helper::getFormattedDateObject($model->deleted_at, 'datetime', false) }}
                                </span>
                            </strong>

                        </li>
                    @endif

                    @if ($model->min_amt)
                        <li>
                            <strong>{{ trans('general.min_amt') }}</strong>:
                           {{$model->min_amt }}
                        </li>
                    @endif

                    @if ($model->manufacturer)
                        <li>
                            <strong>{{ trans('general.manufacturer') }}</strong>:
                            @can('view', \App\Models\Manufacturer::class)
                                <a href="{{ route('manufacturers.show', $model->manufacturer->id) }}">
                                    {{ $model->manufacturer->name }}
                                </a>
                            @else
                                {{ $model->manufacturer->name }}
                            @endcan
                        </li>

                        @if ($model->manufacturer->url)
                            <li>
                                <i class="fas fa-globe-americas"></i> <a href="{{ $model->manufacturer->url }}">{{ $model->manufacturer->url }}</a>
                            </li>
                        @endif

                        @if ($model->manufacturer->support_url)
                            <li>
                                <x-icon type="more-info" /> <a href="{{ $model->manufacturer->support_url }}">{{ $model->manufacturer->support_url }}</a>
                            </li>
                        @endif

                        @if ($model->manufacturer->support_phone)
                            <li>
                                <i class="fas fa-phone"></i>
                                <a href="tel:{{ $model->manufacturer->support_phone }}">{{ $model->manufacturer->support_phone }}</a>

                            </li>
                        @endif

                        @if ($model->manufacturer->support_email)
                            <li>
                                <i class="far fa-envelope"></i> <a href="mailto:{{ $model->manufacturer->support_email }}">{{ $model->manufacturer->support_email }}</a>
                            </li>
                        @endif
                    @endif
                    @if ($primaryModelNumber)
                        <li>
                            <strong>{{ trans('general.model_no') }}</strong>:
                            {{ $primaryModelNumber }}
                        </li>
                    @endif

                    @if ($model->depreciation)
                        <li>
                            <strong>{{ trans('general.depreciation') }}</strong>:
                            {{ $model->depreciation->name }} ({{ $model->depreciation->months.' '.trans('general.months')}})
                        </li>
                    @endif

                    @if ($model->eol)
                        <li>
                            <strong>{{ trans('general.eol') }}</strong>:
                            {{ $model->eol .' '. trans('general.months') }}
                        </li>
                    @endif

                    @if ($model->fieldset)
                        <li>
                            <strong>{{ trans('admin/models/general.fieldset') }}</strong>:
                            <a href="{{ route('fieldsets.show', $model->fieldset->id) }}">{{ $model->fieldset->name }}</a>
                        </li>
                    @endif

                    @if ($model->notes)
                        <li>
                            <strong>{{ trans('general.notes') }}</strong>:
                            {!! nl2br(Helper::parseEscapedMarkedownInline($model->notes)) !!}
                        </li>
                    @endif

                        @if ($model->created_at)
                            <li>
                                <strong>{{ trans('general.created_at') }}</strong>:
                                {{ Helper::getFormattedDateObject($model->created_at, 'datetime', false) }}
                            </li>
                        @endif

                        @if ($model->adminuser)
                            <li>
                                <strong>{{ trans('general.created_by') }}</strong>:
                                {{ $model->adminuser->present()->name() }}
                            </li>
                        @endif


                </ul>
                </div>
        </div>
        </div>

        @can('update', $model)
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="box-heading">
                            <h2 class="box-title">{{ __('Model Numbers') }}</h2>
                        </div>
                    </div>
                    <div class="box-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="list-unstyled" style="margin-bottom: 0;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if($model->modelNumbers->isEmpty())
                            <p class="text-muted">{{ __('No model numbers have been configured yet.') }}</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Code') }}</th>
                                            <th>{{ __('Label') }}</th>
                                            <th>{{ __('Assets') }}</th>
                                            <th class="text-right">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($model->modelNumbers as $number)
                                            <tr>
                                                <td class="col-sm-3">
                                                    <form id="update-model-number-{{ $number->id }}" method="POST" action="{{ route('models.numbers.update', [$model, $number]) }}">
                                                        @csrf
                                                        @method('PUT')
                                                    </form>
                                                    <input type="text" name="code" value="{{ old('code', $number->code) }}" class="form-control input-sm" form="update-model-number-{{ $number->id }}" required>
                                                </td>
                                                <td class="col-sm-3">
                                                    <input type="text" name="label" value="{{ old('label', $number->label) }}" class="form-control input-sm" form="update-model-number-{{ $number->id }}">
                                                </td>
                                                <td class="col-sm-2">
                                                    <span class="label label-default">{{ $number->assets_count }}</span>
                                                </td>
                                                <td class="col-sm-4 text-right">
                                                    <div class="btn-group" role="group">
                                                        <button type="submit" class="btn btn-primary btn-sm" form="update-model-number-{{ $number->id }}">{{ __('Update') }}</button>
                                                        @if($model->primary_model_number_id !== $number->id)
                                                            <button type="submit" name="make_primary" value="1" class="btn btn-default btn-sm" form="update-model-number-{{ $number->id }}">{{ __('Make Primary') }}</button>
                                                        @else
                                                            <span class="btn btn-success btn-sm" disabled>{{ __('Primary') }}</span>
                                                        @endif
                                                    </div>
                                                    @if($model->primary_model_number_id !== $number->id && $number->assets_count === 0)
                                                        <form method="POST" action="{{ route('models.numbers.destroy', [$model, $number]) }}" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('Are you sure you want to delete this model number?') }}');">{{ __('Delete') }}</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="box-footer">
                        <form method="POST" action="{{ route('models.numbers.store', $model) }}" class="form-inline">
                            @csrf
                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="sr-only" for="new_model_number_code">{{ __('Code') }}</label>
                                    <input id="new_model_number_code" type="text" name="code" class="form-control input-sm" placeholder="{{ __('Code') }}" required>
                                </div>
                                <div class="col-sm-4">
                                    <label class="sr-only" for="new_model_number_label">{{ __('Label') }}</label>
                                    <input id="new_model_number_label" type="text" name="label" class="form-control input-sm" placeholder="{{ __('Label (optional)') }}">
                                </div>
                                <div class="col-sm-2">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="make_primary" value="1"> {{ __('Make primary') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-2 text-right">
                                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Add') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @if(isset($specAttributes))
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-header with-border">
                        <div class="box-heading">
                            <h2 class="box-title">{{ __('Specification') }}</h2>
                        </div>
                    </div>
                    <div class="box-body">
                        @if($specAttributes->isEmpty())
                            <p class="text-muted">{{ __('No attribute definitions are scoped to this model.') }}</p>
                        @else
                            <table class="table table-condensed">
                                <tbody>
                                @foreach($specAttributes as $attribute)
                                    @php($definition = $attribute->definition)
                                    <tr>
                                        <th>
                                            {{ $definition->label }}
                                            @if($definition->unit)
                                                <span class="text-muted">({{ $definition->unit }})</span>
                                            @endif
                                            @if($definition->required_for_category)
                                                <span class="label label-default">{{ __('Required') }}</span>
                                            @endif
                                            @if($definition->needs_test)
                                                <span class="label label-info">{{ __('Tested') }}</span>
                                            @endif
                                        </th>
                                        <td>{{ $attribute->formattedValue() ?? __('Not specified') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    @can('update', $model)
                        <div class="box-footer">
                            <a href="{{ route('models.spec.edit', $model) }}" class="btn btn-default btn-block">{{ __('Edit Specification') }}</a>
                        </div>
                    @endcan
                </div>
            </div>
        @endif
            @can('update', \App\Models\AssetModel::class)
            <div class="col-md-12" style="padding-bottom: 5px;">
                <a href="{{ ($model->deleted_at=='') ? route('models.edit', $model->id) : '#' }}" style="width: 100%;" class="btn btn-sm btn-warning btn-social hidden-print{{ ($model->deleted_at!='') ? ' disabled' : '' }}">
                    <x-icon type="edit" />
                    {{ trans('admin/models/table.edit') }}
                </a>
            </div>
            @endcan

            @can('create', \App\Models\AssetModel::class)
            <div class="col-md-12" style="padding-bottom: 5px;">
                <a href="{{ route('models.clone.create', $model->id) }}" style="width: 100%;" class="btn btn-sm btn-info btn-social hidden-print">
                    <x-icon type="clone" />
                    {{ trans('admin/models/table.clone') }}
                </a>
            </div>
            @endcan

            @can('delete', \App\Models\AssetModel::class)
                <div class="col-md-12" style="padding-top: 10px;">

                    @if ($model->deleted_at!='')
                        <form method="POST" action="{{ route('models.restore.store', $model->id) }}">
                            @csrf
                            <button style="width: 100%;" class="btn btn-sm btn-warning btn-social hidden-print">
                                <x-icon type="restore" />
                                {{ trans('button.restore') }}
                            </button>
                        </form>
                    @elseif ($model->assets()->count() > 0)
                        <button class="btn btn-block btn-sm btn-danger btn-social hidden-print disabled" data-tooltip="true"  data-placement="top" data-title="{{ trans('general.cannot_be_deleted') }}">
                            <x-icon type="delete" />
                            {{ trans('general.delete') }}
                        </button>
                    @else
                        <button class="btn btn-block btn-sm btn-danger btn-social delete-asset" data-toggle="modal" title="{{ trans('general.delete_what', ['item'=> trans('general.asset_model')]) }}" data-content="{{ trans('general.sure_to_delete_var', ['item' => $model->name]) }}" data-target="#dataConfirmModal" data-tooltip="true" data-icon="fa fa-trash" data-placement="top" data-title="{{ trans('general.delete_what', ['item'=> trans('general.asset_model')]) }}" onClick="return false;">
                            <x-icon type="delete" />
                            {{ trans('general.delete') }}
                        </button>
                </div>
                @endif
           @endcan

        </div>
</div> <!-- /.row -->

@can('update', \App\Models\AssetModel::class)
    @include ('modals.upload-file', ['item_type' => 'models', 'item_id' => $model->id])
@endcan
@stop

@section('moar_scripts')

    @include ('partials.bootstrap-table', ['exportFile' => 'manufacturer' . $model->name . '-export', 'search' => false])

@stop
