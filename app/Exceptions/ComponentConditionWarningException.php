<?php

namespace App\Exceptions;

use App\Models\ComponentInstance;
use InvalidArgumentException;

class ComponentConditionWarningException extends InvalidArgumentException
{
    public function __construct(
        public readonly ?string $componentName,
        public readonly string $conditionStatus,
        public readonly string $conditionLabel,
    ) {
        parent::__construct(__(
            'This component is marked :condition. Confirm the condition warning before installing or attaching it.',
            ['condition' => $conditionLabel]
        ));
    }

    public static function forComponent(ComponentInstance $component): self
    {
        $conditionStatus = $component->effectiveConditionStatus();

        return new self(
            $component->display_name,
            $conditionStatus,
            ComponentInstance::conditionStatusLabel($conditionStatus) ?? $conditionStatus,
        );
    }

    public static function forCondition(string $conditionStatus, ?string $componentName = null): self
    {
        return new self(
            $componentName,
            $conditionStatus,
            ComponentInstance::conditionStatusLabel($conditionStatus) ?? $conditionStatus,
        );
    }

    public function payload(): array
    {
        return [
            'condition_warning_required' => true,
            'component_name' => $this->componentName,
            'condition_status' => $this->conditionStatus,
            'condition_label' => $this->conditionLabel,
        ];
    }
}
