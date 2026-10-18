<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveyRequest extends Model
{
    public const STATUS_SURVEY_REQUESTED = 'SURVEY_REQUESTED';
    public const STATUS_SURVEY_PROCESSING = 'SURVEY_PROCESSING';
    public const STATUS_FEEDBACK_READY = 'FEEDBACK_READY';
    public const STATUS_MD_APPROVAL_PENDING = 'MD_APPROVAL_PENDING';
    public const STATUS_MD_APPROVED = 'MD_APPROVED';
    public const STATUS_MD_REJECTED = 'MD_REJECTED';
    public const STATUS_SENT_BACK = 'SENT_BACK';
    public const STATUS_WORK_ORDER_ISSUED = 'WORK_ORDER_ISSUED';
    public const STATUS_WORK_IN_PROGRESS = 'WORK_IN_PROGRESS';
    public const STATUS_NOC_HANDOVER = 'NOC_HANDOVER';
    public const STATUS_NOC_TESTING = 'NOC_TESTING';
    public const STATUS_NOC_VERIFIED = 'NOC_VERIFIED';
    public const STATUS_BILLING_PENDING = 'BILLING_PENDING';
    public const STATUS_BILLING_COMPLETED = 'BILLING_COMPLETED';
    public const STATUS_FINAL_MD_APPROVAL = 'FINAL_MD_APPROVAL';
    public const STATUS_FINAL_APPROVED = 'FINAL_APPROVED';
    public const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'request_no',
        'reseller_id',
        'customer_id',
        'bandwidth_mbps',
        'service_type',
        'preferred_nttn',
        'description',
        'status',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'bandwidth_mbps' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(SurveyFeedback::class);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }
}
