<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'reseller_id',
        'name',
        'phone',
        'address',
        'district',
        'thana',
        'latitude',
        'longitude',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function surveyRequests(): HasMany
    {
        return $this->hasMany(SurveyRequest::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }
}
