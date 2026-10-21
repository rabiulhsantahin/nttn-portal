<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    public const MD = 'md';

    public const RESELLER = 'reseller';

    public const TRANSMISSION = 'transmission';

    public const NOC = 'noc';

    public const BILLING = 'billing';

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
