@extends('layouts/default')

@section('title')
{{ $sku->name }}
@parent
@stop

@section('header_right')
    @can('update', \App\Models\Sku::class)
        <a href="{{ route('skus.edit', $sku->id) }}" class="btn btn-primary pull-right">{{ trans('button.edit') }}</a>
    @endcan
@stop

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="box box-default">
            <div class="box-body">
                <p><strong>{{ trans('general.asset_model') }}:</strong>
                    @if($sku->model)
                        <a href="{{ route('models.show', $sku->model->id) }}">{{ $sku->model->name }}</a>
                    @endif
                </p>
            </div>
        </div>

        <div class="box box-default">
            <div class="box-body">
                <table
                        data-columns="{{ \App\Presenters\AssetPresenter::dataTableLayout() }}"
                        data-cookie-id-table="assetListingTable"
                        data-id-table="assetListingTable"
                        data-side-pagination="server"
                        data-sort-order="asc"
                        id="assetListingTable"
                        data-url="{{ route('api.assets.index', ['sku_id'=> $sku->id]) }}"
                        class="table table-striped snipe-table"
                        data-export-options='{
                "fileName": "export-sku-{{ str_slug($sku->name) }}-assets-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                }'>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table', [
        'exportFile' => 'sku-export',
        'search' => true,
        'columns' => \App\Presenters\AssetPresenter::dataTableLayout()
    ])
@stop
