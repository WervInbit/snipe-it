@extends('layouts/default')

@section('title')
    {{ trans('tests.tests') }}
@endsection

@section('content')
<div class="mb-3 text-right">
    <a href="{{ route('asset-tests.create', $asset->id) }}" class="btn btn-primary">{{ trans('button.add') }}</a>
</div>
<table class="table table-striped">
    <thead>
        <tr>
            <th>{{ trans('general.date') }}</th>
            <th>{{ trans('general.status') }}</th>
            <th>{{ trans('tests.needs_cleaning') }}</th>
            <th>{{ trans('general.notes') }}</th>
            <th>{{ trans('table.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tests as $test)
            <tr>
                <td>{{ $test->performed_at }}</td>
                <td>{{ $test->status }}</td>
                <td>
                    @if ($test->needs_cleaning)
                        <span class="badge badge-warning">{{ trans('tests.needs_cleaning') }}</span>
                    @endif
                </td>
                <td>{{ $test->notes }}</td>
                <td>
                    <a href="{{ route('asset-tests.edit', [$asset->id, $test->id]) }}" class="btn btn-default btn-sm">{{ trans('button.edit') }}</a>
                    <form method="POST" action="{{ route('asset-tests.destroy', [$asset->id, $test->id]) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                    </form>
                    <a href="{{ route('asset-tests.repeat.form', [$asset->id, $test->id]) }}" class="btn btn-warning btn-sm">{{ trans('tests.repeat') }}</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
