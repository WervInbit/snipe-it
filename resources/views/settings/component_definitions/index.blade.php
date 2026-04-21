@extends('layouts/default')

@section('title')
    {{ __('Component Definitions') }}
@parent
@stop

@section('header_right')
    <a href="{{ route('settings.component_definitions.create') }}" class="btn btn-primary" style="margin-right: 10px;">{{ __('Create New') }}</a>
    <form method="GET" class="form-inline" role="search" style="display:inline-block;">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="{{ __('Search definitions...') }}" value="{{ $search }}">
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
                    <h3 class="box-title">{{ __('Component Definitions') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Manufacturer') }}</th>
                                <th>{{ __('Instances') }}</th>
                                <th>{{ __('Templates') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($definitions as $definition)
                                <tr>
                                    <td>
                                        <strong>{{ $definition->name }}</strong>
                                        @if ($definition->part_code)
                                            <div class="text-muted small">{{ $definition->part_code }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $definition->category?->name ?: __('-') }}</td>
                                    <td>{{ $definition->manufacturer?->name ?: __('-') }}</td>
                                    <td><span class="label label-default">{{ $definition->instances_count }}</span></td>
                                    <td><span class="label label-default">{{ $definition->expected_templates_count }}</span></td>
                                    <td>
                                        @if ($definition->is_active)
                                            <span class="label label-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('settings.component_definitions.edit', $definition) }}" class="btn btn-default">{{ __('Edit') }}</a>
                                            @if ($definition->is_active)
                                                <form method="POST" action="{{ route('settings.component_definitions.deactivate', $definition) }}" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-warning">{{ __('Deactivate') }}</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('settings.component_definitions.activate', $definition) }}" style="display:inline;">
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
                                    <td colspan="7" class="text-center text-muted">{{ __('No component definitions found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer clearfix">
                    {{ $definitions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
