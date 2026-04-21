@extends('layouts/default')

@section('title')
    {{ __('Edit Component Storage Location') }}
@parent
@stop

@section('content')
    <form method="POST" action="{{ route('settings.component_storage_locations.update', $componentStorageLocation) }}">
        @include('settings.component_storage_locations._form', [
            'method' => 'PUT',
            'submitLabel' => __('Save Changes'),
        ])
    </form>
@endsection
