<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaInbox extends Model
{
    protected $table = 'wa_inbox';
    
    protected $fillable = [
        'tenant_id',
        'wa_account_id',
        'from_number',
        'from_name',
        'message',
        'message_id',
        'webhook_fingerprint',
        'type',
        'media_url',
        'media_mime',
        'media_size',
        'media_filename',
        'media_thumbnail',
        'caption',
        'raw_payload',
        'approval_result',
        'ignored_reason',
        'webhook_source_ip',
        'webhook_user_agent',
        'direction',
        'is_read',
        'is_replied',
        'lead_status',
        'lead_notes',
        'assigned_to',
        'follow_up_date',
        'received_at'
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'is_replied' => 'boolean',
        'received_at' => 'datetime',
        'media_size' => 'integer',
        'follow_up_date' => 'datetime',
        'raw_payload' => 'array',
        'approval_result' => 'array'
    ];
    
    // Lead status options
    public static $leadStatuses = [
        'new' => ['label' => '🆕 New Lead', 'color' => 'info', 'icon' => 'fa-star'],
        'hot' => ['label' => '🔥 Hot Lead', 'color' => 'warning', 'icon' => 'fa-fire'],
        'deal' => ['label' => '✅ Deal Won', 'color' => 'success', 'icon' => 'fa-check-circle'],
        'lost' => ['label' => '❌ Deal Lost', 'color' => 'danger', 'icon' => 'fa-times-circle'],
    ];
    
    // Helper functions untuk status
    public static function getLeadStatusLabel($status)
    {
        $labels = [
            'new' => 'New Lead',
            'hot' => 'Hot Lead',
            'deal' => 'Deal Won',
            'lost' => 'Deal Lost',
        ];
        return $labels[$status] ?? 'New Lead';
    }
    
    public static function getLeadStatusIcon($status)
    {
        $icons = [
            'new' => '🆕',
            'hot' => '🔥',
            'deal' => '✅',
            'lost' => '❌',
        ];
        return $icons[$status] ?? '🆕';
    }
    
    public static function getLeadStatusColor($status)
    {
        $colors = [
            'new' => 'info',
            'hot' => 'warning',
            'deal' => 'success',
            'lost' => 'danger',
        ];
        return $colors[$status] ?? 'info';
    }
    
    public function getLeadStatusBadgeAttribute()
    {
        $status = self::$leadStatuses[$this->lead_status] ?? self::$leadStatuses['new'];
        $color = $status['color'];
        $label = $status['label'];
        
        return "<span class='badge-status {$color}'><i class='fas {$status['icon']}'></i> {$label}</span>";
    }
    
    public function getContactNameAttribute()
    {
        $contact = Contact::where('number', $this->from_number)->first();
        return $contact ? $contact->name : ($this->from_name ?? $this->from_number);
    }
    
    public function getContactInfoAttribute()
    {
        $contact = Contact::where('number', $this->from_number)->first();
        return [
            'name' => $contact ? $contact->name : ($this->from_name ?? $this->from_number),
            'number' => $this->from_number,
            'has_contact' => !is_null($contact)
        ];
    }
    
    public function getHasMediaAttribute(): bool
    {
        return !empty($this->media_url);
    }
    
    public function getMediaCategoryAttribute(): string
    {
        if (!$this->media_mime) return 'other';
        
        if (str_starts_with($this->media_mime, 'image/')) return 'image';
        if (str_starts_with($this->media_mime, 'video/')) return 'video';
        if (str_starts_with($this->media_mime, 'audio/')) return 'audio';
        if (str_starts_with($this->media_mime, 'application/pdf')) return 'pdf';
        if (str_starts_with($this->media_mime, 'application/msword')) return 'word';
        if (str_starts_with($this->media_mime, 'application/vnd.openxmlformats-officedocument')) return 'word';
        if (str_starts_with($this->media_mime, 'application/vnd.ms-excel')) return 'excel';
        if (str_starts_with($this->media_mime, 'application/vnd.openxmlformats-officedocument.spreadsheetml')) return 'excel';
        
        return 'document';
    }
    
    public function getFileIconAttribute(): string
    {
        return match($this->media_category) {
            'image' => 'fa-image',
            'video' => 'fa-video',
            'audio' => 'fa-music',
            'pdf' => 'fa-file-pdf',
            'word' => 'fa-file-word',
            'excel' => 'fa-file-excel',
            default => 'fa-file'
        };
    }
    
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->media_size) return '';
        
        $bytes = $this->media_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
    
    public static function getConversations($limit = 50)
    {
        $conversations = self::select(
                'from_number',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(message) as message'),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('MAX(lead_status) as lead_status'),
                DB::raw('MAX(follow_up_date) as follow_up_date'),
                DB::raw('SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            )
            ->groupBy('from_number')
            ->orderBy('last_message_at', 'desc')
            ->paginate($limit);
        
        foreach ($conversations as $conv) {
            $contact = Contact::where('number', $conv->from_number)->first();
            $conv->display_name = $contact ? $contact->name : $conv->from_number;
            $conv->display_number = $conv->from_number;
            $conv->has_contact = !is_null($contact);
            $conv->has_media = self::where('from_number', $conv->from_number)->whereNotNull('media_url')->exists();
            
            // Tambahkan status badge
            $conv->lead_status_label = self::getLeadStatusLabel($conv->lead_status);
            $conv->lead_status_icon = self::getLeadStatusIcon($conv->lead_status);
            $conv->lead_status_color = self::getLeadStatusColor($conv->lead_status);
        }
        
        return $conversations;
    }
    
    public static function getConversationWith($number, $limit = 50)
    {
        $messages = self::where('from_number', $number)
            ->orderBy('created_at', 'asc')
            ->paginate($limit);
        
        $contact = Contact::where('number', $number)->first();
        $displayName = $contact ? $contact->name : $number;
        
        foreach ($messages as $msg) {
            $msg->display_name = $displayName;
            $msg->display_number = $number;
        }
        
        return $messages;
    }
    
    public static function markConversationAsRead($number)
    {
        return self::where('from_number', $number)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
    
    public static function getUnreadCount()
    {
        return self::where('is_read', false)->count();
    }
    
    public static function getUnreadCountPerConversation($number)
    {
        return self::where('from_number', $number)
            ->where('is_read', false)
            ->count();
    }
    
    // Get leads that need follow up today
    public static function getTodayFollowUps()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();
        
        return self::whereNotNull('follow_up_date')
            ->where('follow_up_date', '>=', $today)
            ->where('follow_up_date', '<=', $tomorrow)
            ->where('lead_status', '!=', 'deal')
            ->where('lead_status', '!=', 'lost')
            ->orderBy('follow_up_date', 'asc')
            ->get();
    }
}
