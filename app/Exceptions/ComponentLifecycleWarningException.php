<?php

namespace App\Exceptions;

use App\Models\ComponentInstance;
use InvalidArgumentException;

class ComponentLifecycleWarningException extends InvalidArgumentException
{
    public function __construct(
        public readonly ?string $componentName,
        public readonly string $lifecycleStatus,
        public readonly string $lifecycleLabel,
    ) {
        parent::__construct(__(
            'This component is marked :status. Confirm the lifecycle warning before installing or attaching it.',
            ['status' => $lifecycleLabel]
        ));
    }

    public static function forComponent(ComponentInstance $component): self
    {
        $lifecycleStatus = $component->effectiveLifecycleStatus();

        return new self(
            $component->display_name,
            $lifecycleStatus,
            ComponentInstance::lifecycleStatusLabel($lifecycleStatus) ?? $lifecycleStatus,
        );
    }

    public function payload(): array
    {
        return [
            'lifecycle_warning_required' => true,
            'component_name' => $this->componentName,
            'lifecycle_status' => $this->lifecycleStatus,
            'lifecycle_label' => $this->lifecycleLabel,
        ];
    }
}
