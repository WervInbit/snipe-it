@extends('layouts/default')

@section('title')
    {{ __('Component Storage Locations') }}
@parent
@stop

@section('header_right')
    <a href="{{ route('settings.component_storage_locations.create') }}" class="btn btn-primary" style="margin-right: 10px;">{{ __('Create New') }}</a>
    <form method="GET" class="form-inline" role="search" style="display:inline-block;">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="{{ __('Search storage...') }}" value="{{ $search }}">
            <span class="input-group-btn">
                <button type="submit" class="btn btn-default">{{ __('Search') }}</button>
            </span>
        </div>
    </form>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            @include('partials.notifications')
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Component Storage Locations') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Site Location') }}</th>
                                <th>{{ __('Components') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storageLocations as $location)
                                <tr>
                                    <td>{{ $location->name }}</td>
                                    <td class="monospace">{{ $location->code ?: __('-') }}</td>
                                    <td>{{ ucfirst($location->type) }}</td>
                                    <td>{{ $location->siteLocation?->name ?: __('-') }}</td>
                                    <td><span class="label label-default">{{ $location->component_instances_count }}</span></td>
                                    <td>
                                        @if ($location->is_active)
                                            <span class="label label-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('settings.component_storage_locations.edit', $location) }}" class="btn btn-default">{{ __('Edit') }}</a>
                                            @if ($location->is_active)
                                                <form method="POST" action="{{ route('settings.component_storage_locations.deactivate', $location) }}" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-warning">{{ __('Deactivate') }}</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('settings.component_storage_locations.activate', $location) }}" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-success">{{ __('Activate') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ __('No component storage locations found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer clearfix">
                    {{ $storageLocations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
