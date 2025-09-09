<?php

namespace App\Models\Traits;

use App\Models\TestAudit;
use Illuminate\Support\Facades\Auth;

trait TestAuditable
{
    public static function bootTestAuditable(): void
    {
        static::creating(function ($model) {
            // Intentionally left blank to allow hooking into the creating event.
        });

        static::created(function ($model) {
            foreach ($model->getAuditFields() as $field) {
                $model->writeTestAudit($field, null, $model->getAttribute($field));
            }
        });

        static::updating(function ($model) {
            foreach ($model->getAuditFields() as $field) {
                if ($model->isDirty($field)) {
                    $model->writeTestAudit($field, $model->getOriginal($field), $model->getAttribute($field));
                }
            }
        });

        static::deleting(function ($model) {
            foreach ($model->getAuditFields() as $field) {
                $model->writeTestAudit($field, $model->getOriginal($field), null);
            }
        });
    }

    protected function getAuditFields(): array
    {
        return property_exists($this, 'auditFields')
            ? $this->auditFields
            : array_keys($this->getAttributes());
    }

    protected function writeTestAudit(string $field, $before, $after): void
    {
        TestAudit::create([
            'auditable_type' => static::class,
            'auditable_id'   => $this->getKey(),
            'user_id'        => Auth::id(),
            'field'          => $field,
            'before'         => $this->serializeValue($before),
            'after'          => $this->serializeValue($after),
            'created_at'     => now(),
        ]);
    }

    protected function serializeValue($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        return is_null($value) ? null : (string) $value;
    }
}
