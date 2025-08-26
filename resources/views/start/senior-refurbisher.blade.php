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
    </div>
</div>
@stop
