<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ComponentInstance;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesComponentSerialChanges
{
    protected function componentSerialChangeRules(): array
    {
        return [
            'serial_change_enabled' => ['nullable', 'boolean'],
            'serial_change_confirmed' => ['nullable', 'boolean'],
            'serial' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function componentSerialContextFromRequest(Request $request, ?ComponentInstance $component = null): array
    {
        if (!$request->boolean('serial_change_enabled')) {
            return [];
        }

        $serial = $this->normalizeComponentSerialInput($request->input('serial'));
        $currentSerial = $this->normalizeComponentSerialInput($component?->serial);

        if ($currentSerial !== null && $serial !== $currentSerial && !$request->boolean('serial_change_confirmed')) {
            throw ValidationException::withMessages([
                'serial' => trans('general.component_serial_change_required'),
            ]);
        }

        return [
            'serial' => $serial,
        ];
    }

    protected function normalizeComponentSerialInput(mixed $serial): ?string
    {
        $value = trim((string) ($serial ?? ''));

        return $value !== '' ? $value : null;
    }
}
