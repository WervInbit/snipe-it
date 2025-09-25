@extends('layouts/default', [
    'helpText' => __('Define reusable model attributes and constrain how assets inherit their specifications.'),
    'helpPosition' => 'right',
])

@section('title')
    {{ __('Attributes') }}
    @parent
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h2 class="box-title">{{ __('Attribute Definitions') }}</h2>
                    <div class="box-tools pull-right">
                        <a href="{{ route('attributes.create') }}" class="btn btn-primary btn-sm">{{ __('New Attribute') }}</a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Key') }}</th>
                            <th>{{ __('Datatype') }}</th>
                            <th>{{ __('Unit') }}</th>
                            <th>{{ __('Categories') }}</th>
                            <th>{{ __('Required') }}</th>
                            <th>{{ __('Needs Test') }}</th>
                            <th>{{ __('Asset Overrides') }}</th>
                            <th>{{ __('Options') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($definitions as $definition)
                            <tr>
                                <td>{{ $definition->label }}</td>
                                <td><code>{{ $definition->key }}</code></td>
                                <td>{{ ucfirst($definition->datatype) }}</td>
                                <td>{{ $definition->unit ?? '--' }}</td>
                                <td>
                                    @if($definition->categories->isEmpty())
                                        <span class="text-muted">{{ __('All') }}</span>
                                    @else
                                        {{ $definition->categories->pluck('name')->implode(', ') }}
                                    @endif
                                </td>
                                <td>{!! $definition->required_for_category ? '<i class="fas fa-check text-success"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                <td>{!! $definition->needs_test ? '<i class="fas fa-vial text-info"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                <td>{!! $definition->allow_asset_override ? '<i class="fas fa-toggle-on text-primary"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                <td>{{ $definition->options_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('attributes.edit', $definition) }}" class="btn btn-xs btn-default">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">{{ __('No attributes defined yet.') }}</td>
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
