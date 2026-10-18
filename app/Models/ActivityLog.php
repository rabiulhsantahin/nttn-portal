<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'from_status',
        'to_status',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $entityType,
        int $entityId,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $remarks = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'remarks' => $remarks,
            'created_at' => now(),
        ]);
    }
}
