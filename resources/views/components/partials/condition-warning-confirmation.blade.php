@php
    $component = $component ?? null;
    $conditionStatus = $conditionStatus ?? ($component?->effectiveConditionStatus());
    $conditionLabel = $conditionLabel
        ?? (\App\Models\ComponentInstance::conditionStatusLabel($conditionStatus) ?? $conditionStatus);
    $show = $show ?? (
        $component
            ? $component->requiresConditionWarningForAttachment()
            : in_array($conditionStatus, \App\Models\ComponentInstance::attachmentWarningConditionStatuses(), true)
    );
    $compact = $compact ?? false;
    $message = $message ?? __('This component is marked :condition. Confirm the warning before installing or attaching it.', [
        'condition' => $conditionLabel,
    ]);
    $checkboxLabel = $checkboxLabel ?? __('I understand this condition warning and want to continue.');
@endphp

@if($show)
    <input type="hidden" name="condition_warning_confirmed" value="0">
    <div class="alert alert-warning{{ $compact ? ' small' : '' }}" role="alert" @if($compact) style="padding:6px 10px;margin-bottom:6px;" @endif>
        <strong>{{ __('Condition warning') }}:</strong> {{ $message }}
        <div class="checkbox" style="{{ $compact ? 'margin:4px 0 0;' : 'margin-bottom:0;' }}">
            <label>
                <input type="checkbox" name="condition_warning_confirmed" value="1" @checked(old('condition_warning_confirmed'))>
                {{ $checkboxLabel }}
            </label>
        </div>
    </div>
@endif
