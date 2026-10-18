<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $fillable = [
        'link_id',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
