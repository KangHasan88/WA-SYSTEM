<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\WaRateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WaGatewayApprovalController extends Controller
{
    public function show(string $approvalId)
    {
        $approval = ApprovalRequest::where('approval_id', $approvalId)->first();

        if (!$approval) {
            return response()->json([
                'success' => false,
                'message' => 'Approval request not found.',
            ], 404);
        }

        if ($approval->status === 'pending' && $approval->expires_at->isPast()) {
            $approval->forceFill(['status' => 'expired'])->save();
        }

        return response()->json([
            'success' => true,
            'approval_id' => $approval->approval_id,
            'status' => $approval->status,
            'source' => $approval->source,
            'action' => $approval->action,
            'risk' => $approval->risk,
            'to_number' => $this->maskNumber($approval->to_number),
            'dry_run' => $approval->dry_run,
            'created_at' => optional($approval->created_at)->toISOString(),
            'expires_at' => optional($approval->expires_at)->toISOString(),
            'sent_at' => optional($approval->sent_at)->toISOString(),
            'approved_at' => optional($approval->approved_at)->toISOString(),
            'rejected_at' => optional($approval->rejected_at)->toISOString(),
            'remaining_seconds' => $approval->status === 'pending'
                ? max(0, now()->diffInSeconds($approval->expires_at, false))
                : 0,
        ]);
    }

    public function requestApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => ['required', 'string', 'max:32'],
            'source' => ['nullable', 'string', 'max:80'],
            'action' => ['required', 'string', 'min:3', 'max:1000'],
            'risk' => ['nullable', 'in:low,medium,high'],
            'expires_in_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'payload' => ['nullable', 'array'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $client = $request->attributes->get('api_client');
        $code = $this->generateCode();
        $approvalId = 'apv_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(10));
        $expiresIn = $validated['expires_in_minutes'] ?? 15;
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $toNumber = $this->formatNumber($validated['to']);
        $source = $validated['source'] ?? $client?->slug;
        $risk = $validated['risk'] ?? 'medium';

        $approval = ApprovalRequest::create([
            'approval_id' => $approvalId,
            'code' => $code,
            'api_client_id' => $client?->id,
            'to_number' => $toNumber,
            'source' => $source,
            'action' => $validated['action'],
            'risk' => $risk,
            'status' => 'pending',
            'payload' => $validated['payload'] ?? null,
            'dry_run' => $dryRun,
            'expires_at' => now()->addMinutes($expiresIn),
        ]);

        $message = $this->buildApprovalMessage($approval);
        $slot = null;

        if (!$dryRun) {
            $slot = app(WaRateLimitService::class)->dispatchMessage(
                $toNumber,
                $message,
                null,
                'Approval Request',
            );

            $approval->forceFill(['sent_at' => now()])->save();
        }

        return response()->json([
            'success' => true,
            'approval_id' => $approval->approval_id,
            'code' => $approval->code,
            'status' => $approval->status,
            'risk' => $approval->risk,
            'dry_run' => $approval->dry_run,
            'expires_at' => $approval->expires_at->toISOString(),
            'sent_at' => optional($approval->sent_at)->toISOString(),
            'scheduled_at' => $slot['scheduled_at'] ?? null,
            'reply_format' => [
                'approve' => 'YES ' . $approval->code,
                'reject' => 'NO ' . $approval->code,
            ],
            'message_preview' => $dryRun ? $message : null,
        ], 202);
    }

    private function generateCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (ApprovalRequest::where('code', $code)->where('status', 'pending')->exists());

        return $code;
    }

    private function buildApprovalMessage(ApprovalRequest $approval): string
    {
        return "Approval dibutuhkan\n\n"
            . "Source: {$approval->source}\n"
            . "Action: {$approval->action}\n"
            . "Risk: " . strtoupper($approval->risk) . "\n"
            . "Code: {$approval->code}\n"
            . "Expired: {$approval->expires_at->timezone('Asia/Jakarta')->format('d/m/Y H:i')} WIB\n\n"
            . "Balas:\n"
            . "YES {$approval->code}\n"
            . "atau\n"
            . "NO {$approval->code}";
    }

    private function formatNumber(string $number): string
    {
        $cleanNumber = preg_replace('/\D/', '', $number) ?: '';

        while (str_starts_with($cleanNumber, '6262')) {
            $cleanNumber = '62' . substr($cleanNumber, 4);
        }

        if (str_starts_with($cleanNumber, '0')) {
            return '62' . substr($cleanNumber, 1);
        }

        if (!str_starts_with($cleanNumber, '62')) {
            return '62' . $cleanNumber;
        }

        return $cleanNumber;
    }

    private function maskNumber(string $number): string
    {
        $length = strlen($number);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($number, 0, 4)
            . str_repeat('*', max(0, $length - 7))
            . substr($number, -3);
    }
}
