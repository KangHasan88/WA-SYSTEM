<?php

namespace App\Console\Commands;

use App\Models\WASchedule;
use App\Services\PhoneNumberSanitizer;
use App\Services\WaRateLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledMessages extends Command
{
    protected $signature = 'wa:send-scheduled';
    protected $description = 'Send scheduled WhatsApp messages';

    public function handle()
    {
        $this->info('[' . now() . '] Checking for scheduled messages...');
        
        $schedules = WASchedule::pending()->get();
        
        if ($schedules->isEmpty()) {
            $this->info('No pending schedules found.');
            return 0;
        }
        
        $this->info("Found {$schedules->count()} schedule(s) to process.");
        
        foreach ($schedules as $schedule) {
            $this->info("Processing schedule #{$schedule->id} - {$schedule->title}");
            
            $schedule->status = 'processing';
            $schedule->save();
            
            $sanitizer = app(PhoneNumberSanitizer::class);
            $normalized = $sanitizer->normalizeMany($schedule->numbers ?? []);
            $filtered = $sanitizer->excludeBlockedContacts($normalized['numbers']);
            $numbers = $filtered['numbers'];
            $total = count($numbers);
            $sent = 0;
            $failed = count($normalized['invalid']) + count($filtered['blocked']);
            $rateLimiter = app(WaRateLimitService::class);

            if ($normalized['invalid'] !== [] || $filtered['blocked'] !== []) {
                Log::warning("SCHEDULE HYGIENE - Some numbers skipped for schedule #{$schedule->id}", [
                    'invalid_count' => count($normalized['invalid']),
                    'duplicate_count' => $normalized['duplicates'],
                    'blocked_count' => count($filtered['blocked']),
                ]);
            }

            if ($numbers === []) {
                $schedule->sent_count = 0;
                $schedule->failed_count = $failed;
                $schedule->status = 'completed';
                $schedule->save();
                $this->warn("Schedule #{$schedule->id} completed with no active valid numbers.");
                continue;
            }
            
            foreach ($numbers as $index => $number) {
                try {
                    // DEBUG LOG: sebelum dispatch
                    Log::info("SCHEDULE DEBUG - Dispatching job for schedule #{$schedule->id}", [
                        'number' => $number,
                        'title' => $schedule->title,
                        'attempt' => $index + 1
                    ]);
                    
                    $slot = $rateLimiter->dispatchMessage(
                        $number, 
                        $schedule->message, 
                        $schedule->image_url,
                        $schedule->title
                    );
                    
                    $sent++;
                    
                    Log::info("SCHEDULE DEBUG - Job dispatched successfully for schedule #{$schedule->id}", [
                        'number' => $number,
                        'sent_count' => $sent,
                        'scheduled_at' => $slot['scheduled_at'],
                        'rate_position' => $slot['position'],
                    ]);
                    
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("SCHEDULE DEBUG - Failed to dispatch job for schedule #{$schedule->id}", [
                        'number' => $number,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                
                // Update progress setiap 10 nomor
                if (($index + 1) % 10 == 0 || ($index + 1) == $total) {
                    $schedule->sent_count = $sent;
                    $schedule->failed_count = $failed;
                    $schedule->save();
                    $this->info("Progress: {$sent}/{$total} dispatched, {$failed} failed");
                }
            }
            
            // Final update
            $schedule->sent_count = $sent;
            $schedule->failed_count = $failed;
            $schedule->status = 'completed';
            $schedule->save();
            
            $this->info("Schedule #{$schedule->id} completed. Dispatched: {$sent}, Failed: {$failed}");
        }
        
        $this->info('All schedules processed.');
        
        return 0;
    }
}
