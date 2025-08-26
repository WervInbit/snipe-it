@extends('layouts/default')

@section('title')
    {{ trans('tests.edit_test_results') }}
@endsection

@section('content')
<form method="POST" action="{{ route('test-results.update', [$asset->id, $testRun->id]) }}">
    @csrf
    @method('PUT')
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ trans('tests.test') }}</th>
                <th>{{ trans('general.status') }}</th>
                <th>{{ trans('general.notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($testRun->results as $result)
                <tr>
                    <td>
                        {{ $result->type->name }}
                        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{ $result->type->description }}"></i>
                    </td>
                    <td>
                        <select name="status[{{ $result->id }}]" class="form-control" data-toggle="tooltip" title="{{ trans('tests.set_status') }}">
                            <option value="pass" @selected($result->status=='pass')>{{ trans('tests.pass') }}</option>
                            <option value="fail" @selected($result->status=='fail')>{{ trans('tests.fail') }}</option>
                            <option value="pending" @selected($result->status=='pending')>{{ trans('tests.pending') }}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="notes[{{ $result->id }}]" value="{{ $result->notes }}" class="form-control" data-toggle="tooltip" title="{{ trans('tests.add_notes') }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
</form>
@endsection
