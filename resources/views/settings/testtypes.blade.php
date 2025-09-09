@extends('layouts/default')

@section('title')
    {{ trans('admin/settings/general.test_settings_title') }}
@parent
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ trans('general.name') }}</th>
                            <th>Slug</th>
                            <th>{{ trans('general.category') }}</th>
                            <th>{{ trans('admin/testtypes/general.tooltip') }}</th>
                            <th>{{ trans('button.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testTypes as $type)
                            <tr>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->slug }}</td>
                                <td>{{ $type->category }}</td>
                                <td>
                                    <form method="POST" action="{{ route('settings.testtypes.update', $type) }}" class="form-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="tooltip" value="{{ $type->tooltip }}" class="form-control"/>
                                </td>
                                <td>
                                        <button class="btn btn-primary" type="submit">{{ trans('button.save') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
