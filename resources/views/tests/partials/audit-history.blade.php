@if(auth()->user() && auth()->user()->can('viewAudit') && $auditable->audits->isNotEmpty())
<div class="card mb-3">
    <div class="card-header">{{ __('Audit History') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('Field') }}</th>
                    <th>{{ __('Actor') }}</th>
                    <th>{{ __('Timestamp') }}</th>
                    <th>{{ __('Before') }}</th>
                    <th>{{ __('After') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($auditable->audits as $audit)
                <tr>
                    <td>{{ $audit->field }}</td>
                    <td>{{ optional($audit->actor)->name }}</td>
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
