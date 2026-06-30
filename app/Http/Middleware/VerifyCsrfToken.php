<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhook untuk Node.js (incoming messages)
        'wa-webhook/incoming',
        'wa-webhook/*',
        
        // API endpoints (jika ada)
        'api/*',
        
        // Tambahkan route lain yang perlu exclude CSRF di sini
    ];
}