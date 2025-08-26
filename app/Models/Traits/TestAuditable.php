<?php

namespace App\Models\Traits;

use App\Models\TestAudit;
use Illuminate\Support\Facades\Auth;

trait TestAuditable
{
    public static function bootTestAuditable(): void
    {
        static::created(function ($model) {
            $model->writeTestAudit('created', null, $model->attributesToArray());
        });

        static::updating(function ($model) {
            $dirty = $model->getDirty();
            if (!empty($dirty)) {
                $before = [];
                $after = [];
                foreach ($dirty as $field => $new) {
                    $before[$field] = $model->getOriginal($field);
                    $after[$field] = $new;
                }
                $model->writeTestAudit('updated', $before, $after);
            }
        });

        static::deleted(function ($model) {
            $model->writeTestAudit('deleted', $model->getOriginal(), null);
        });
    }

    protected function writeTestAudit(string $event, ?array $before, ?array $after): void
    {
        TestAudit::create([
            'testable_type' => static::class,
            'testable_id'   => $this->getKey(),
            'event'         => $event,
            'before'        => $before,
            'after'         => $after,
            'actor_id'      => Auth::id(),
        ]);
    }

    public function audits()
    {
        return $this->morphMany(TestAudit::class, 'testable')->latest();
    }
}
