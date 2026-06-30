<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiMessageRequest extends Model
{
    protected $fillable = [
        'message_id',
        'api_client_id',
        'to_number',
        'message',
        'title',
        'image_url',
        'source',
        'reference_type',
        'reference_id',
        'priority',
        'status',
        'queued_at',
        'payload',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'payload' => 'array',
    ];

    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class);
    }
}
