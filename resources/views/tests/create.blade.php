@extends('layouts/default')

@section('title')
    {{ trans('tests.test') }}
@endsection

@section('content')
@php($tooltips = \App\Models\Setting::getSettings()->test_tooltips ?? [])
<form method="POST" action="{{ isset($test->id) ? route('asset-tests.update', [$asset->id, $test->id]) : route('asset-tests.store', $asset->id) }}">
    @csrf
    @if(isset($test->id))
        @method('PUT')
    @endif
    <div class="form-group">
        <label for="performed_at">{{ trans('general.date') }}</label>
        <input type="date" class="form-control" name="performed_at" value="{{ old('performed_at', optional($test->performed_at)->format('Y-m-d')) }}" data-toggle="tooltip" title="{{ $tooltips['performed_at'] ?? '' }}">
    </div>
    <div class="form-group">
        <label for="status">{{ trans('general.status') }}</label>
        <input type="text" class="form-control" name="status" value="{{ old('status', $test->status) }}" data-toggle="tooltip" title="{{ $tooltips['status'] ?? '' }}">
    </div>
    <div class="form-group" data-toggle="tooltip" title="{{ $tooltips['needs_cleaning'] ?? '' }}">
        <label>
            <input type="checkbox" name="needs_cleaning" value="1" {{ old('needs_cleaning', $test->needs_cleaning) ? 'checked' : '' }}>
            {{ trans('tests.needs_cleaning') }}
        </label>
    </div>
    <div class="form-group">
        <label for="notes">{{ trans('general.notes') }}</label>
        <textarea class="form-control" name="notes" data-toggle="tooltip" title="{{ $tooltips['notes'] ?? '' }}">{{ old('notes', $test->notes) }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
</form>
@endsection
