<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaInboxController;

// ============================================
// WEBHOOK ROUTES (No CSRF, No Session)
// ============================================
Route::post('/wa-webhook/incoming', [WaInboxController::class, 'webhook']);