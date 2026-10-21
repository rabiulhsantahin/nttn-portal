<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    public const TYPE_UPGRADE = 'UPGRADE';

    public const TYPE_DOWNGRADE = 'DOWNGRADE';

    public const TYPE_DISCONTINUE = 'DISCONTINUE';

    public const STATUS_PENDING_BILLING = 'PENDING_BILLING';

    public const STATUS_PENDING_MD = 'PENDING_MD';

    public const STATUS_PENDING_TX = 'PENDING_TX';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'request_no',
        'link_id',
        'type',
        'old_bandwidth_mbps',
        'requested_bandwidth_mbps',
        'new_monthly_charge',
        'reason',
        'tx_remarks',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'old_bandwidth_mbps' => 'integer',
            'requested_bandwidth_mbps' => 'integer',
            'new_monthly_charge' => 'decimal:2',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
