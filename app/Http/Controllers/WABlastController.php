<?php

namespace App\Http\Controllers;

use App\Services\WaRateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WABlastController extends Controller
{
    public function index()
    {
        return view('wa-blast');
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numbers' => 'required|string',
            'message' => 'required|string|min:1|max:2000',
            'image_url' => 'nullable|url',
        ], [
            'numbers.required' => 'Nomor WA tidak boleh kosong',
            'message.required' => 'Pesan tidak boleh kosong',
            'message.max' => 'Pesan maksimal 2000 karakter',
            'image_url.url' => 'URL gambar tidak valid',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $numbers = preg_split('/\r\n|\r|\n/', $request->numbers);
        
        $numbers = array_filter(array_map(function ($num) {
            return trim(preg_replace('/\s+/', '', $num));
        }, $numbers));

        if (empty($numbers)) {
            return back()->with('error', 'Minimal 1 nomor WhatsApp harus diisi');
        }

        if (count($numbers) > 500) {
            return back()->with('error', 'Maksimal 500 nomor per blast untuk menghindari spam');
        }

        $totalDispatched = 0;
        $rateLimiter = app(WaRateLimitService::class);
        
        foreach ($numbers as $index => $number) {
            $rateLimiter->dispatchMessage($number, $request->message, $request->image_url);
            $totalDispatched++;
        }

        $message = "✅ {$totalDispatched} pesan sedang diproses.\n";
        $message .= "⏱️ Estimasi selesai: " . $rateLimiter->estimateCompletionMinutes($totalDispatched) . " menit\n";
        $message .= "🛡️ Rate limit aktif: maksimal " . WaRateLimitService::MAX_PER_HOUR . " penerima/jam.\n";

        return back()->with('success', $message);
    }

    // ============================================
    // NODE.JS MANAGER METHODS
    // ============================================

    /**
     * Cek status Node.js service
     */
    public function nodeStatus()
    {
        try {
            // Cek apakah ada proses yang listen di port 7070
            $portResult = Process::run('ss -tlnp | grep :7070');
            $isRunning = !empty(trim($portResult->output()));
            
            // Backup: cek apakah ada proses node sender.js
            if (!$isRunning) {
                $processResult = Process::run('pgrep -f "node.*sender.js"');
                $isRunning = !empty(trim($processResult->output()));
            }
            
            return response()->json([
                'success' => true,
                'running' => $isRunning,
                'message' => $isRunning ? 'Node.js is running' : 'Node.js is stopped'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Node.js status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'running' => false,
                'message' => 'Error checking status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force Reset Node.js service
     */
    public function nodeForceReset(Request $request)
    {
        $resetSession = $request->input('reset_session', false);
        
        try {
            Log::info("Node.js force reset initiated. Reset session: " . ($resetSession ? 'Yes' : 'No'));
            
            // Panggil script wrapper
            if ($resetSession) {
                Process::run('sudo /usr/local/bin/wa-start reset');
            } else {
                Process::run('sudo /usr/local/bin/wa-start');
            }
            
            sleep(5);
            
            // Verifikasi
            $portResult = Process::run('ss -tlnp | grep :7070');
            $success = !empty(trim($portResult->output()));
            
            Log::info("Node.js force reset completed. Success: " . ($success ? 'Yes' : 'No'));
            
            return response()->json([
                'success' => $success,
                'message' => $success 
                    ? 'Force reset successful! QR code will appear. Please refresh the page.'
                    : 'Force reset completed. Please wait a moment and refresh the page.',
                'reset_session' => $resetSession
            ]);
            
        } catch (\Exception $e) {
            Log::error('Node.js force reset error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'message' => 'Force reset command executed. Page will refresh.'
            ]);
        }
    }
}
