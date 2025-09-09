@extends('layouts/default')

@section('title')
    {{ trans('general.batch_edit') }}
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <form class="form-horizontal" method="post" action="{{ route('hardware/bulksave') }}" autocomplete="off" role="form">
            @csrf
            @foreach($assets as $asset_id)
                <input type="hidden" name="ids[]" value="{{ $asset_id }}">
            @endforeach
            <div class="box box-default">
                <div class="box-body">
                    @include('partials.forms.edit.category-select', ['translated_name' => trans('general.category'), 'fieldname' => 'category_id', 'category_type' => 'asset'])
                    <div class="form-group {{ $errors->has('status_id') ? ' has-error' : '' }}">
                        <label for="status_id" class="col-md-3 control-label">{{ trans('admin/hardware/form.status') }}</label>
                        <div class="col-md-7">
                            <x-input.select
                                name="status_id"
                                :options="$statuslabel_list"
                                :selected="old('status_id')"
                                style="width: 100%"
                                aria-label="status_id"
                            />
                            {!! $errors->first('status_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>
                    @include('partials.forms.edit.location-select', ['translated_name' => trans('admin/hardware/form.default_location'), 'fieldname' => 'rtd_location_id'])
                    <div class="form-group">
                        <div class="col-md-9 col-md-offset-3">
                            <label class="form-control">
                                <input type="radio" name="update_real_loc" value="1" checked>
                                {{ trans('admin/hardware/form.asset_location_update_default_current') }}
                            </label>
                            <label class="form-control">
                                <input type="radio" name="update_real_loc" value="0">
                                {{ trans('admin/hardware/form.asset_location_update_default') }}
                            </label>
                            <label class="form-control">
                                <input type="radio" name="update_real_loc" value="2">
                                {{ trans('admin/hardware/form.asset_location_update_actual') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-7 col-md-offset-3">
                            <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
