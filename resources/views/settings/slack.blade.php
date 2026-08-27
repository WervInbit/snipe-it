@extends('layouts/default')

@section('title')
    {{ trans('admin/settings/general.webhook_title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('settings.index') }}" class="btn btn-primary">{{ trans('general.back') }}</a>
@stop

@section('content')
    @livewire('slack-settings-form')
@stop

