<?php

namespace App\Models;

use App\Models\Traits\TestAuditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkflowLegacyAttestation extends SnipeModel
{
    use HasFactory;
    use TestAuditable;

    protected $table = 'workflow_legacy_attestations';

    protected $fillable = [
        'evidence_set_uuid',
        'asset_id',
        'model_number_id',
        'workflow_profile_id',
        'workflow_profile_item_id',
        'workflow_item_id',
        'profile_name_snapshot',
        'profile_slug_snapshot',
        'item_name_snapshot',
        'item_slug_snapshot',
        'requirement_context_hash',
        'source_note',
        'source_tested_at',
        'accepted_by',
        'accepted_by_name_snapshot',
        'accepted_at',
        'superseded_at',
        'superseded_by_result_id',
        'revoked_by',
        'revoked_by_name_snapshot',
        'revoked_at',
        'revocation_reason',
        'active_slot',
    ];

    protected array $auditFields = [
        'asset_id',
        'model_number_id',
        'workflow_profile_id',
        'workflow_profile_item_id',
        'workflow_item_id',
        'requirement_context_hash',
        'source_note',
        'source_tested_at',
        'accepted_by',
        'accepted_at',
        'superseded_at',
        'superseded_by_result_id',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'active_slot',
    ];

    protected $casts = [
        'source_tested_at' => 'date',
        'accepted_at' => 'datetime',
        'superseded_at' => 'datetime',
        'revoked_at' => 'datetime',
        'active_slot' => 'int',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->whereNull('superseded_at')
            ->where('active_slot', 1);
    }

    public function scopeForAsset(Builder $query, Asset $asset): Builder
    {
        return $query->where('asset_id', $asset->id);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function modelNumber(): BelongsTo
    {
        return $this->belongsTo(ModelNumber::class, 'model_number_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkflowProfile::class, 'workflow_profile_id');
    }

    public function profileItem(): BelongsTo
    {
        return $this->belongsTo(WorkflowProfileItem::class, 'workflow_profile_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TestType::class, 'workflow_item_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function supersedingResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class, 'superseded_by_result_id');
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }
}
