<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_WORK_IN_PROGRESS = 'WORK_IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'work_order_no',
        'survey_request_id',
        'assigned_to',
        'status',
        'deadline',
        'instructions',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function surveyRequest(): BelongsTo
    {
        return $this->belongsTo(SurveyRequest::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function link(): HasOne
    {
        return $this->hasOne(Link::class);
    }

    public function nocHandover(): HasOne
    {
        return $this->hasOne(NocHandover::class);
    }
}
