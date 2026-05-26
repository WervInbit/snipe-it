@php
    $component = $component ?? null;
    $lifecycleStatus = $lifecycleStatus ?? ($component?->effectiveLifecycleStatus());
    $lifecycleLabel = $lifecycleLabel
        ?? (\App\Models\ComponentInstance::lifecycleStatusLabel($lifecycleStatus) ?? $lifecycleStatus);
    $show = $show ?? (
        $component
            ? $component->requiresLifecycleWarningForAttachment()
            : in_array($lifecycleStatus, \App\Models\ComponentInstance::attachmentWarningLifecycleStatuses(), true)
    );
    $message = $message ?? __('This component is marked :status. Confirm before installing or attaching it.', [
        'status' => $lifecycleLabel,
    ]);
    $checkboxLabel = $checkboxLabel ?? __('I understand this lifecycle warning and want to continue.');
@endphp

@if($show)
    <input type="hidden" name="lifecycle_warning_confirmed" value="0">
    <div class="alert alert-warning" role="alert">
        <strong>{{ __('Lifecycle warning') }}:</strong> {{ $message }}
        <div class="checkbox" style="margin-bottom:0;">
            <label>
                <input type="checkbox" name="lifecycle_warning_confirmed" value="1" @checked(old('lifecycle_warning_confirmed'))>
                {{ $checkboxLabel }}
            </label>
        </div>
    </div>
@endif
