<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_SENT_BACK = 'SENT_BACK';

    public const STAGE_MD_SURVEY = 'MD_SURVEY';
    public const STAGE_FINAL_MD = 'FINAL_MD';
    public const STAGE_SERVICE_REQUEST = 'SERVICE_REQUEST';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'approval_stage',
        'status',
        'remarks',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
