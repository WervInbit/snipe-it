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
            foreach ($model->getAttributes() as $field => $value) {
                $model->writeTestAudit($field, null, $value);
            }
        });

        static::updating(function ($model) {
            foreach ($model->getDirty() as $field => $new) {
                $before = $model->getOriginal($field);
                if ($before != $new) {
                    $model->writeTestAudit($field, $before, $new);
                }
            }
        });

        static::deleting(function ($model) {
            foreach ($model->getOriginal() as $field => $value) {
                $model->writeTestAudit($field, $value, null);
            }
        });
    }

    protected function writeTestAudit(string $field, $before, $after): void
    {
        TestAudit::create([
            'auditable_type' => static::class,
            'auditable_id'   => $this->getKey(),
            'actor_id'       => Auth::id(),
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
