@extends('layouts/default')

@section('title')
{{ __('My Tray') }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.create') }}" class="btn btn-primary">
    {{ __('Register Component') }}
</a>
<a href="{{ route('components.index') }}" class="btn btn-default">
    {{ trans('general.back') }}
</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('Tray Workspace') }}</h3>
            </div>
            <div class="box-body">
                @if ($trayComponents->isEmpty())
                    <p class="text-muted">{{ __('No components are currently assigned to your tray.') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.tag') }}</th>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ __('Source Asset') }}</th>
                                <th>{{ __('Held Duration') }}</th>
                                <th>{{ __('Warning') }}</th>
                                <th>{{ trans('general.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($trayComponents as $component)
                                <tr>
                                    <td><a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a></td>
                                    <td>{{ $component->display_name }}</td>
                                    <td>
                                        @if ($component->sourceAsset)
                                            <a href="{{ route('hardware.show', $component->sourceAsset) }}">{{ $component->sourceAsset->present()->name() }}</a>
                                        @else
                                            <span class="text-muted">{{ trans('general.none') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $component->transfer_age_human ?? trans('general.none') }}</td>
                                    <td><span class="label {{ $component->tray_warning['class'] ?? 'label-default' }}">{{ $component->tray_warning['label'] ?? trans('general.none') }}</span></td>
                                    <td>
                                        <a href="{{ route('components.show', $component) }}#component-install" class="btn btn-xs btn-primary">{{ __('Install') }}</a>
                                        <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <form method="POST" action="{{ route('components.move_to_stock', $component) }}" class="form-inline">
                                                    @csrf
                                                    <div class="form-group" style="width: 100%;">
                                                        <label class="sr-only" for="tray_stock_location_{{ $component->id }}">{{ __('Stock Location') }}</label>
                                                        <select class="form-control input-sm" id="tray_stock_location_{{ $component->id }}" name="storage_location_id" style="width: 60%;" required>
                                                            <option value="">{{ __('Stock') }}</option>
                                                            @foreach ($stockLocations as $location)
                                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-default">{{ __('Move To Stock') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col-md-4">
                                                <form method="POST" action="{{ route('components.flag_needs_verification', $component) }}" class="form-inline">
                                                    @csrf
                                                    <div class="form-group" style="width: 100%;">
                                                        <label class="sr-only" for="tray_verify_location_{{ $component->id }}">{{ __('Verification Location') }}</label>
                                                        <select class="form-control input-sm" id="tray_verify_location_{{ $component->id }}" name="storage_location_id" style="width: 60%;">
                                                            <option value="">{{ __('Keep Current Location') }}</option>
                                                            @foreach ($verificationLocations as $location)
                                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-warning">{{ __('Needs Verification') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col-md-4">
                                                <form method="POST" action="{{ route('components.mark_destruction_pending', $component) }}" class="form-inline">
                                                    @csrf
                                                    <div class="form-group" style="width: 100%;">
                                                        <label class="sr-only" for="tray_destruction_location_{{ $component->id }}">{{ __('Destruction Location') }}</label>
                                                        <select class="form-control input-sm" id="tray_destruction_location_{{ $component->id }}" name="storage_location_id" style="width: 60%;">
                                                            <option value="">{{ __('No Location') }}</option>
                                                            @foreach ($destructionLocations as $location)
                                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-danger">{{ __('Mark Destruction Pending') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
