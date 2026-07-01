<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUser extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'central_tenant_user_id',
        'role',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(TenantMapping::class, 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
