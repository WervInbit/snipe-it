@php
    $legacyAttestations = collect($legacyAttestations ?? []);
    $legacyAcceptedIds = collect($testSummary['legacy_accepted'] ?? [])->pluck('attestation.id')->filter()->map(fn ($id) => (int) $id);
    $legacyStaleIds = collect($testSummary['legacy_stale'] ?? [])->pluck('attestation.id')->filter()->map(fn ($id) => (int) $id);
@endphp

@if ($legacyAttestations->isNotEmpty())
    <div class="panel panel-info" data-testid="legacy-workflow-evidence">
        <div class="panel-heading">
            <strong>{{ trans('tests.legacy_evidence_title') }}</strong>
            <span class="label label-info pull-right">{{ $legacyAttestations->whereNull('revoked_at')->whereNull('superseded_at')->count() }}</span>
        </div>
        <div class="panel-body">
            <p>{{ trans('tests.legacy_history_explanation') }}</p>
            @foreach ($legacyAttestations->groupBy('profile_name_snapshot') as $profileName => $profileRows)
                <details>
                    <summary><strong>{{ $profileName }}</strong> ({{ $profileRows->count() }})</summary>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>{{ trans('tests.legacy_workflow_item') }}</th>
                                    <th>{{ trans('tests.legacy_state') }}</th>
                                    <th>{{ trans('tests.legacy_original_date') }}</th>
                                    <th>{{ trans('tests.legacy_accepted_by') }}</th>
                                    <th>{{ trans('tests.legacy_source_note') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($profileRows as $attestation)
                                    @php
                                        $state = $attestation->revoked_at
                                            ? 'revoked'
                                            : ($attestation->superseded_at
                                                ? 'superseded'
                                                : ($legacyStaleIds->contains((int) $attestation->id) ? 'stale' : 'active'));
                                    @endphp
                                    <tr>
                                        <td>{{ $attestation->item_name_snapshot }}</td>
                                        <td>
                                            @if ($state === 'active')
                                                <span class="label label-info">{{ trans('tests.legacy_accepted') }}</span>
                                            @elseif ($state === 'stale')
                                                <span class="label label-warning">{{ trans('tests.legacy_review_required') }}</span>
                                            @elseif ($state === 'superseded')
                                                <span class="label label-default">{{ trans('tests.legacy_superseded') }}</span>
                                            @else
                                                <span class="label label-danger">{{ trans('tests.legacy_revoked') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($attestation->source_tested_at)->format('Y-m-d') ?: trans('tests.legacy_unknown') }}</td>
                                        <td>
                                            {{ $attestation->accepted_by_name_snapshot ?: trans('tests.legacy_unknown') }}
                                            <br><small class="text-muted">{{ optional($attestation->accepted_at)->format('Y-m-d H:i') }}</small>
                                        </td>
                                        <td>{{ $attestation->source_note }}</td>
                                        <td>
                                            @if ($state === 'active' && Gate::allows('tests.manage_legacy_evidence'))
                                                <form method="POST" action="{{ route('hardware.legacy-workflow-attestations.revoke', [$asset, $attestation]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="sr-only" for="revoke-legacy-{{ $attestation->id }}">{{ trans('tests.legacy_revocation_reason') }}</label>
                                                    <input id="revoke-legacy-{{ $attestation->id }}" class="form-control input-sm" name="revocation_reason" required maxlength="2000" placeholder="{{ trans('tests.legacy_revocation_reason') }}">
                                                    <button class="btn btn-danger btn-xs" type="submit" style="margin-top:4px">{{ trans('tests.legacy_revoke') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
@endif
