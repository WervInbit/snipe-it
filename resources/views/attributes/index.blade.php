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
                        <form method="GET" action="{{ route('attributes.index') }}" class="form-inline" id="attributes-search-form" style="display:inline-block; margin-right:10px;">
                            <div class="input-group input-group-sm">
                                <input
                                    type="search"
                                    name="search"
                                    value="{{ $search ?? '' }}"
                                    class="form-control"
                                    placeholder="{{ __('Search attributes...') }}"
                                    autocomplete="off"
                                    aria-label="{{ __('Search attributes') }}"
                                    data-attribute-live-search
                                >
                                <span class="input-group-addon" data-attribute-search-loading style="display:none;" aria-live="polite">
                                    <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                                    <span class="sr-only">{{ __('Searching...') }}</span>
                                </span>
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-default">{{ __('Search') }}</button>
                                </span>
                            </div>
                        </form>
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
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Categories') }}</th>
                            <th>{{ __('Required') }}</th>
                            <th>{{ __('Asset Overrides') }}</th>
                            <th>{{ __('Options') }}</th>
                            <th class="text-right"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($definitions as $definition)
                            <tr data-attribute-row data-search-text="{{ \Illuminate\Support\Str::lower(implode(' ', array_filter([
                                $definition->label,
                                $definition->key,
                                $definition->datatype,
                                $definition->unit,
                                $definition->isDeprecated() ? __('Deprecated') : __('Active'),
                                $definition->isHidden() ? __('Hidden') : null,
                                $definition->categories->isEmpty() ? __('All') : $definition->categories->pluck('name')->implode(' '),
                                $definition->required_for_category ? __('Required') : null,
                                $definition->allow_asset_override ? __('Asset Overrides') : null,
                            ]))) }}">
                                <td>{{ $definition->label }}</td>
                                <td><code>{{ $definition->key }}</code></td>
                                <td>{{ ucfirst($definition->datatype) }}</td>
                                <td>
                                    @if($definition->isDeprecated())
                                        <span class="label label-warning">{{ __('Deprecated') }}</span>
                                    @else
                                        <span class="label label-success">{{ __('Active') }}</span>
                                    @endif
                                    @if($definition->isHidden())
                                        <span class="label label-default">{{ __('Hidden') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($definition->categories->isEmpty())
                                        <span class="text-muted">{{ __('All') }}</span>
                                    @else
                                        {{ $definition->categories->pluck('name')->implode(', ') }}
                                    @endif
                                </td>
                                <td>{!! $definition->required_for_category ? '<i class="fas fa-check text-success"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                <td>{!! $definition->allow_asset_override ? '<i class="fas fa-toggle-on text-primary"></i>' : '<span class="text-muted">--</span>' !!}</td>
                                <td>{{ $definition->options_count }}</td>
                                <td class="text-right" style="white-space: nowrap;">
                                    <a href="{{ route('attributes.edit', $definition) }}" class="btn btn-xs btn-default">{{ __('Edit') }}</a>
                                    @if($definition->isHidden())
                                        <form action="{{ route('attributes.unhide', $definition) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-xs btn-success">{{ __('Unhide') }}</button>
                                        </form>
                                    @else
                                        <form action="{{ route('attributes.hide', $definition) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-xs btn-warning">{{ __('Hide') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">{{ __('No attributes defined yet.') }}</td>
                            </tr>
                        @endforelse
                        <tr data-attribute-loading style="display:none;">
                            <td colspan="9" class="text-center text-muted">
                                <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                                <span class="sr-only">{{ __('Searching...') }}</span>
                            </td>
                        </tr>
                        <tr data-attribute-no-matches style="display:none;">
                            <td colspan="9" class="text-center text-muted">{{ __('No attributes defined yet.') }}</td>
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

@push('js')
<script nonce="{{ csrf_token() }}">
(function () {
    var form = document.getElementById('attributes-search-form');
    if (!form) return;
    var input = form.querySelector('[data-attribute-live-search]');
    if (!input) return;

    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-attribute-row]'));
    var loadingIndicator = document.querySelector('[data-attribute-search-loading]');
    var loadingRow = document.querySelector('[data-attribute-loading]');
    var noMatchesRow = document.querySelector('[data-attribute-no-matches]');
    var timer = null;
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
        window.clearTimeout(timer);

        timer = window.setTimeout(function () {
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
@endpush
