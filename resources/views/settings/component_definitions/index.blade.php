@extends('layouts/default')

@section('title')
    {{ __('Component Definitions') }}
@parent
@stop

@section('header_right')
    <a href="{{ route('settings.component_definitions.create') }}" class="btn btn-primary" style="margin-right: 10px;">{{ __('Create New') }}</a>
    <form method="GET" class="form-inline" role="search" id="component-definition-search-form" style="display:inline-block;">
        <div class="input-group">
            <input
                type="search"
                name="search"
                class="form-control"
                placeholder="{{ __('Search definitions...') }}"
                value="{{ $search }}"
                autocomplete="off"
                data-component-definition-live-search
            >
            <span class="input-group-addon" data-component-definition-search-loading style="display:none;" aria-live="polite">
                <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                <span class="sr-only">{{ __('Searching...') }}</span>
            </span>
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
                                <tr data-component-definition-row data-search-text="{{ \Illuminate\Support\Str::lower(implode(' ', array_filter([
                                    $definition->name,
                                    $definition->part_code,
                                    $definition->model_number,
                                    $definition->category?->name,
                                    $definition->manufacturer?->name,
                                    $definition->is_active ? __('Active') : __('Inactive'),
                                ]))) }}">
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
                            <tr data-component-definition-loading style="display:none;">
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                                    <span class="sr-only">{{ __('Searching...') }}</span>
                                </td>
                            </tr>
                            <tr data-component-definition-no-matches style="display:none;">
                                <td colspan="7" class="text-center text-muted">{{ __('No component definitions found.') }}</td>
                            </tr>
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

@section('moar_scripts')
    <script>
        (function () {
            var input = document.querySelector('[data-component-definition-live-search]');
            var form = document.getElementById('component-definition-search-form');

            if (!input || !form) {
                return;
            }

            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-component-definition-row]'));
            var loadingIndicator = document.querySelector('[data-component-definition-search-loading]');
            var loadingRow = document.querySelector('[data-component-definition-loading]');
            var noMatchesRow = document.querySelector('[data-component-definition-no-matches]');
            var submitTimer = null;
            var loadedSearch = input.value.trim();

            function isServerSearchPending() {
                return input.value.trim() !== loadedSearch;
            }

            function setLoading(isLoading) {
                if (loadingIndicator) {
                    loadingIndicator.style.display = isLoading ? '' : 'none';
                }
            }

            function filterVisibleRows() {
                var query = input.value.trim().toLowerCase();
                var visibleRows = 0;
                var isLoading = isServerSearchPending();

                rows.forEach(function (row) {
                    var haystack = row.getAttribute('data-search-text') || '';
                    var isMatch = query === '' || haystack.indexOf(query) !== -1;

                    row.style.display = isMatch ? '' : 'none';

                    if (isMatch) {
                        visibleRows++;
                    }
                });

                setLoading(isLoading);

                if (loadingRow) {
                    loadingRow.style.display = rows.length > 0 && visibleRows === 0 && isLoading ? '' : 'none';
                }

                if (noMatchesRow) {
                    noMatchesRow.style.display = rows.length > 0 && visibleRows === 0 && !isLoading ? '' : 'none';
                }
            }

            input.addEventListener('input', function () {
                filterVisibleRows();
                window.clearTimeout(submitTimer);

                submitTimer = window.setTimeout(function () {
                    var currentSearch = input.value.trim();

                    if (currentSearch === loadedSearch) {
                        setLoading(false);
                        return;
                    }

                    setLoading(true);
                    form.submit();
                }, 600);
            });

            form.addEventListener('submit', function () {
                if (input.value.trim() !== loadedSearch) {
                    setLoading(true);
                }
            });

            filterVisibleRows();
        })();
    </script>
@endsection
