<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'nttn_provider',
        'nttn_link_id',
        'nttn_provider_email',
        'nttn_email_sent_at',
        'attachment_path',
        'status',
        'is_customer_verified',
        'verified_at',
        'verified_by',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_customer_verified' => 'boolean',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'nttn_email_sent_at' => 'datetime',
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_CLOSED || $this->is_customer_verified;
    }

    public function canBeVerified(): bool
    {
        return ! $this->is_customer_verified && in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }
}
