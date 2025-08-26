@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 15px;">
        <a href="{{ route('view-assets') }}" class="btn btn-primary btn-block">My Assets</a>
    </div>
</div>
@stop
