<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\VisitorService;
use App\Models\VisitorTracking;
use App\Models\VisitorSession;
use App\Models\BannedIp;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class VisitorTrackingMiddleware
{
    protected $visitorService;

    public function __construct(VisitorService $visitorService)
    {
        $this->visitorService = $visitorService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (mixed)  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        // If configured, defer visitor tracking until after response is sent
        if (Config::get('monitoring.defer_visitor_tracking', true)) {
            app()->terminating(function () use ($request, $response, $responseTime) {
                try {
                    if (!Config::get('monitoring.visitor_tracking_enabled', true)) {
                        return;
                    }

                    $ip = $request->ip() ?? '127.0.0.1';
                    $userAgent = $request->header('User-Agent', 'Unknown');

                    // Debounce DB writes per IP
                    $debounceSeconds = (int) Config::get('monitoring.visitor_write_debounce_seconds', 60);
                    $debounceKey = "vt:debounce:" . $ip;
                    $canWrite = Cache::add($debounceKey, 1, $debounceSeconds);

                    // Sampling heavy work (UA analysis, geo)
                    $rate = max(1, (int) Config::get('monitoring.visitor_sampling_rate', 10));
                    $isSampled = (random_int(1, $rate) === 1);

                    $analysis = [ 'isBot' => false ];
                    $geoData = [ 'country' => null, 'city' => null, 'lat' => null, 'lon' => null ];

                    if ($isSampled) {
                        try { $analysis = $this->visitorService->analyzeUserAgent($userAgent); } catch (\Throwable $e) {}
                        try {
                            $geoTtl = (int) Config::get('monitoring.geo_cache_ttl', 86400);
                            $geoData = Cache::remember("vt:geo:" . $ip, $geoTtl, function () use ($ip) {
                                return $this->visitorService->getGeoDataFromIP($ip);
                            });
                        } catch (\Throwable $e) {}
                    }

                    $browser = $analysis['isBot'] ? ($analysis['botInfo']['name'] ?? 'Bot') : ($analysis['client']['name'] ?? 'Unknown');
                    $os = $analysis['isBot'] ? 'Bot/Spider' : ($analysis['os']['name'] ?? 'Unknown');

                    if ($canWrite) {
                        VisitorTracking::updateOrCreate(
                            [ 'ip_address' => $ip, 'user_id' => Auth::id(), ],
                            [
                                'user_agent'    => $userAgent,
                                'url'           => $request->fullUrl(),
                                'country'       => $geoData['country'] ?? null,
                                'city'          => $geoData['city'] ?? null,
                                'browser'       => $browser,
                                'os'            => $os,
                                'latitude'      => $geoData['lat'] ?? null,
                                'longitude'     => $geoData['lon'] ?? null,
                                'device'        => $analysis['device'] ?? null,
                                'brand'         => $analysis['brand'] ?? null,
                                'model'         => $analysis['model'] ?? null,
                                'last_activity' => now(),
                                'response_time' => $responseTime,
                                'status_code'   => $response->getStatusCode(),
                            ]
                        );
                    }

                    try {
                        $sessionId = method_exists($request, 'session') && $request->session() ? $request->session()->getId() : null;
                        $vsKey = 'vs:log:' . ($sessionId ?: $ip);
                        $vsDebounce = (int) Config::get('monitoring.visitor_session_log_debounce', 30);
                        if (Cache::add($vsKey, 1, $vsDebounce)) {
                            VisitorSession::log($request, Auth::user());
                        }
                    } catch (\Throwable $e) {
                        Log::warning('VisitorSession log failed: ' . $e->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::error('Visitor tracking error: ' . $e->getMessage());
                }
            });

            return $response;
        }

        try {
            if (!Config::get('monitoring.visitor_tracking_enabled', true)) {
                return $response;
            }

            $ip = $request->ip() ?? '127.0.0.1';
            $userAgent = $request->header('User-Agent', 'Unknown');

            // Debounce DB writes per IP
            $debounceSeconds = (int) Config::get('monitoring.visitor_write_debounce_seconds', 60);
            $debounceKey = "vt:debounce:" . $ip;
            $canWrite = Cache::add($debounceKey, 1, $debounceSeconds);

            // Sampling heavy work (UA analysis, geo)
            $rate = max(1, (int) Config::get('monitoring.visitor_sampling_rate', 10));
            $isSampled = (random_int(1, $rate) === 1);

            $analysis = [
                'isBot' => false,
            ];
            $geoData = [
                'country' => null,
                'city' => null,
                'lat' => null,
                'lon' => null,
            ];

            if ($isSampled) {
                // تحليل الـUser-Agent (ثقيل)
                try {
                    $analysis = $this->visitorService->analyzeUserAgent($userAgent);
                } catch (\Throwable $e) {
                    // تجاهل أخطاء التحليل
                }

                // جلب بيانات الموقع الجغرافي مع كاش
                try {
                    $geoTtl = (int) Config::get('monitoring.geo_cache_ttl', 86400);
                    $geoData = Cache::remember("vt:geo:" . $ip, $geoTtl, function () use ($ip) {
                        return $this->visitorService->getGeoDataFromIP($ip);
                    });
                } catch (\Throwable $e) {
                    // تجاهل أخطاء الـgeo
                }
            }

            // تحديد نوع المتصفح ونظام التشغيل
            $browser = $analysis['isBot']
                ? ($analysis['botInfo']['name'] ?? 'Bot')
                : ($analysis['client']['name'] ?? 'Unknown');

            $os = $analysis['isBot']
                ? 'Bot/Spider'
                : ($analysis['os']['name'] ?? 'Unknown');

            if ($canWrite) {
                // حفظ أو تحديث سجل الزائر (مقلل بالتخفيض والـsampling)
                VisitorTracking::updateOrCreate(
                    [
                        'ip_address' => $ip,
                        'user_id'    => Auth::id(),
                    ],
                    [
                        'user_agent'    => $userAgent,
                        'url'           => $request->fullUrl(),
                        'country'       => $geoData['country'] ?? null,
                        'city'          => $geoData['city'] ?? null,
                        'browser'       => $browser,
                        'os'            => $os,
                        'latitude'      => $geoData['lat'] ?? null,
                        'longitude'     => $geoData['lon'] ?? null,
                        'device'        => $analysis['device'] ?? null,
                        'brand'         => $analysis['brand'] ?? null,
                        'model'         => $analysis['model'] ?? null,
                        'last_activity' => now(),
                        'response_time' => $responseTime,
                        'status_code'   => $response->getStatusCode(),
                    ]
                );
            }

            // تحديث/إنشاء جلسة الزائر النشطة مع debounce
            try {
                $sessionId = method_exists($request, 'session') && $request->session() ? $request->session()->getId() : null;
                $vsKey = 'vs:log:' . ($sessionId ?: $ip);
                $vsDebounce = (int) Config::get('monitoring.visitor_session_log_debounce', 30);
                if (Cache::add($vsKey, 1, $vsDebounce)) {
                    VisitorSession::log($request, Auth::user());
                }
            } catch (\Throwable $e) {
                Log::warning('VisitorSession log failed: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Visitor tracking error: ' . $e->getMessage());
        }

        return $response;
    }
}
