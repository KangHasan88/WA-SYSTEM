<?php

namespace App\Services;

use App\Jobs\SendWAMessageJob;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class WaRateLimitService
{
    public const MAX_PER_HOUR = 100;
    public const BASE_INTERVAL_SECONDS = 36;
    public const JITTER_MIN_SECONDS = 0;
    public const JITTER_MAX_SECONDS = 19;

    public function dispatchMessage(
        string $number,
        string $message,
        ?string $imageUrl = null,
        ?string $title = null,
        ?int $apiMessageRequestId = null,
        ?int $waScheduleId = null
    ): array {
        $slot = $this->reserveSlot();

        SendWAMessageJob::dispatch($number, $message, $imageUrl, $title, $apiMessageRequestId, $waScheduleId)
            ->delay(now()->addSeconds($slot['delay_seconds']));

        return $slot;
    }

    public function estimateCompletionMinutes(int $recipientCount): int
    {
        if ($recipientCount <= 0) {
            return 0;
        }

        $seconds = max(0, $recipientCount - 1) * (self::BASE_INTERVAL_SECONDS + self::JITTER_MAX_SECONDS);

        return max(1, (int) ceil($seconds / 60));
    }

    public function buildCampaignPlan(int $recipientCount, ?CarbonInterface $from = null): array
    {
        $from = CarbonImmutable::instance($from ?: now());
        $batchCount = (int) ceil($recipientCount / self::MAX_PER_HOUR);
        $estimatedMinutes = $this->estimateCompletionMinutes($recipientCount);
        $batches = [];

        for ($batch = 0; $batch < $batchCount; $batch++) {
            $startIndex = ($batch * self::MAX_PER_HOUR) + 1;
            $endIndex = min($recipientCount, ($batch + 1) * self::MAX_PER_HOUR);

            $batches[] = [
                'batch' => $batch + 1,
                'start_recipient' => $startIndex,
                'end_recipient' => $endIndex,
                'recipient_count' => ($endIndex - $startIndex) + 1,
                'earliest_dispatch_at' => $from->addHours($batch)->toISOString(),
            ];
        }

        return [
            'recipient_count' => $recipientCount,
            'max_per_hour' => self::MAX_PER_HOUR,
            'batch_count' => $batchCount,
            'interval_seconds_min' => self::BASE_INTERVAL_SECONDS + self::JITTER_MIN_SECONDS,
            'interval_seconds_max' => self::BASE_INTERVAL_SECONDS + self::JITTER_MAX_SECONDS,
            'estimated_minutes' => $estimatedMinutes,
            'estimated_completed_at' => $from->addMinutes($estimatedMinutes)->toISOString(),
            'batches' => $batches,
        ];
    }

    public function previewSchedule(int $recipientCount, ?CarbonInterface $from = null): array
    {
        $from = CarbonImmutable::instance($from ?: now());
        $items = [];

        for ($i = 0; $i < $recipientCount; $i++) {
            $hourOffset = intdiv($i, self::MAX_PER_HOUR);
            $position = $i % self::MAX_PER_HOUR;
            $scheduledAt = $from
                ->startOfHour()
                ->addHours($hourOffset)
                ->addSeconds($position * self::BASE_INTERVAL_SECONDS);

            if ($scheduledAt->lessThan($from)) {
                $scheduledAt = $from->addSeconds(($position * self::BASE_INTERVAL_SECONDS) + 5);
            }

            $items[] = [
                'position' => $position + 1,
                'hour_offset' => $hourOffset,
                'scheduled_at' => $scheduledAt->toISOString(),
                'delay_seconds' => (int) ceil(max(0, $scheduledAt->diffInSeconds($from, false) * -1)),
            ];
        }

        return $items;
    }

    private function reserveSlot(): array
    {
        return Cache::lock('wa_rate_limit_slot_lock', 10)->block(5, function () {
            $now = CarbonImmutable::instance(now());
            $nextAvailableRaw = Cache::get('wa_next_available_send_at');
            $nextAvailable = $nextAvailableRaw ? CarbonImmutable::parse($nextAvailableRaw) : $now->addSeconds(5);

            if ($nextAvailable->lessThan($now->addSeconds(5))) {
                $nextAvailable = $now->addSeconds(5);
            }

            $jitter = random_int(self::JITTER_MIN_SECONDS, self::JITTER_MAX_SECONDS);
            $spacing = self::BASE_INTERVAL_SECONDS + $jitter;
            $followingSlot = $nextAvailable->addSeconds($spacing);

            Cache::put('wa_next_available_send_at', $followingSlot->toISOString(), now()->addDays(7));

            return [
                'hour_key' => 'global-spacing',
                'position' => Cache::increment('wa_send_slot_sequence'),
                'scheduled_at' => $nextAvailable->toISOString(),
                'delay_seconds' => (int) ceil(max(0, $nextAvailable->diffInSeconds($now, false) * -1)),
                'spacing_seconds' => $spacing,
                'max_per_hour' => self::MAX_PER_HOUR,
            ];
        });
    }
}
