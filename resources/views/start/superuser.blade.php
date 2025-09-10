@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="text-center">
    <h1>Welcome, {{ auth()->user()->present()->name() }}</h1>
    <a href="{{ route('home') }}"
       class="btn btn-primary btn-lg btn-block"
       style="max-width:300px;margin:15px auto;">
        <i class="fas fa-chart-bar"></i> Dashboard
    </a>
    <a href="{{ route('hardware.index') }}"
       class="btn btn-primary btn-lg btn-block"
       style="max-width:300px;margin:15px auto;">
        <i class="fas fa-desktop"></i> Hardware
    </a>
    <a href="{{ route('users.index') }}"
       class="btn btn-primary btn-lg btn-block"
       style="max-width:300px;margin:15px auto;">
        <i class="fas fa-users"></i> Users
    </a>
    <a href="{{ route('settings.general.index') }}"
       class="btn btn-primary btn-lg btn-block"
       style="max-width:300px;margin:15px auto;">
        <i class="fas fa-cog"></i> Settings
    </a>
</div>
@stop
