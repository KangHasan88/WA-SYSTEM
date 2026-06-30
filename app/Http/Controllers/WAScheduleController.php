<?php

namespace App\Http\Controllers;

use App\Models\WASchedule;
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
        
        $schedule = WASchedule::create([
            'title' => $request->title,
            'message' => $request->message,
            'image_url' => $request->image_url,
            'numbers' => $request->numbers,
            'total_numbers' => count($request->numbers),
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
            'sent_count' => 0,
            'failed_count' => 0,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dijadwalkan',
            'schedule' => $schedule
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
        
        if ($schedule->status === 'pending') {
            $schedule->status = 'cancelled';
            $schedule->save();
            return response()->json(['success' => true, 'message' => 'Jadwal dibatalkan']);
        }
        
        return response()->json([
            'success' => false, 
            'message' => 'Tidak dapat membatalkan jadwal yang sudah diproses'
        ], 422);
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