<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'vlan',
        'port',
        'status',
        'activation_date',
    ];

    protected function casts(): array
    {
        return [
            'bandwidth_mbps' => 'integer',
            'activation_date' => 'date',
        ];
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
}
