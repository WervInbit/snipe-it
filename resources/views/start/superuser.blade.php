@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <a href="{{ route('home') }}" class="btn btn-primary">Dashboard</a>
        <a href="{{ route('hardware.index') }}" class="btn btn-primary">Hardware</a>
        <a href="{{ route('users.index') }}" class="btn btn-primary">Users</a>
        <a href="{{ route('settings.general.index') }}" class="btn btn-primary">Settings</a>
    </div>
</div>
@stop
