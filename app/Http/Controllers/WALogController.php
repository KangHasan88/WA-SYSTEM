<?php

namespace App\Http\Controllers;

use App\Models\WALog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WALogController extends Controller
{
    public function getData(Request $request)
    {
        try {
            $query = WALog::query();
            
            if ($request->number) {
                $query->where('number', 'like', '%' . $request->number . '%');
            }
            
            if ($request->status && $request->status !== 'All') {
                $query->where('status', $request->status);
            }
            
            if ($request->date_range) {
                $dates = explode(' to ', $request->date_range);
                if (count($dates) == 2) {
                    $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
                } elseif (count($dates) == 1) {
                    $query->whereDate('created_at', $dates[0]);
                }
            }
            
            $logs = $query->orderBy('created_at', 'desc')->paginate(50);
            
            return response()->json([
                'success' => true,
                'logs' => $logs->items(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage()
            ]);
            
        } catch (\Exception $e) {
            Log::error('WALog getData error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => [],
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1
            ], 500);
        }
    }
    
    public function store(Request $request)
{
    try {
        $log = WALog::create([
            'number' => $request->number,
            'message' => $request->message,
            'title' => $request->title,
            'image_url' => $request->image_url,  // <-- TAMBAHKAN INI
            'status' => $request->status,
            'response' => $request->response
        ]);
        
        return response()->json([
            'success' => true, 
            'log' => $log
        ]);
        
    } catch (\Exception $e) {
        Log::error('WALog store error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false, 
            'error' => $e->getMessage()
        ], 500);
    }
}
}