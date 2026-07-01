<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'token_hash',
        'token_created_at',
        'token_rotated_at',
        'scopes',
        'rate_limit_per_minute',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
        'token_created_at' => 'datetime',
        'token_rotated_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public static function generatePlainToken(): string
    {
        return 'kwa_' . Str::random(64);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findActiveByToken(?string $token): ?self
    {
        if (!$token) {
            return null;
        }

        return self::where('token_hash', self::hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?: [];

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }
}
