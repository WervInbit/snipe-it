<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowProfileItem extends SnipeModel
{
    use HasFactory;

    public const LABEL_MODE_PASS_FAIL = 'pass_fail';
    public const LABEL_MODE_DONE_NOT_DONE = 'done_not_done';

    public const LABEL_MODES = [
        self::LABEL_MODE_PASS_FAIL,
        self::LABEL_MODE_DONE_NOT_DONE,
    ];

    protected $table = 'workflow_profile_items';

    protected $fillable = [
        'workflow_profile_id',
        'workflow_item_id',
        'sort_order',
        'is_required',
        'result_label_mode',
    ];

    protected $casts = [
        'workflow_profile_id' => 'int',
        'workflow_item_id' => 'int',
        'sort_order' => 'int',
        'is_required' => 'bool',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkflowProfile::class, 'workflow_profile_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TestType::class, 'workflow_item_id');
    }
}
