<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WASchedule extends Model
{
    protected $table = 'wa_schedules';
    
    protected $fillable = [
        'title',
        'message',
        'image_url',
        'numbers',
        'campaign_plan',
        'total_numbers',
        'next_number_index',
        'dispatched_count',
        'scheduled_at',
        'next_dispatch_at',
        'status',
        'sent_count',
        'failed_count',
        'error_message',
        'paused_at',
        'cancelled_at',
        'completed_at',
    ];
    
    protected $casts = [
        'numbers' => 'array',
        'campaign_plan' => 'array',
        'scheduled_at' => 'datetime',
        'next_dispatch_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function getFormattedScheduledAtAttribute()
    {
        return $this->scheduled_at ? $this->scheduled_at->format('d/m/Y H:i') : '-';
    }
    
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge-status info">Pending</span>',
            'processing' => '<span class="badge-status warning">Processing</span>',
            'paused' => '<span class="badge-status warning">Paused</span>',
            'completed' => '<span class="badge-status success">Completed</span>',
            'cancelled' => '<span class="badge-status error">Cancelled</span>',
            'failed' => '<span class="badge-status error">Failed</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge-status">' . $this->status . '</span>';
    }
    
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing'])
                     ->where('scheduled_at', '<=', now())
                     ->where(function ($query) {
                         $query->whereNull('next_dispatch_at')
                             ->orWhere('next_dispatch_at', '<=', now());
                     });
    }
    
    public function updateProgress($sent, $failed)
    {
        $this->sent_count = $sent;
        $this->failed_count = $failed;
        
        if ($sent + $failed >= $this->total_numbers) {
            $this->status = 'completed';
        } else {
            $this->status = 'processing';
        }
        
        $this->save();
    }
}
