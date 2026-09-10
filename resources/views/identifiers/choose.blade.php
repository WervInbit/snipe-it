@extends('layouts/default')
@section('title', trans('identifiers.existing'))
@section('content')
<div class="box box-default">
    <div class="box-body">
        <p class="alert alert-warning">{{ trans('identifiers.choose') }}</p>
        <p><strong>{{ $data['value'] }}</strong></p>
        <table class="table table-striped">
            <thead><tr><th>{{ trans('general.type') }}</th><th>{{ trans('general.id') }}</th><th>{{ trans('general.name') }}</th><th>{{ trans('general.tag') }}</th><th>{{ trans('admin/hardware/form.serial') }}</th><th></th></tr></thead>
            <tbody>
            @foreach ($records as $entry)
                @php($record = $entry['record'])
                <tr>
                    <td>{{ $entry['type'] === 'asset' ? trans('general.asset') : trans('general.component') }}</td>
                    <td>{{ $record->id }}</td>
                    <td>{{ $entry['type'] === 'asset' ? $record->name : $record->display_name }}</td>
                    <td>{{ $entry['type'] === 'asset' ? $record->asset_tag : $record->component_tag }}</td>
                    <td>{{ $record->serial }}</td>
                    <td><a class="btn btn-primary" href="{{ route('identifiers.choose', array_merge($data, ['selected_type' => $entry['type'], 'selected_id' => $record->id])) }}">{{ trans('general.select') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
