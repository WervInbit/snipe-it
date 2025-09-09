@extends('layouts/default')

@section('title')
{{ trans('general.skus') }}
@parent
@stop

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
      <div class="box-body">
        <table
            data-columns="{{ \App\Presenters\SkuPresenter::dataTableLayout() }}"
            data-cookie-id-table="skusTable"
            data-id-table="skusTable"
            data-side-pagination="server"
            data-sort-order="asc"
            id="skusTable"
            data-url="{{ route('api.skus.index') }}"
            class="table table-striped snipe-table"
            data-export-options='{
              "fileName": "export-skus-{{ date('Y-m-d') }}",
              "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
              }'>
          </table>
      </div>
    </div>
  </div>
</div>
@stop

@section('moar_scripts')
  @include ('partials.bootstrap-table',
      ['exportFile' => 'skus-export',
      'search' => true,
      'columns' => \App\Presenters\SkuPresenter::dataTableLayout()
  ])
@stop
