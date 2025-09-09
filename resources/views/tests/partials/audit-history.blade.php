@if($audits->isNotEmpty())
<div class="card mb-3">
    <div class="card-header">{{ trans('tests.audit_trail') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ trans('tests.run_or_result') }}</th>
                    <th>{{ trans('tests.field') }}</th>
                    <th>{{ trans('general.user') }}</th>
                    <th>{{ trans('tests.timestamp') }}</th>
                    <th>{{ trans('tests.before') }}</th>
                    <th>{{ trans('tests.after') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($audits as $audit)
                <tr>
                    <td>
                        @if($audit->auditable instanceof \App\Models\TestRun)
                            {{ trans('tests.test_run') }} #{{ $audit->auditable->id }}
                        @else
                            {{ $audit->auditable->type->name }} ({{ trans('tests.test_run') }} #{{ $audit->auditable->test_run_id }})
                        @endif
                    </td>
                    <td>{{ $audit->field }}</td>
                    <td>{{ optional($audit->user)->name }}</td>
                    <td>{{ $audit->created_at }}</td>
                    <td>{{ $audit->before }}</td>
                    <td>{{ $audit->after }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
