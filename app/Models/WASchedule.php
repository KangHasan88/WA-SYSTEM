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
        'total_numbers',
        'scheduled_at',
        'status',
        'sent_count',
        'failed_count',
        'error_message'
    ];
    
    protected $casts = [
        'numbers' => 'array',
        'scheduled_at' => 'datetime',
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
            'completed' => '<span class="badge-status success">Completed</span>',
            'cancelled' => '<span class="badge-status error">Cancelled</span>',
            'failed' => '<span class="badge-status error">Failed</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge-status">' . $this->status . '</span>';
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_at', '<=', now());
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