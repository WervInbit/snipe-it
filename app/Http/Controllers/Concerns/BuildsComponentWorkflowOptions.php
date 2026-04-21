<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ComponentDefinition;
use App\Models\ComponentInstance;
use App\Models\ComponentStorageLocation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

trait BuildsComponentWorkflowOptions
{
    protected function activeComponentDefinitions(): Collection
    {
        return ComponentDefinition::query()
            ->with(['category', 'manufacturer'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function activeStorageLocations(): Collection
    {
        return ComponentStorageLocation::query()
            ->with('siteLocation')
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    protected function storageLocationsByType(): array
    {
        $locations = $this->activeStorageLocations();

        return [
            'stock' => $locations->where('type', ComponentStorageLocation::TYPE_STOCK)->values(),
            'verification' => $locations->where('type', ComponentStorageLocation::TYPE_VERIFICATION)->values(),
            'destruction' => $locations->where('type', ComponentStorageLocation::TYPE_DESTRUCTION)->values(),
            'general' => $locations->where('type', ComponentStorageLocation::TYPE_GENERAL)->values(),
            'all' => $locations->values(),
        ];
    }

    protected function sourceTypeOptions(): array
    {
        return [
            ComponentInstance::SOURCE_MANUAL => __('Manual'),
            ComponentInstance::SOURCE_PURCHASED => __('Purchased'),
            ComponentInstance::SOURCE_EXTERNAL_INTAKE => __('External Intake'),
            ComponentInstance::SOURCE_EXTRACTED => __('Extracted'),
        ];
    }

    protected function conditionOptions(): array
    {
        return [
            ComponentInstance::CONDITION_UNKNOWN => __('Unknown'),
            ComponentInstance::CONDITION_GOOD => __('Good'),
            ComponentInstance::CONDITION_FAIR => __('Fair'),
            ComponentInstance::CONDITION_POOR => __('Poor'),
            ComponentInstance::CONDITION_BROKEN => __('Broken'),
        ];
    }

    protected function trayWarningState(?CarbonInterface $startedAt): array
    {
        if (!$startedAt) {
            return [
                'label' => __('Unknown'),
                'class' => 'label-default',
            ];
        }

        $hours = $startedAt->diffInHours(now());
        $reminderHours = (int) config('components.tray.reminder_hours', 2);
        $verificationHours = (int) config('components.tray.needs_verification_hours', 24);

        if ($hours >= $verificationHours) {
            return [
                'label' => __('Needs Verification'),
                'class' => 'label-danger',
            ];
        }

        if ($hours >= $reminderHours) {
            return [
                'label' => __('Aging'),
                'class' => 'label-warning',
            ];
        }

        return [
            'label' => __('Fresh'),
            'class' => 'label-success',
        ];
    }
}
