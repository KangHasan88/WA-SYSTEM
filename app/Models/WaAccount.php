<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'label',
        'phone_number',
        'session_id',
        'session_path',
        'status',
        'last_connected_at',
        'last_disconnected_at',
        'rate_limit_per_hour',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
        'last_disconnected_at' => 'datetime',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(TenantMapping::class, 'tenant_id');
    }
}
