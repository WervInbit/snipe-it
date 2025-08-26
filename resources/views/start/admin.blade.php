@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    @can('scanning')
        <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
            <a href="{{ route('scan') }}" class="btn btn-primary btn-block">Scan QR</a>
        </div>
    @endcan
    @can('assets.create')
        <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
            <a href="{{ route('hardware.create') }}" class="btn btn-primary btn-block">New Asset</a>
        </div>
    @endcan
    @can('assets.delete')
        <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
            <a href="{{ route('hardware.index') }}" class="btn btn-primary btn-block">Management</a>
        </div>
    @endcan
    <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
        <a href="{{ route('users.index') }}" class="btn btn-primary btn-block">Users</a>
    </div>
</div>
@stop
