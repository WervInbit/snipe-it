@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="text-center">
    <h1>Welcome, {{ auth()->user()->present()->name() }}</h1>
    @can('scanning')
        <a href="{{ route('scan') }}"
           class="btn btn-primary btn-lg btn-block"
           style="max-width:300px;margin:15px auto;">
            <i class="fas fa-camera"></i> Scan QR
        </a>
    @endcan
    @can('assets.create')
        <a href="{{ route('hardware.create') }}"
           class="btn btn-primary btn-lg btn-block"
           style="max-width:300px;margin:15px auto;">
            <i class="fas fa-plus"></i> New Asset
        </a>
    @endcan
</div>
@stop
