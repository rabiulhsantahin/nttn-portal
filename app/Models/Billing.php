<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billing extends Model
{
    protected $table = 'billing';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'link_id',
        'installation_charge',
        'monthly_charge',
        'nttn_cost',
        'billing_start_date',
        'due_date',
        'invoice_no',
        'status',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'installation_charge' => 'decimal:2',
            'monthly_charge' => 'decimal:2',
            'nttn_cost' => 'decimal:2',
            'billing_start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
