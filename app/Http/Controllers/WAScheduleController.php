<?php

namespace App\Http\Controllers;

use App\Models\WASchedule;
use App\Services\PhoneNumberSanitizer;
use App\Services\WaRateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WAScheduleController extends Controller
{
    /**
     * Display a listing of schedules (Web View)
     */
    public function index()
    {
        $schedules = WASchedule::orderBy('scheduled_at', 'desc')->paginate(20);
        return view('wa-schedule.index', compact('schedules'));
    }
    
    /**
     * Store a newly created schedule (API)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:100',
            'message' => 'required|string|max:2000',
            'numbers' => 'required|array|min:1|max:500',
            'image_url' => 'nullable|url',
            'scheduled_at' => 'required|date|after:now',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $sanitizer = app(PhoneNumberSanitizer::class);
        $normalized = $sanitizer->normalizeMany($request->numbers);

        if (! empty($normalized['invalid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ada nomor invalid.',
                'errors' => [
                    'numbers' => [
                        'invalid' => array_slice($normalized['invalid'], 0, 10),
                        'rule' => PhoneNumberSanitizer::INVALID_REASON,
                    ],
                ],
            ], 422);
        }

        $filtered = $sanitizer->excludeBlockedContacts($normalized['numbers']);

        if ($filtered['numbers'] === []) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada nomor aktif yang bisa dijadwalkan.',
            ], 422);
        }
        
        $rateLimiter = app(WaRateLimitService::class);
        $campaignPlan = $rateLimiter->buildCampaignPlan(count($filtered['numbers']), $request->date('scheduled_at'));

        $schedule = WASchedule::create([
            'title' => $request->title,
            'message' => $request->message,
            'image_url' => $request->image_url,
            'numbers' => $filtered['numbers'],
            'campaign_plan' => $campaignPlan,
            'total_numbers' => count($filtered['numbers']),
            'next_number_index' => 0,
            'dispatched_count' => 0,
            'scheduled_at' => $request->scheduled_at,
            'next_dispatch_at' => $request->scheduled_at,
            'status' => 'pending',
            'sent_count' => 0,
            'failed_count' => 0,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dijadwalkan',
            'schedule' => $schedule,
            'campaign_plan' => $campaignPlan,
            'skipped' => [
                'duplicates' => $normalized['duplicates'],
                'blocked' => count($filtered['blocked']),
            ],
        ]);
    }

    /**
     * Preview campaign duration before creating schedule.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numbers' => 'nullable|array|max:500',
            'recipient_count' => 'nullable|integer|min:1|max:500',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $recipientCount = (int) $request->input('recipient_count', 0);
        $skipped = ['duplicates' => 0, 'blocked' => 0, 'invalid' => 0];

        if ($request->has('numbers')) {
            $sanitizer = app(PhoneNumberSanitizer::class);
            $normalized = $sanitizer->normalizeMany($request->input('numbers', []));
            $filtered = $sanitizer->excludeBlockedContacts($normalized['numbers']);
            $recipientCount = count($filtered['numbers']);
            $skipped = [
                'duplicates' => $normalized['duplicates'],
                'blocked' => count($filtered['blocked']),
                'invalid' => count($normalized['invalid']),
            ];
        }

        if ($recipientCount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal 1 penerima aktif diperlukan untuk preview.',
            ], 422);
        }

        $plan = app(WaRateLimitService::class)->buildCampaignPlan(
            $recipientCount,
            $request->date('scheduled_at') ?: now()
        );

        return response()->json([
            'success' => true,
            'campaign_plan' => $plan,
            'skipped' => $skipped,
        ]);
    }
    
    /**
     * Display the specified schedule (API)
     */
    public function show($id)
    {
        $schedule = WASchedule::findOrFail($id);
        return response()->json($schedule);
    }
    
    /**
     * Cancel the specified schedule
     */
    public function cancel($id)
    {
        $schedule = WASchedule::findOrFail($id);
        
        if (in_array($schedule->status, ['pending', 'processing', 'paused'], true)) {
            $schedule->status = 'cancelled';
            $schedule->cancelled_at = now();
            $schedule->save();
            return response()->json(['success' => true, 'message' => 'Jadwal dibatalkan']);
        }
        
        return response()->json([
            'success' => false, 
            'message' => 'Tidak dapat membatalkan jadwal yang sudah diproses'
        ], 422);
    }

    public function pause($id)
    {
        $schedule = WASchedule::findOrFail($id);

        if (! in_array($schedule->status, ['pending', 'processing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya jadwal pending/processing yang bisa di-pause.',
            ], 422);
        }

        $schedule->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Campaign di-pause']);
    }

    public function resume($id)
    {
        $schedule = WASchedule::findOrFail($id);

        if ($schedule->status !== 'paused') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya jadwal paused yang bisa dilanjutkan.',
            ], 422);
        }

        $schedule->update([
            'status' => $schedule->next_number_index > 0 ? 'processing' : 'pending',
            'next_dispatch_at' => now(),
            'paused_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Campaign dilanjutkan']);
    }
    
    /**
     * Remove the specified schedule
     */
    public function destroy($id)
    {
        $schedule = WASchedule::findOrFail($id);
        $schedule->delete();
        
        return response()->json(['success' => true, 'message' => 'Jadwal dihapus']);
    }
    
    /**
     * Get schedules as JSON (for API)
     */
    public function getSchedulesJson(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $schedules = WASchedule::orderBy('scheduled_at', 'desc')->paginate($perPage);
        return response()->json($schedules);
    }
}
