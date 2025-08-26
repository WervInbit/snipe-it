@extends('layouts/default')

@section('title')
    {{ trans('tests.repeat') }}
@endsection

@section('content')
<p>{{ trans('tests.repeat_confirm') }}</p>
<form method="POST" action="{{ route('asset-tests.repeat', [$asset->id, $test->id]) }}">
    @csrf
    <button type="submit" class="btn btn-warning">{{ trans('tests.repeat') }}</button>
</form>
@endsection
