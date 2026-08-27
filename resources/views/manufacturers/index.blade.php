@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/manufacturers/table.asset_manufacturers') }} 
@parent
@stop

{{-- Page content --}}
@section('content')

  <div class="row">
    <div class="col-md-12">

      <div class="box box-default">
        <div class="box-body">

            <table
              data-columns="{{ \App\Presenters\ManufacturerPresenter::dataTableLayout() }}"
              data-cookie-id-table="manufacturersTable"
              data-id-table="manufacturersTable"
              data-side-pagination="server"
              data-sort-order="asc"
              id="manufacturersTable"
              data-buttons="manufacturerButtons"
              class="table table-striped snipe-table"
              data-url="{{route('api.manufacturers.index', ['deleted' => (request('deleted')=='true') ? 'true' : 'false' ]) }}"
              data-export-options='{
                "fileName": "export-manufacturers-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                }'>
            </table>

        </div><!-- /.box-body -->
      </div><!-- /.box -->
    </div>
  </div>
@stop

@section('moar_scripts')


  @include ('partials.bootstrap-table')
@stop
