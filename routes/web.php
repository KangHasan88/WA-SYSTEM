<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WABlastController;
use App\Http\Controllers\WALogController;
use App\Http\Controllers\ContactGroupController;
use App\Http\Controllers\WAScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WaInboxController;
use App\Http\Controllers\WaTemplateController;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('wa-blast.index');
});

// ============================================
// DASHBOARD ROUTES
// ============================================
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// ============================================
// WA BLAST ROUTES
// ============================================
Route::get('/wa-blast', [WABlastController::class, 'index'])->name('wa-blast.index');
Route::post('/wa-blast/send', [WABlastController::class, 'send'])->name('wa-blast.send');

// ============================================
// WA LOGS ROUTES
// ============================================
Route::get('/wa-logs/data', [WALogController::class, 'getData'])->name('wa-logs.data');
Route::post('/wa-logs/store', [WALogController::class, 'store'])->name('wa-logs.store');

// ============================================
// WA INBOX ROUTES (Web UI)
// ============================================
Route::prefix('wa-inbox')->name('wa-inbox.')->group(function () {
    Route::get('/', [WaInboxController::class, 'index'])->name('index');
    Route::get('/conversation/{number}', [WaInboxController::class, 'conversation'])->name('conversation');
    Route::delete('/{id}', [WaInboxController::class, 'destroy'])->name('destroy');
});

// ============================================
// WA INBOX API ROUTES (for AJAX)
// ============================================
Route::prefix('wa-inbox/api')->name('wa-inbox.api.')->group(function () {
    Route::get('/conversations', [WaInboxController::class, 'apiConversations'])->name('conversations');
    Route::get('/messages/{number}', [WaInboxController::class, 'apiMessages'])->name('messages');
    Route::post('/reply/{number}', [WaInboxController::class, 'reply'])->name('reply');
    Route::post('/mark-read/{number}', [WaInboxController::class, 'markAsRead'])->name('mark-read');
    
    // ============================================
    // SALES PIPELINE ROUTES (TAMBAHKAN INI)
    // ============================================
    Route::post('/lead-status/{id}', [WaInboxController::class, 'updateLeadStatus']);
    Route::post('/lead-notes/{id}', [WaInboxController::class, 'updateLeadNotes']);
    Route::post('/follow-up/{id}', [WaInboxController::class, 'updateFollowUp']);
    Route::get('/follow-ups/today', [WaInboxController::class, 'getTodayFollowUps']);
    Route::post('/follow-up/done/{id}', [WaInboxController::class, 'markFollowUpDone']);
    Route::get('/lead-statuses', [WaInboxController::class, 'getLeadStatusOptions']);
});

// ============================================
// WA INBOX MEDIA ROUTES (Download file)
// ============================================
Route::get('/wa-inbox/media/download/{id}', [WaInboxController::class, 'downloadMedia'])->name('wa-inbox.media.download');

// ============================================
// WA WEBHOOK ROUTES (for Node.js)
// ============================================
Route::post('/wa-webhook/incoming', [WaInboxController::class, 'webhook'])->name('webhook.incoming');

