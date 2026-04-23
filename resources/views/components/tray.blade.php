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
                <p class="text-muted">{{ __('Review what is currently in your tray, then launch the next workflow from the row actions.') }}</p>

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
                                @php($returnTo = route('components.tray'))
                                <tr>
                                    <td><a href="{{ route('components.show', $component) }}">{{ $component->component_tag }}</a></td>
                                    <td>
                                        {{ $component->display_name }}
                                        @if ($component->componentDefinition)
                                            <div class="text-muted small">
                                                {{ $component->componentDefinition->category?->name ?: trans('general.none') }}
                                                @if ($component->componentDefinition->manufacturer)
                                                    | {{ $component->componentDefinition->manufacturer->name }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($component->sourceAsset)
                                            <a href="{{ route('hardware.show', $component->sourceAsset) }}">{{ $component->sourceAsset->present()->name() }}</a>
                                        @else
                                            <span class="text-muted">{{ trans('general.none') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $component->transfer_age_human ?? trans('general.none') }}</td>
                                    <td><span class="label {{ $component->tray_warning['class'] ?? 'label-default' }}">{{ $component->tray_warning['label'] ?? trans('general.none') }}</span></td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('components.install.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-primary">{{ __('Install') }}</a>
                                        <a href="{{ route('components.move_to_stock.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-default">{{ __('To Storage') }}</a>
                                        <a href="{{ route('components.flag_needs_verification.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-warning">{{ __('Needs Verification') }}</a>
                                        <a href="{{ route('components.mark_destruction_pending.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-danger">{{ __('Mark Destruction Pending') }}</a>
                                        <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ __('Open') }}</a>
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
