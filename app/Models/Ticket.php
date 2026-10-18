<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_WORKING = 'WORKING';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    public const PRIORITY_CRITICAL = 'CRITICAL';
    public const PRIORITY_HIGH = 'HIGH';
    public const PRIORITY_NORMAL = 'NORMAL';
    public const PRIORITY_LOW = 'LOW';

    protected $fillable = [
        'ticket_no',
        'link_id',
        'created_by',
        'assigned_to',
        'type',
        'priority',
        'description',
        'status',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
