@extends('layouts/default')

@section('title')
    {{ trans('tests.tests') }}
@endsection

@section('content')
<div class="container">
    @can('tests.execute')
        <form method="POST" action="{{ route('test-runs.store', $asset->id) }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ trans('tests.start_new_run') }}</button>
        </form>
    @endcan

    @foreach ($runs as $run)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-xs-7">
                        <div>{{ optional($run->created_at)->format('Y-m-d H:i') }}</div>
                        <div class="text-muted">{{ optional($run->user)->name }}</div>
                    </div>
                    <div class="col-xs-5 text-right">
                        @can('update', $run)
                            <a href="{{ route('test-results.edit', [$asset->id, $run->id]) }}" class="btn btn-default btn-sm mb-1">{{ trans('button.edit') }}</a>
                        @endcan
                        @can('delete', $run)
                            <form method="POST" action="{{ route('test-runs.destroy', [$asset->id, $run->id]) }}" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">{{ trans('button.delete') }}</button>
                            </form>
                        @endcan
                    </div>
                </div>
                <ul class="list-unstyled mt-2 mb-0">
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
            </div>
        </div>
    @endforeach

    @can('audits.view')
        @php
            $audits = $runs->flatMap->audits
                ->merge($runs->flatMap->results->flatMap->audits)
                ->sortByDesc('created_at');
        @endphp
        @if($audits->isNotEmpty())
            <button class="btn btn-default mb-2" type="button" data-toggle="collapse" data-target="#test-audit-trail">
                {{ trans('tests.view_audit_trail') }}
            </button>
            <div id="test-audit-trail" class="collapse">
                @include('tests.partials.audit-history', ['audits' => $audits])
            </div>
        @endif
    @endcan
</div>
@endsection
