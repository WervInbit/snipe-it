<?php

namespace App\Models;

use App\Models\SnipeModel;
use App\Models\Traits\TestAuditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\AttributeDefinition;
use App\Models\TestResultPhoto;

/**
 * Stores the outcome of a single workflow item within a run.
 *
 * Each result belongs to a specific workflow run and workflow item and may have
 * multiple audit records.
 *
 * @property string|null $note Note captured during the test run.
 */
class TestResult extends SnipeModel
{
    use HasFactory;
    use TestAuditable;

    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_NVT  = 'nvt';

    /**
     * Allowed status values for a test result.
     */
    public const STATUSES = [
        self::STATUS_PASS,
        self::STATUS_FAIL,
        self::STATUS_NVT,
    ];

    protected $table = 'workflow_results';

    protected $fillable = [
        'workflow_run_id',
        'workflow_item_id',
        'workflow_profile_item_id',
        'attribute_definition_id',
        'status',
        'note',
        'photo_path',
        'expected_value',
        'expected_raw_value',
        'is_required',
        'result_label_mode',
        'sort_order',
    ];

    protected array $auditFields = [
        'workflow_run_id',
        'workflow_item_id',
        'workflow_profile_item_id',
        'status',
        'note',
        'attribute_definition_id',
        'expected_value',
        'expected_raw_value',
        'is_required',
        'result_label_mode',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'bool',
        'sort_order' => 'int',
    ];

    public function testRun(): BelongsTo
    {
        return $this->belongsTo(TestRun::class, 'workflow_run_id');
    }

    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TestType::class, 'workflow_item_id');
    }

    public function profileItem(): BelongsTo
    {
        return $this->belongsTo(WorkflowProfileItem::class, 'workflow_profile_item_id');
    }

    /**
     * Audit log entries for changes to this result.
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(TestAudit::class, 'auditable');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TestResultPhoto::class, 'workflow_result_id');
    }
}
