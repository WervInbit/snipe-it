@extends('layouts/default')


@section('title')
    {{ trans('tests.tests') }}
@endsection

@section('content')
<div class="mb-3 text-right">
    <form method="POST" action="{{ route('test-runs.store', $asset->id) }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-primary">{{ trans('tests.start_new_run') }}</button>
    </form>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>{{ trans('general.date') }}</th>
            <th>{{ trans('general.user') }}</th>
            <th>{{ trans('tests.test') }}</th>
            <th>{{ trans('table.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($runs as $run)
            <tr>
                <td>{{ optional($run->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ optional($run->user)->name }}</td>
                <td>
                    <ul class="list-unstyled mb-0">
                        @foreach ($run->results as $result)
                            <li>
                                {{ $result->type->name }}
                                <i class="fas fa-info-circle" data-toggle="tooltip" title="{{ $result->type->tooltip }}"></i>:
                                {{ trans('tests.' . $result->status) }}
                                @if ($result->note)
                                    <span class="text-muted">{{ $result->note }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    <a href="{{ route('test-results.edit', [$asset->id, $run->id]) }}" class="btn btn-default btn-sm">{{ trans('button.edit') }}</a>
                    <form method="POST" action="{{ route('test-runs.destroy', [$asset->id, $run->id]) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@can('viewAudit')
    @foreach ($runs as $run)
        @include('tests.partials.audit-history', ['auditable' => $run])
        @foreach ($run->results as $result)
            @include('tests.partials.audit-history', ['auditable' => $result])
        @endforeach
    @endforeach
@endcan

@endsection
