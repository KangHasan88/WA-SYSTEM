<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpReminder extends Model
{
    protected $table = 'follow_up_reminders';
    
    protected $fillable = [
        'tenant_id',
        'wa_inbox_id',
        'customer_number',
        'customer_name',
        'reminder_date',
        'reminder_note',
        'status',
        'done_at',
        'done_by'
    ];
    
    protected $casts = [
        'reminder_date' => 'datetime',
        'done_at' => 'datetime'
    ];
    
    public function waInbox()
    {
        return $this->belongsTo(WaInbox::class, 'wa_inbox_id');
    }
    
    public static function getPendingReminders()
    {
        return self::where('status', 'pending')
            ->where('reminder_date', '<=', now())
            ->orderBy('reminder_date', 'asc')
            ->get();
    }
}
