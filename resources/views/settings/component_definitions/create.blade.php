@extends('layouts/default')

@section('title')
    {{ __('Create Component Definition') }}
@parent
@stop

@section('content')
    <form method="POST" action="{{ route('settings.component_definitions.store') }}">
        @include('settings.component_definitions._form', [
            'method' => 'POST',
            'submitLabel' => __('Create Definition'),
        ])
    </form>
@endsection
