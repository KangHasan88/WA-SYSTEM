<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'approval_id',
        'code',
        'api_client_id',
        'to_number',
        'source',
        'action',
        'risk',
        'status',
        'payload',
        'dry_run',
        'expires_at',
        'sent_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'dry_run' => 'boolean',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }
}
