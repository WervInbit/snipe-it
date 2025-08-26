@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <a href="{{ route('scan') }}" class="btn btn-primary">Scan</a>
        <a href="{{ route('hardware.create') }}" class="btn btn-primary">New Asset</a>
        <a href="{{ route('hardware.index') }}" class="btn btn-primary">Management</a>
    </div>
</div>
@stop
