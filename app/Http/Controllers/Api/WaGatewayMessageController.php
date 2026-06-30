<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiMessageRequest;
use App\Services\WaRateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WaGatewayMessageController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => ['required', 'string', 'max:32'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'title' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'source' => ['nullable', 'string', 'max:80'],
            'reference_type' => ['nullable', 'string', 'max:80'],
            'reference_id' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', 'in:low,normal,high'],
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
        $messageId = 'wa_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(10));
        $priority = $validated['priority'] ?? 'normal';
        $normalizedNumber = $this->formatNumber($validated['to']);

        $record = ApiMessageRequest::create([
            'message_id' => $messageId,
            'api_client_id' => $client?->id,
            'to_number' => $normalizedNumber,
            'message' => $validated['message'],
            'title' => $validated['title'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'source' => $validated['source'] ?? $client?->slug,
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'priority' => $priority,
            'status' => 'queued',
            'queued_at' => now(),
            'payload' => $validated,
        ]);

        $slot = app(WaRateLimitService::class)->dispatchMessage(
            $normalizedNumber,
            $validated['message'],
            $validated['image_url'] ?? null,
            $validated['title'] ?? null,
            $record->id
        );

        return response()->json([
            'success' => true,
            'message_id' => $record->message_id,
            'status' => $record->status,
            'queued_at' => optional($record->queued_at)->toISOString(),
            'scheduled_at' => $slot['scheduled_at'],
            'rate_limit' => [
                'max_per_hour' => $slot['max_per_hour'],
                'position' => $slot['position'],
            ],
            'source' => $record->source,
            'reference_type' => $record->reference_type,
            'reference_id' => $record->reference_id,
        ], 202);
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
}
