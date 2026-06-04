@extends('layouts/default')

@section('title')
    {{ __('Workflow Profiles') }}
@parent
@stop

@push('css')
<style nonce="{{ csrf_token() }}">
    .workflow-profile-flag {
        white-space: nowrap;
    }
</style>
@endpush

@section('header_right')
    <a href="{{ route('settings.testtypes.index') }}" class="btn btn-default">
        <x-icon type="tasks" /> {{ __('Workflow Items') }}
    </a>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#create-workflow-profile-modal">
        <x-icon type="plus" /> {{ __('Create Profile') }}
    </button>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Workflow Profiles') }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Order') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Categories') }}</th>
                                <th>{{ __('Items') }}</th>
                                <th>{{ __('Flags') }}</th>
                                <th class="text-right">{{ trans('button.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profiles as $profile)
                                <tr>
                                    <td>{{ $profile->display_order }}</td>
                                    <td>
                                        <strong>{{ $profile->name }}</strong>
                                        @if($profile->description)
                                            <div class="text-muted">{{ \Illuminate\Support\Str::limit($profile->description, 90) }}</div>
                                        @endif
                                    </td>
                                    <td class="monospace text-muted">{{ $profile->slug }}</td>
                                    <td>{{ $profile->categories->pluck('name')->implode(', ') ?: trans('general.all') }}</td>
                                    <td>{{ $profile->items_count }}</td>
                                    <td>
                                        @if($profile->is_active)
                                            <span class="label label-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="label label-default">{{ __('Inactive') }}</span>
                                        @endif
                                        @if($profile->is_default)
                                            <span class="label label-primary">{{ __('Default') }}</span>
                                        @endif
                                        @if($profile->blocks_sale_readiness)
                                            <span class="label label-warning">{{ __('Blocks sale') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="{{ trans('button.actions') }}">
                                            <a href="{{ route('settings.workflow-profiles.items.edit', $profile) }}" class="btn btn-default">
                                                {{ __('Items') }}
                                            </a>
                                            <button type="button"
                                                    class="btn btn-default"
                                                    data-toggle="modal"
                                                    data-target="#edit-workflow-profile-{{ $profile->id }}-modal">
                                                {{ trans('button.edit') }}
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('settings.workflow-profiles.destroy', $profile) }}"
                                                  style="display:inline-block"
                                                  onsubmit="return confirm('{{ __('Delete this workflow profile? Historical runs keep their saved profile snapshot.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    {{ trans('button.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ trans('general.no_results') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="create-workflow-profile-modal" tabindex="-1" role="dialog" aria-labelledby="create-workflow-profile-label">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('settings.workflow-profiles.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="create-workflow-profile-label">{{ __('Create Workflow Profile') }}</h4>
                    </div>
                    <div class="modal-body">
                        @include('settings.partials.workflow-profile-form', ['profile' => null, 'categories' => $categories])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ trans('button.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @foreach($profiles as $profile)
        <div class="modal fade" id="edit-workflow-profile-{{ $profile->id }}-modal" tabindex="-1" role="dialog" aria-labelledby="edit-workflow-profile-{{ $profile->id }}-label">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('settings.workflow-profiles.update', $profile) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('general.close') }}"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="edit-workflow-profile-{{ $profile->id }}-label">{{ __('Edit :name', ['name' => $profile->name]) }}</h4>
                        </div>
                        <div class="modal-body">
                            @include('settings.partials.workflow-profile-form', ['profile' => $profile, 'categories' => $categories])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ trans('button.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
