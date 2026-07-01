<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\Log;

class ApprovalReplyParser
{
    public function parse(?string $fromNumber, string $message): ?array
    {
        if (!$fromNumber) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', strtoupper($message)));

        if (!preg_match('/^(YES|NO)\s+(\d{6})$/', $text, $matches)) {
            return null;
        }

        return [
            'decision' => $matches[1],
            'code' => $matches[2],
            'from_number' => $fromNumber,
        ];
    }

    public function process(?string $fromNumber, string $message): ?array
    {
        $parsed = $this->parse($fromNumber, $message);

        if (!$parsed) {
            return null;
        }

        $decision = $parsed['decision'];
        $code = $parsed['code'];

        $approval = ApprovalRequest::where('code', $code)
            ->where('to_number', $fromNumber)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$approval) {
            Log::warning('Approval reply ignored: no matching pending approval', [
                'from_number' => $fromNumber,
                'code' => $code,
                'decision' => $decision,
                'reason' => 'not_found_or_already_processed',
            ]);

            return [
                'matched' => false,
                'reason' => 'not_found',
                'code' => $code,
                'decision' => $decision,
            ];
        }

        if ($approval->expires_at->isPast()) {
            $approval->update(['status' => 'expired']);

            Log::warning('Approval reply ignored: approval expired', [
                'approval_id' => $approval->approval_id,
                'from_number' => $fromNumber,
                'code' => $code,
            ]);

            return [
                'matched' => true,
                'approval_id' => $approval->approval_id,
                'status' => 'expired',
                'code' => $code,
            ];
        }

        $status = $decision === 'YES' ? 'approved' : 'rejected';
        $approval->update([
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'rejected_at' => $status === 'rejected' ? now() : null,
        ]);

        Log::info('Approval reply processed', [
            'approval_id' => $approval->approval_id,
            'from_number' => $fromNumber,
            'code' => $code,
            'status' => $status,
        ]);

        return [
            'matched' => true,
            'approval_id' => $approval->approval_id,
            'status' => $status,
            'code' => $code,
        ];
    }
}
