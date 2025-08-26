@extends('layouts/default')

@section('title')
    Start
    @parent
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <a href="{{ route('view-assets') }}" class="btn btn-primary">My Assets</a>
    </div>
</div>
@stop
