@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
        <a href="{{ route('home') }}" class="btn btn-primary btn-block">Dashboard</a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
        <a href="{{ route('hardware.index') }}" class="btn btn-primary btn-block">Hardware</a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
        <a href="{{ route('users.index') }}" class="btn btn-primary btn-block">Users</a>
    </div>
    <div class="col-xs-12 col-sm-6 col-md-3" style="margin-bottom: 15px;">
        <a href="{{ route('settings.general.index') }}" class="btn btn-primary btn-block">Settings</a>
    </div>
</div>
@stop
