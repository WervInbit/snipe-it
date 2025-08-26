@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 15px;">
        <a href="{{ route('scan') }}" class="btn btn-primary btn-block">Scan QR</a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 15px;">
        <a href="{{ route('hardware.create') }}" class="btn btn-primary btn-block">New Asset</a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 15px;">
        <a href="{{ route('hardware.index') }}" class="btn btn-primary btn-block">Management</a>
    </div>
</div>
@stop
