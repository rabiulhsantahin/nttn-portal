<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRateRevision extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'link_id',
        'billing_id',
        'requested_by',
        'approved_by',
        'old_monthly_charge',
        'new_monthly_charge',
        'old_installation_charge',
        'new_installation_charge',
        'old_nttn_cost',
        'new_nttn_cost',
        'effective_date',
        'reason',
        'status',
        'remarks',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'old_monthly_charge' => 'decimal:2',
            'new_monthly_charge' => 'decimal:2',
            'old_installation_charge' => 'decimal:2',
            'new_installation_charge' => 'decimal:2',
            'old_nttn_cost' => 'decimal:2',
            'new_nttn_cost' => 'decimal:2',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
