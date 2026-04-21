@extends('layouts/default')

@section('title')
    {{ __('Edit Component Definition') }}
@parent
@stop

@section('content')
    <form method="POST" action="{{ route('settings.component_definitions.update', $componentDefinition) }}">
        @include('settings.component_definitions._form', [
            'method' => 'PUT',
            'submitLabel' => __('Save Changes'),
        ])
    </form>
@endsection
