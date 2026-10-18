<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFeedback extends Model
{
    protected $table = 'survey_feedback';

    protected $fillable = [
        'survey_request_id',
        'provider',
        'feasible',
        'route',
        'fiber_distance_km',
        'nearest_pop',
        'port',
        'vlan',
        'installation_cost',
        'monthly_cost',
        'expected_delivery_days',
        'feedback',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'feasible' => 'boolean',
            'fiber_distance_km' => 'decimal:3',
            'installation_cost' => 'decimal:2',
            'monthly_cost' => 'decimal:2',
            'expected_delivery_days' => 'integer',
        ];
    }

    public function surveyRequest(): BelongsTo
    {
        return $this->belongsTo(SurveyRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
