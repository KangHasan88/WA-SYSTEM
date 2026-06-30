<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WALog extends Model
{
    protected $table = 'wa_logs';

    protected $fillable = [
        'number',
        'message',
        'title',
        'image_url',  // <-- TAMBAHKAN INI
        'status',
        'response'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}