@php
    $missingProfiles = collect($testSummary['missing_profiles'] ?? []);
@endphp

@if ($missingProfiles->isNotEmpty())
    <li>{{ trans('tests.missing_workflow_profiles', ['profiles' => $missingProfiles->implode(', ')]) }}</li>
@elseif ($testSummary['missing_run'] ?? false)
    <li>{{ trans('tests.no_test_run_recorded') }}</li>
@endif
@if ($testSummary['failed']->isNotEmpty())
    <li>{{ trans('tests.failed_list', ['tests' => $testSummary['failed']->implode(', ')]) }}</li>
@endif
@if ($testSummary['incomplete']->isNotEmpty())
    <li>{{ trans('tests.incomplete_list', ['tests' => $testSummary['incomplete']->implode(', ')]) }}</li>
@endif
