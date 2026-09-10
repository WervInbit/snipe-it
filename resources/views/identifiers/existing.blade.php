@foreach (['tag' => ($identifierRecord instanceof \App\Models\Asset ? 'asset_tag' : 'component_tag'), 'serial' => 'serial'] as $identifierField => $identifierColumn)
    @php($identifierSummary = app(\App\Services\IdentifierDuplicateService::class)->summary($identifierField, (string) $identifierRecord->{$identifierColumn}, $identifierRecord))
    @if ($identifierSummary['duplicate'])
        <div class="alert alert-warning" role="status">
            <strong>{{ trans('identifiers.existing') }}:</strong>
            {{ trans('identifiers.warning', ['field' => trans('identifiers.'.$identifierField), 'count' => $identifierSummary['count']]) }}
            <strong>{{ $identifierRecord->{$identifierColumn} }}</strong>
            @foreach ($identifierSummary['matches'] as $identifierMatch)
                <a href="{{ $identifierMatch['url'] }}">{{ $identifierMatch['label'] }} (#{{ $identifierMatch['id'] }})</a>
            @endforeach
            @if (count($identifierSummary['matches']) < $identifierSummary['count'])
                {{ trans('identifiers.restricted') }}
            @endif
        </div>
    @endif
@endforeach