// ============================================
// WA TEMPLATE ROUTES
// ============================================
Route::prefix('wa-templates')->name('wa-templates.')->group(function () {
    Route::get('/', [WaTemplateController::class, 'index'])->name('index');
    Route::post('/', [WaTemplateController::class, 'store'])->name('store');
    Route::put('/{id}', [WaTemplateController::class, 'update'])->name('update');
    Route::delete('/{id}', [WaTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle', [WaTemplateController::class, 'toggle'])->name('toggle');
    Route::get('/list', [WaTemplateController::class, 'getTemplates'])->name('list');
    Route::get('/{id}', [WaTemplateController::class, 'show'])->name('show');
    Route::post('/preview', [WaTemplateController::class, 'preview'])->name('preview');
});

// ============================================
// PROXY UNTUK NODE.JS WA SERVICE
// ============================================
Route::any('/wa-proxy/{path?}', function ($path = '') {
    $nodeUrl = 'http://127.0.0.1:7070/' . ltrim($path, '/');
    
    try {
        $startTime = microtime(true);
        
        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => false
        ])->send(request()->method(), $nodeUrl, [
            'query' => request()->query(),
            'json' => request()->json()->all() ?? request()->all(),
        ]);
        
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $body = $response->body();
        $contentType = $response->header('Content-Type');
        $data = $response->json();
        
        if ($path === 'wa-status' && is_array($data)) {
            $data['proxy_used'] = true;
            $data['proxy_response_time_ms'] = $responseTime;
            $data['backend_url'] = 'http://127.0.0.1:7070';
            $data['node_running'] = true;
            return response()->json($data, $response->status())
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
        
        return response($body, $response->status())
            ->header('Content-Type', $contentType ?: 'application/json')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        if ($path === 'wa-status') {
            return response()->json([
                'connected' => false,
                'status' => 'disconnected',
                'node_running' => false,
                'message' => 'WhatsApp service is starting up. Please wait...',
                'error' => 'Connection refused. Node.js server not running on port 7070.',
                'backend_url' => 'http://127.0.0.1:7070',
                'timestamp' => now()->toISOString()
            ], 200);
        }
        
        return response()->json([
            'connected' => false,
            'status' => 'disconnected',
            'error' => 'Connection refused: ' . $e->getMessage(),
            'message' => 'WhatsApp Node.js server belum berjalan.',
            'timestamp' => now()->toISOString()
        ], 503);
        
    } catch (\Exception $e) {
        if ($path === 'wa-status') {
            return response()->json([
                'connected' => false,
                'status' => 'disconnected',
                'node_running' => false,
                'error' => $e->getMessage(),
                'message' => 'Error connecting to WhatsApp service',
                'timestamp' => now()->toISOString()
            ], 200);
        }
        
        return response()->json([
            'connected' => false,
            'status' => 'disconnected',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->where('path', '.*');

// ============================================
// CORS HANDLER
// ============================================
Route::options('/wa-proxy/{path?}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->header('Access-Control-Max-Age', '86400');
})->where('path', '.*');

// ============================================
// NODE.JS MANAGER ROUTES
// ============================================
Route::get('/wa-node/status', [WABlastController::class, 'nodeStatus']);
Route::post('/wa-node/start', [WABlastController::class, 'nodeStart']);
Route::post('/wa-node/stop', [WABlastController::class, 'nodeStop']);
Route::post('/wa-node/restart', [WABlastController::class, 'nodeRestart']);
Route::post('/wa-node/force-reset', [WABlastController::class, 'nodeForceReset']);

// ============================================
// CONTACT GROUP ROUTES
// ============================================
Route::prefix('contact-groups')->name('contact-groups.')->group(function () {
    Route::get('/', [ContactGroupController::class, 'index'])->name('index');
    Route::post('/group', [ContactGroupController::class, 'storeGroup'])->name('group.store');
    Route::put('/group/{id}', [ContactGroupController::class, 'updateGroup'])->name('group.update');
    Route::delete('/group/{id}', [ContactGroupController::class, 'deleteGroup'])->name('group.delete');
    
    Route::post('/contact', [ContactGroupController::class, 'storeContact'])->name('contact.store');
    Route::put('/contact/{id}', [ContactGroupController::class, 'updateContact'])->name('contact.update');
    Route::delete('/contact/{id}', [ContactGroupController::class, 'deleteContact'])->name('contact.delete');
    
    Route::post('/import', [ContactGroupController::class, 'importContacts'])->name('import');
    Route::get('/export/{id}', [ContactGroupController::class, 'exportContacts'])->name('export');
    Route::get('/list', [ContactGroupController::class, 'getGroups'])->name('list');
    Route::get('/contacts/{groupId}', [ContactGroupController::class, 'getContacts'])->name('contacts');
});

// ============================================
// WA SCHEDULE ROUTES
// ============================================
Route::prefix('wa-schedule')->name('wa-schedule.')->group(function () {
    Route::post('/', [WAScheduleController::class, 'store'])->name('store');
    Route::get('/', [WAScheduleController::class, 'index'])->name('index');
    Route::get('/{id}', [WAScheduleController::class, 'show'])->name('show');
    Route::delete('/{id}', [WAScheduleController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/cancel', [WAScheduleController::class, 'cancel'])->name('cancel');
});
