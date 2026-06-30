<?php

namespace App\Http\Controllers;

use App\Models\ApiMessageRequest;
use App\Models\WALog;
use App\Services\WaRateLimitService;
use Illuminate\Http\Request;

class DeliveryReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status', 'all'),
            'source' => trim((string) $request->query('source', '')),
            'number' => trim((string) $request->query('number', '')),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $messages = ApiMessageRequest::query()
            ->with('apiClient')
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['source'] !== '', fn ($query) => $query->where('source', 'like', '%' . $filters['source'] . '%'))
            ->when($filters['number'] !== '', fn ($query) => $query->where('to_number', 'like', '%' . preg_replace('/\D/', '', $filters['number']) . '%'))
            ->when($filters['date_from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate(25)
            ->through(function (ApiMessageRequest $message) {
                $message->latest_wa_log = WALog::where('number', $message->to_number)
                    ->where('message', $message->message)
                    ->latest('id')
                    ->first();

                return $message;
            })
            ->withQueryString();

        $summary = [
            'total' => ApiMessageRequest::count(),
            'queued' => ApiMessageRequest::where('status', 'queued')->count(),
            'sending' => ApiMessageRequest::where('status', 'sending')->count(),
            'success' => ApiMessageRequest::where('status', 'success')->count(),
            'failed' => ApiMessageRequest::whereIn('status', ['failed', 'invalid'])->count(),
        ];

        return view('delivery-reports.index', compact('messages', 'filters', 'summary'));
    }

    public function retry(ApiMessageRequest $message)
    {
        if (!in_array($message->status, ['failed', 'invalid'], true)) {
            return redirect()
                ->route('delivery-reports.index')
                ->with('error', 'Retry hanya tersedia untuk status failed atau invalid.');
        }

        $slot = app(WaRateLimitService::class)->dispatchMessage(
            $message->to_number,
            $message->message,
            $message->image_url,
            $message->title,
            $message->id
        );

        $message->forceFill([
            'status' => 'queued',
            'queued_at' => now(),
            'reference_type' => $message->reference_type ?: 'manual-retry',
            'updated_at' => now(),
        ])->save();

        return redirect()
            ->route('delivery-reports.index')
            ->with('success', 'Pesan gagal sudah dijadwalkan ulang lewat rate limiter. Jadwal: ' . $slot['scheduled_at']);
    }
}
