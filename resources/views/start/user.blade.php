@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="text-center">
    <h1>Welcome, {{ auth()->user()->present()->name() }}</h1>
    <a href="{{ route('view-assets') }}"
       class="btn btn-primary btn-lg btn-block"
       style="max-width:300px;margin:15px auto;">
        <i class="fas fa-box"></i> My Assets
    </a>
</div>
@stop
