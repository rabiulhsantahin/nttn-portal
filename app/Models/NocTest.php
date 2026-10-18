<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NocTest extends Model
{
    public const RESULT_PENDING = 'PENDING';
    public const RESULT_PASSED = 'PASSED';
    public const RESULT_FAILED = 'FAILED';

    protected $fillable = [
        'noc_handover_id',
        'physical_link',
        'optical_power',
        'vlan_ok',
        'bandwidth_ok',
        'ping_ok',
        'latency_ok',
        'packet_loss_ok',
        'monitoring_ok',
        'result',
        'remarks',
        'tested_by',
    ];

    protected function casts(): array
    {
        return [
            'physical_link' => 'boolean',
            'optical_power' => 'boolean',
            'vlan_ok' => 'boolean',
            'bandwidth_ok' => 'boolean',
            'ping_ok' => 'boolean',
            'latency_ok' => 'boolean',
            'packet_loss_ok' => 'boolean',
            'monitoring_ok' => 'boolean',
        ];
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(NocHandover::class, 'noc_handover_id');
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }

    public function allChecksPassed(): bool
    {
        return $this->physical_link
            && $this->optical_power
            && $this->vlan_ok
            && $this->bandwidth_ok
            && $this->ping_ok
            && $this->latency_ok
            && $this->packet_loss_ok
            && $this->monitoring_ok;
    }
}
