<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class UpdateUserLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user && $request->bearerToken()) {
            try {
                $user = Auth::guard('sanctum')->user();
            } catch (\Throwable $e) {}
        }

        if ($user) {
            /** @var \App\Models\User $user */
            $minutes = (int) Config::get('monitoring.user_last_activity_minutes', 5);
            $key = 'ua:last_activity:' . $user->id;

            if (Cache::add($key, 1, now()->addMinutes($minutes))) {
                // Only update at most once per window
                $user->last_activity = now();
                $user->save();
            }
        }

        return $next($request);
    }
}
