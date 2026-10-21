<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NocHandover extends Model
{
    public const STATUS_RECEIVED = 'RECEIVED';

    public const STATUS_TESTING = 'TESTING';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'work_order_id',
        'link_id',
        'status',
        'remarks',
        'handed_over_at',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'handed_over_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function test(): HasOne
    {
        return $this->hasOne(NocTest::class);
    }
}
