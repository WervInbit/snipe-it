@extends('layouts/default')

@section('title')
    {{ __('Edit Test Results') }}
@endsection

@section('content')
<form method="POST" action="{{ route('test-results.update', [$asset->id, $testRun->id]) }}">
    @csrf
    @method('PUT')
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('Test') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Notes') }}</th>
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
                        <select name="status[{{ $result->id }}]" class="form-control" data-toggle="tooltip" title="{{ __('Set status') }}">
                            <option value="pass" @selected($result->status=='pass')>{{ __('Pass') }}</option>
                            <option value="fail" @selected($result->status=='fail')>{{ __('Fail') }}</option>
                            <option value="pending" @selected($result->status=='pending')>{{ __('Pending') }}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="notes[{{ $result->id }}]" value="{{ $result->notes }}" class="form-control" data-toggle="tooltip" title="{{ __('Add notes') }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
</form>
@endsection
