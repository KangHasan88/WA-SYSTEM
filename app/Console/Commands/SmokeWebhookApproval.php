<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Services\ApprovalReplyParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmokeWebhookApproval extends Command
{
    protected $signature = 'wa:webhook-smoke {--json : Output JSON}';
    protected $description = 'Smoke test incoming webhook approval parser without sending WhatsApp messages';

    public function handle(): int
    {
        DB::beginTransaction();

        try {
            $parser = app(ApprovalReplyParser::class);
            $number = '6281282277665';
            $otherNumber = '6281111111111';

            $valid = ApprovalRequest::create([
                'approval_id' => 'smoke_' . now()->format('YmdHis') . '_valid',
                'code' => '111222',
                'to_number' => $number,
                'source' => 'smoke',
                'action' => 'Smoke webhook approval valid',
                'risk' => 'low',
                'status' => 'pending',
                'payload' => ['smoke' => true],
                'dry_run' => true,
                'expires_at' => now()->addMinutes(5),
            ]);

            $expired = ApprovalRequest::create([
                'approval_id' => 'smoke_' . now()->format('YmdHis') . '_expired',
                'code' => '333444',
                'to_number' => $number,
                'source' => 'smoke',
                'action' => 'Smoke webhook approval expired',
                'risk' => 'low',
                'status' => 'pending',
                'payload' => ['smoke' => true],
                'dry_run' => true,
                'expires_at' => now()->subMinute(),
            ]);

            $wrongCode = $parser->process($number, 'YES 999999');
            $wrongNumber = $parser->process($otherNumber, 'YES 111222');
            $expiredResult = $parser->process($number, 'YES 333444');
            $firstApproval = $parser->process($number, 'YES 111222');
            $duplicateReplay = $parser->process($number, 'YES 111222');

            $valid->refresh();
            $expired->refresh();

            $checks = [
                'wrong_code_not_matched' => ($wrongCode['matched'] ?? false) === false,
                'wrong_number_not_matched' => ($wrongNumber['matched'] ?? false) === false,
                'expired_marked_expired' => $expired->status === 'expired' && ($expiredResult['status'] ?? null) === 'expired',
                'valid_approved_once' => $valid->status === 'approved' && ($firstApproval['status'] ?? null) === 'approved',
                'duplicate_replay_not_matched' => ($duplicateReplay['matched'] ?? false) === false,
            ];

            $result = [
                'ok' => !in_array(false, $checks, true),
                'checks' => $checks,
            ];
        } finally {
            DB::rollBack();
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($result['checks'] as $name => $passed) {
                $this->line(($passed ? '[OK] ' : '[FAIL] ') . $name);
            }
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
