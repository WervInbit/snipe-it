@extends('layouts/default')

@section('title')
{{ trans('general.my_tray') }}
@parent
@stop

@section('header_right')
<a href="{{ route('components.create') }}" class="btn btn-primary">
    {{ trans('general.register_component') }}
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
                <h3 class="box-title">{{ trans('general.tray_workspace') }}</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">{{ trans('general.tray_workspace_help') }}</p>

                @if ($trayComponents->isEmpty())
                    <p class="text-muted">{{ trans('general.no_components_in_tray') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.tag') }}</th>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.source_asset') }}</th>
                                <th>{{ trans('general.held_duration') }}</th>
                                <th>{{ trans('general.warning_label') }}</th>
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
                                        <a href="{{ route('components.install.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-primary">{{ trans('general.install') }}</a>
                                        <a href="{{ route('components.move_to_stock.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-default">{{ trans('general.to_storage') }}</a>
                                        <a href="{{ route('components.flag_needs_verification.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-warning">{{ trans('general.needs_verification') }}</a>
                                        @can('destroy', $component)
                                            <a href="{{ route('components.mark_destruction_pending.create', [$component, 'return_to' => $returnTo]) }}" class="btn btn-xs btn-danger">{{ trans('general.mark_destruction_pending') }}</a>
                                        @endcan
                                        <a href="{{ route('components.show', $component) }}" class="btn btn-xs btn-default">{{ trans('general.open') }}</a>
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
