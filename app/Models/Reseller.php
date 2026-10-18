<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    protected $fillable = [
        'user_id',
        'reseller_code',
        'company_name',
        'address',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
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
