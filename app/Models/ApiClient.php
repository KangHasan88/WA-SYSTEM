<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'token_hash',
        'scopes',
        'rate_limit_per_minute',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
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
