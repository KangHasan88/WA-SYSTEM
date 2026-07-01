<?php

namespace App\Jobs;

use App\Models\ApiMessageRequest;
use App\Models\WASchedule;
use App\Models\WALog;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWAMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $number;
    protected $message;
    protected $imageUrl;
    protected $title;
    protected $apiMessageRequestId;
    protected $waScheduleId;
    public $tries = 5;
    public $backoff = [5, 15, 30, 60, 120];
    public $timeout = 120;

    public function __construct($number, $message, $imageUrl = null, $title = null, $apiMessageRequestId = null, $waScheduleId = null)
    {
        $this->number = $number;
        $this->message = $message;
        $this->imageUrl = $imageUrl;
        $this->title = $title;
        $this->apiMessageRequestId = $apiMessageRequestId;
        $this->waScheduleId = $waScheduleId;
    }

    public function handle(): void
    {
        if (! $this->scheduleAllowsSend()) {
            return;
        }

        $this->updateApiMessageRequest('sending');

        Log::info("==========================================");
        Log::info("SendWAMessageJob - START DEBUG");
        Log::info("==========================================");
        Log::info("Original Number: " . $this->number);
        Log::info("Original Message Length: " . strlen($this->message));
        Log::info("Message Preview: " . substr($this->message, 0, 100));
        Log::info("Image URL: " . ($this->imageUrl ?? 'null'));
        Log::info("Title: " . ($this->title ?? 'null'));
        Log::info("Attempt: " . $this->attempts());

        // Format number
        $formattedNumber = $this->formatNumber($this->number);
        Log::info("Formatted Number: " . $formattedNumber);

        // Cek apakah number sudah benar
        if (strlen($formattedNumber) < 10) {
            Log::error("Number too short after formatting: " . $formattedNumber);
            $this->updateApiMessageRequest('invalid');
            
            WALog::create([
                'number' => $this->number,
                'message' => $this->message,
                'title' => $this->title,
                'image_url' => $this->imageUrl,
                'status' => 'invalid',
                'response' => json_encode(['error' => 'Invalid number format', 'original' => $this->number, 'formatted' => $formattedNumber])
            ]);
            return;
        }

        try {
            // PERBAIKAN: Gunakan localhost dengan method POST
            $nodeUrl = 'http://127.0.0.1:7070/send';
            Log::info("Sending to Node.js URL: " . $nodeUrl);
            
            $payload = [
                'number' => $formattedNumber,
                'message' => $this->message,
            ];
            
            if ($this->imageUrl) {
                $payload['image_url'] = $this->imageUrl;
                Log::info("With image: " . $this->imageUrl);
            }
            
            Log::info("Payload: " . json_encode($payload));
            
            // PASTIKAN MENGGUNAKAN METHOD POST
            $response = Http::timeout(60)->post($nodeUrl, $payload);
            
            Log::info("HTTP Status: " . $response->status());
            Log::info("Response Body: " . $response->body());
            
            $json = $response->json();
            Log::info("Response JSON: " . json_encode($json));

            // Cek apakah WhatsApp belum ready
            if (isset($json['status']) && $json['status'] === 'error') {
                $errorMsg = $json['error'] ?? '';
                Log::warning("Error from Node.js: " . $errorMsg);
                
                if ((str_contains($errorMsg, 'belum ready') || 
                     str_contains($errorMsg, 'not ready') ||
                     str_contains($errorMsg, 'not connected') ||
                     str_contains($errorMsg, 'belum siap')) && 
                    $this->attempts() < $this->tries) {
                    
                    Log::warning("WhatsApp belum ready, retry ke-" . $this->attempts(), [
                        'number' => $this->number,
                        'error' => $errorMsg
                    ]);
                    $this->updateApiMessageRequest('queued');
                    $this->release(10);
                    return;
                }
            }

            $apiStatus = (($json['status'] ?? 'error') === 'success') ? 'success' : 'failed';
            $this->updateApiMessageRequest($apiStatus);

            // Simpan log ke database
            $log = WALog::create([
                'number' => $formattedNumber,
                'message' => $this->message,
                'title' => $this->title,
                'image_url' => $this->imageUrl,
                'status' => $json['status'] ?? 'error',
                'response' => json_encode($json),
            ]);
            
            Log::info("Log saved to database. ID: " . ($log->id ?? 'null'));
            Log::info("SendWAMessageJob - SUCCESS");
            Log::info("==========================================");

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("ConnectionException: " . $e->getMessage());
            Log::error("Could not connect to Node.js on port 7070");
            
            WALog::create([
                'number' => $formattedNumber,
                'message' => $this->message,
                'title' => $this->title,
                'image_url' => $this->imageUrl,
                'status' => 'error',
                'response' => json_encode(['error' => 'Connection refused: ' . $e->getMessage(), 'nodejs_down' => true])
            ]);
            
            if ($this->attempts() < $this->tries) {
                Log::info("Retry attempt " . ($this->attempts() + 1) . " in 30 seconds");
                $this->updateApiMessageRequest('queued');
                $this->release(30);
                return;
            }
            
            $this->updateApiMessageRequest('failed');
            Log::error("Max retries reached. Giving up.");
            Log::info("==========================================");
            throw $e;
            
        } catch (\Exception $e) {
            Log::error("EXCEPTION: " . $e->getMessage());
            Log::error("Exception Trace: " . $e->getTraceAsString());
            
            WALog::create([
                'number' => $formattedNumber,
                'message' => $this->message,
                'title' => $this->title,
                'image_url' => $this->imageUrl,
                'status' => 'error',
                'response' => json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()])
            ]);

            if ($this->attempts() < $this->tries) {
                Log::info("Retry attempt " . ($this->attempts() + 1) . " in 30 seconds");
                $this->updateApiMessageRequest('queued');
                $this->release(30);
                return;
            }
            
            $this->updateApiMessageRequest('failed');
            Log::error("Max retries reached. Giving up.");
            Log::info("==========================================");
            throw $e;
        }
    }

    private function updateApiMessageRequest(string $status): void
    {
        if (!$this->apiMessageRequestId) {
            return;
        }

        ApiMessageRequest::whereKey($this->apiMessageRequestId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    private function scheduleAllowsSend(): bool
    {
        if (! $this->waScheduleId) {
            return true;
        }

        $schedule = WASchedule::find($this->waScheduleId);

        if (! $schedule) {
            Log::warning("SendWAMessageJob skipped because schedule was deleted", [
                'schedule_id' => $this->waScheduleId,
                'number' => $this->number,
            ]);
            return false;
        }

        if ($schedule->status === 'paused') {
            Log::info("SendWAMessageJob released because schedule is paused", [
                'schedule_id' => $this->waScheduleId,
                'number' => $this->number,
            ]);
            $this->release(300);
            return false;
        }

        if (in_array($schedule->status, ['cancelled', 'failed'], true)) {
            Log::warning("SendWAMessageJob cancelled before send", [
                'schedule_id' => $this->waScheduleId,
                'number' => $this->number,
                'status' => $schedule->status,
            ]);

            WALog::create([
                'number' => $this->number,
                'message' => $this->message,
                'title' => $this->title,
                'image_url' => $this->imageUrl,
                'status' => 'cancelled',
                'response' => json_encode([
                    'reason' => 'Campaign status is ' . $schedule->status,
                    'schedule_id' => $this->waScheduleId,
                ]),
            ]);

            return false;
        }

        return true;
    }

    private function formatNumber($number): string
    {
        Log::info("Formatting number: " . $number);
        
        // Bersihkan dari karakter non-digit
        $number = preg_replace('/\D/', '', $number);
        Log::info("After cleaning: " . $number);
        
        // Jika sudah dimulai dengan 62
        if (substr($number, 0, 2) === '62') {
            // Pastikan tidak double 62
            while (substr($number, 0, 4) === '6262') {
                $number = '62' . substr($number, 4);
                Log::info("Fixed double 62: " . $number);
            }
            Log::info("Final (already 62): " . $number);
            return $number;
        }
        
        // Jika dimulai dengan 0, ganti jadi 62
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
            Log::info("Converted 0 to 62: " . $number);
            return $number;
        }
        
        // Jika tidak dimulai dengan 62 atau 0, tambahkan 62
        $number = '62' . $number;
        Log::info("Added 62 prefix: " . $number);
        
        return $number;
    }
}
