<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMapping extends Model
{
    protected $fillable = [
        'central_tenant_id',
        'name_snapshot',
        'slug_snapshot',
        'status',
        'plan_snapshot',
        'timezone',
        'settings',
        'synced_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'synced_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(TenantUser::class, 'tenant_id');
    }
}
