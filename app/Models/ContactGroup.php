<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactGroup extends Model
{
    protected $table = 'contact_groups';
    
    protected $fillable = [
        'tenant_id',
        'name',
        'description'
    ];
    
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'group_id');
    }
    
    public function getTotalContactsAttribute(): int
    {
        return $this->contacts()->count();
    }
}
