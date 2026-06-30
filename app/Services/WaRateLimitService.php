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
        ?int $apiMessageRequestId = null
    ): array {
        $slot = $this->reserveSlot();

        SendWAMessageJob::dispatch($number, $message, $imageUrl, $title, $apiMessageRequestId)
            ->delay(now()->addSeconds($slot['delay_seconds']));

        return $slot;
    }

    public function estimateCompletionMinutes(int $recipientCount): int
    {
        if ($recipientCount <= 0) {
            return 0;
        }

        $hours = intdiv(max(0, $recipientCount - 1), self::MAX_PER_HOUR);
        $position = (($recipientCount - 1) % self::MAX_PER_HOUR);
        $seconds = ($hours * 3600) + ($position * self::BASE_INTERVAL_SECONDS) + self::JITTER_MAX_SECONDS;

        return max(1, (int) ceil($seconds / 60));
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
        $now = CarbonImmutable::instance(now());

        for ($hourOffset = 0; $hourOffset < 72; $hourOffset++) {
            $hourStart = $now->startOfHour()->addHours($hourOffset);
            $key = 'wa_send_slots:' . $hourStart->format('YmdH');
            $count = Cache::increment($key);

            if ($count === 1) {
                Cache::put($key, 1, now()->addDays(3));
            }

            if ($count > self::MAX_PER_HOUR) {
                continue;
            }

            $jitter = random_int(self::JITTER_MIN_SECONDS, self::JITTER_MAX_SECONDS);
            $scheduledAt = $hourStart
                ->addSeconds(($count - 1) * self::BASE_INTERVAL_SECONDS)
                ->addSeconds($jitter);

            if ($scheduledAt->lessThan($now->addSeconds(5))) {
                $scheduledAt = $now->addSeconds(random_int(5, 20));
            }

            return [
                'hour_key' => $key,
                'position' => $count,
                'scheduled_at' => $scheduledAt->toISOString(),
                'delay_seconds' => (int) ceil(max(0, $scheduledAt->diffInSeconds($now, false) * -1)),
                'max_per_hour' => self::MAX_PER_HOUR,
            ];
        }

        throw new \RuntimeException('Unable to reserve WhatsApp send slot within 72 hours.');
    }
}
