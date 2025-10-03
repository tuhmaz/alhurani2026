<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BannedIp extends Model
{
    protected $table = 'banned_ips';

    protected $fillable = [
        'ip',
        'reason',
        'banned_by',      // أصبح الآن user_id
        'banned_until'
    ];

    protected $casts = [
        'banned_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة المستخدم (الأدمن الذي حظر)
    public function admin()
    {
        return $this->belongsTo(\App\Models\User::class, 'banned_by');
    }

    // التحقق إذا الآيبي محظور
    public static function isBanned($ip)
    {
        return static::where('ip', $ip)
            ->where(function($query) {
                $query->whereNull('banned_until')
                      ->orWhere('banned_until', '>', now());
            })
            ->exists();
    }

    // حظر آيبي
    public static function ban($ip, $reason = null, $days = 30, $adminId = null)
    {
        return static::create([
            'ip' => $ip,
            'reason' => $reason,
            'banned_by' => $adminId ?: (Auth::check() ? Auth::id() : null),
            'banned_until' => $days ? now()->addDays($days) : null
        ]);
    }

    // Scope: المحظورين النشطين
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('banned_until')
              ->orWhere('banned_until', '>', now());
        });
    }

    // Scope: المحظورين المنتهين
    public function scopeExpired($query)
    {
        return $query->whereNotNull('banned_until')
                     ->where('banned_until', '<=', now());
    }

    public function isActive()
    {
        return is_null($this->banned_until) || $this->banned_until->isFuture();
    }

}
