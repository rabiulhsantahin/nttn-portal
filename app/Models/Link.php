<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Link extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_TERMINATED = 'TERMINATED';

    protected $fillable = [
        'link_id',
        'customer_id',
        'reseller_id',
        'work_order_id',
        'bandwidth_mbps',
        'nttn_provider',
        'circuit_id',
        'nttn_link_id',
        'vlan',
        'port',
        'status',
        'is_legacy',
        'md_approval_ref',
        'md_approval_doc',
        'legacy_notes',
        'activation_date',
    ];

    protected function casts(): array
    {
        return [
            'bandwidth_mbps' => 'integer',
            'activation_date' => 'date',
            'is_legacy' => 'boolean',
        ];
    }

    public function isLegacy(): bool
    {
        return (bool) $this->is_legacy;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function billing(): HasOne
    {
        return $this->hasOne(Billing::class);
    }

    public function chatRoom(): HasOne
    {
        return $this->hasOne(ChatRoom::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'entity_id')->where('entity_type', 'link');
    }

    public function rateRevisions(): HasMany
    {
        return $this->hasMany(BillingRateRevision::class);
    }

    public function pendingRateRevision(): HasOne
    {
        return $this->hasOne(BillingRateRevision::class)->where('status', BillingRateRevision::STATUS_PENDING)->latestOfMany();
    }

    public function canBeDiscontinued(): bool
    {
        $activationDate = $this->activation_date ?? $this->created_at;
        if (! $activationDate) {
            return false;
        }

        return now()->greaterThanOrEqualTo($activationDate->copy()->addMonths(3));
    }

    public function discontinuationEligibleDate(): ?Carbon
    {
        $activationDate = $this->activation_date ?? $this->created_at;

        return $activationDate ? $activationDate->copy()->addMonths(3) : null;
    }

    public function getUnitPriceAttribute(): float
    {
        $bandwidth = (int) $this->bandwidth_mbps;
        $monthly = (float) ($this->billing?->monthly_charge ?? 0);

        return $bandwidth > 0 ? round($monthly / $bandwidth, 2) : 0.0;
    }
}
