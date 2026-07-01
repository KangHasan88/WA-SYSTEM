<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $table = 'contacts';
    
    protected $fillable = [
        'tenant_id',
        'group_id',
        'number',
        'name',
        'status'
    ];
    
    public function group(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class, 'group_id');
    }
    
    // Format number without + prefix
    public function getFormattedNumberAttribute(): string
    {
        $number = preg_replace('/\D/', '', $this->number);
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        if (substr($number, 0, 2) !== '62') {
            $number = '62' . $number;
        }
        return $number;
    }

    public function getIsSendableAttribute(): bool
    {
        return $this->status === 'active';
    }
}
