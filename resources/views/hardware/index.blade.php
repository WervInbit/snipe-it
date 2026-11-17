@extends('layouts/default')

@section('title0')

  @if ((Request::get('company_id')) && ($company))
    {{ $company->name }}
  @endif



@if (Request::get('status'))
  @if (Request::get('status')=='Pending')
    {{ trans('general.pending') }}
  @elseif (Request::get('status')=='RTD')
    {{ trans('general.ready_to_deploy') }}
  @elseif (Request::get('status')=='Deployed')
    {{ trans('general.deployed') }}
  @elseif (Request::get('status')=='Undeployable')
    {{ trans('general.undeployable') }}
  @elseif (Request::get('status')=='Deployable')
    {{ trans('general.deployed') }}
  @elseif (Request::get('status')=='Requestable')
    {{ trans('admin/hardware/general.requestable') }}
  @elseif (Request::get('status')=='Archived')
    {{ trans('general.archived') }}
  @elseif (Request::get('status')=='Deleted')
    {{ ucfirst(trans('general.deleted')) }}
  @elseif (Request::get('status')=='byod')
    {{ strtoupper(trans('general.byod')) }}
  @endif
@else
{{ trans('general.all') }}
@endif
{{ trans('general.assets') }}

  @if (Request::has('order_number'))
    : Order #{{ strval(Request::get('order_number')) }}
  @endif
@stop

{{-- Page title --}}
@section('title')
@yield('title0')  @parent
@stop


{{-- Page content --}}
@section('content')
@php
    $assetsTableScope = request()->has('status') ? e(request()->input('status')) : '';
    $assetsTableVersion = optional($snipeSettings->updated_at)->timestamp;
    $assetsTableId = $assetsTableScope.'assetsListingTable'.($assetsTableVersion ? '_'.$assetsTableVersion : '');
@endphp

<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-body">
       
          <div class="row">
            <div class="col-md-12">

                <form method="GET" action="{{ route('hardware.index') }}" class="form-inline" style="margin-bottom:10px">
                    @foreach (request()->except('legacy_evidence') as $key => $value)
                        @if (is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="legacy_evidence_filter">{{ trans('tests.legacy_filter_label') }}</label>
                    <select class="form-control input-sm" name="legacy_evidence" id="legacy_evidence_filter" onchange="this.form.submit()">
                        <option value="">{{ trans('tests.legacy_filter_all') }}</option>
                        <option value="active" @selected(request('legacy_evidence') === 'active')>{{ trans('tests.legacy_filter_active') }}</option>
                        <option value="none" @selected(request('legacy_evidence') === 'none')>{{ trans('tests.legacy_filter_none') }}</option>
                        <option value="superseded" @selected(request('legacy_evidence') === 'superseded')>{{ trans('tests.legacy_filter_superseded') }}</option>
                        <option value="revoked" @selected(request('legacy_evidence') === 'revoked')>{{ trans('tests.legacy_filter_revoked') }}</option>
                    </select>
                    <noscript><button class="btn btn-default btn-sm" type="submit">{{ __('Apply') }}</button></noscript>
                </form>

                @include('partials.asset-bulk-actions', ['status' => Request::get('status')])
                   
              <table
                data-columns="{{ \App\Presenters\AssetPresenter::dataTableLayout() }}"
                data-cookie-id-table="{{ $assetsTableId }}"
                data-id-table="{{ $assetsTableId }}"
                data-cookie="false"
                data-addrbar="false"
                data-search-text="{{ e(request()->query('search', '')) }}"
                data-side-pagination="server"
                data-show-footer="true"
                data-sort-order="asc"
                data-sort-name="name"
                data-toolbar="#assetsBulkEditToolbar"
                data-bulk-button-id="#bulkAssetEditButton"
                data-bulk-form-id="#assetsBulkForm"
                data-buttons="assetButtons"
                id="{{ $assetsTableId }}"
                class="table table-striped snipe-table"
                data-url="{{ route('api.assets.index',
                    array('status' => e(Request::get('status')),
                    'order_number'=>e(strval(Request::get('order_number'))),
                    'company_id'=>e(Request::get('company_id')),
                    'status_id'=>e(Request::get('status_id')),
                    'legacy_evidence'=>e(Request::get('legacy_evidence')))) }}"
                data-export-options='{
                "fileName": "export{{ (Request::has('status')) ? '-'.str_slug(Request::get('status')) : '' }}-assets-{{ date('Y-m-d') }}",
                "ignoreColumn": ["actions","image","change","checkbox","icon"]
                }'>
              </table>

            </div><!-- /.col -->
          </div><!-- /.row -->
        
      </div><!-- ./box-body -->
    </div><!-- /.box -->
  </div>
</div>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table')

@stop
