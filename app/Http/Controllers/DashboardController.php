<?php

namespace App\Http\Controllers;

use App\Models\WALog;
use App\Models\WASchedule;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Total stats
        $totalMessages = WALog::count();
        $successMessages = WALog::where('status', 'success')->count();
        $failedMessages = WALog::where('status', 'error')->count();
        $successRate = $totalMessages > 0 ? round(($successMessages / $totalMessages) * 100, 1) : 0;
        
        // Today's stats
        $todayMessages = WALog::whereDate('created_at', today())->count();
        $todaySuccess = WALog::whereDate('created_at', today())->where('status', 'success')->count();
        
        // This week stats
        $weekMessages = WALog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        // This month stats
        $monthMessages = WALog::whereMonth('created_at', now()->month)->count();
        
        // Groups & Contacts
        $totalGroups = ContactGroup::count();
        $totalContacts = \App\Models\Contact::count();
        
        // Pending schedules
        $pendingSchedules = WASchedule::where('status', 'pending')->where('scheduled_at', '>', now())->count();
        
        // Last 7 days chart data
        $chartData = $this->getLast7DaysData();
        
        // Top 10 recent logs
        $recentLogs = WALog::orderBy('created_at', 'desc')->limit(10)->get();
        
        // Status distribution
        $statusDistribution = [
            'success' => $successMessages,
            'error' => $failedMessages,
            'invalid' => WALog::where('status', 'invalid')->count(),
            'pending' => WALog::where('status', 'pending')->count(),
        ];
        
        return view('dashboard.index', compact(
            'totalMessages',
            'successMessages',
            'failedMessages',
            'successRate',
            'todayMessages',
            'todaySuccess',
            'weekMessages',
            'monthMessages',
            'totalGroups',
            'totalContacts',
            'pendingSchedules',
            'chartData',
            'recentLogs',
            'statusDistribution'
        ));
    }
    
    private function getLast7DaysData()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayName = $date->format('D');
            
            $total = WALog::whereDate('created_at', $date)->count();
            $success = WALog::whereDate('created_at', $date)->where('status', 'success')->count();
            
            $data['labels'][] = $dayName;
            $data['total'][] = $total;
            $data['success'][] = $success;
        }
        return $data;
    }
}