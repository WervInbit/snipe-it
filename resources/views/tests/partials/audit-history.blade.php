@if(auth()->user() && (auth()->user()->isSuperUser() || auth()->user()->isAdmin()))
<div class="card mb-3">
    <div class="card-header">{{ __('Audit History') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>{{ __('Event') }}</th>
                    <th>{{ __('Actor') }}</th>
                    <th>{{ __('Timestamp') }}</th>
                    <th>{{ __('Before') }}</th>
                    <th>{{ __('After') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($auditable->audits as $audit)
                <tr>
                    <td>{{ $audit->event }}</td>
                    <td>{{ optional($audit->actor)->name }}</td>
                    <td>{{ $audit->created_at }}</td>
                    <td><pre class="mb-0">{{ json_encode($audit->before, JSON_PRETTY_PRINT) }}</pre></td>
                    <td><pre class="mb-0">{{ json_encode($audit->after, JSON_PRETTY_PRINT) }}</pre></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
