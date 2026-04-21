@extends('layouts/default')

@section('title')
    {{ __('Create Component Storage Location') }}
@parent
@stop

@section('content')
    <form method="POST" action="{{ route('settings.component_storage_locations.store') }}">
        @include('settings.component_storage_locations._form', [
            'method' => 'POST',
            'submitLabel' => __('Create Storage Location'),
        ])
    </form>
@endsection
