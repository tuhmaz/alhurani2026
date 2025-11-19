<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\BannedIp;
use App\Models\SecurityLog;

/**
 * Middleware للحماية من هجمات Brute Force
 * يقوم بحظر عناوين IP التي تقوم بمحاولات متكررة فاشلة
 */
class BruteForceProtection
{
    /**
     * عدد المحاولات المسموحة قبل الحظر المؤقت
     */
    protected int $maxAttempts = 5;

    /**
     * عدد الدقائق للحظر المؤقت
     */
    protected int $tempBlockMinutes = 15;

    /**
     * عدد المحاولات قبل الحظر الدائم
     */
    protected int $maxAttemptsForPermanentBan = 20;

    /**
     * المسارات الحساسة التي تحتاج حماية إضافية
     */
    protected array $sensitivePaths = [
        'login',
        'register',
        'password/email',
        'password/reset',
        'two-factor-challenge',
        'api/login',
        'api/register',
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // التحقق من وجود IP في قائمة الحظر
        if (BannedIp::isBanned($ip)) {
            $this->logAttempt($ip, $request, 'blocked_access');

            return response()->json([
                'status' => false,
                'message' => 'تم حظر عنوان IP هذا بسبب نشاط مشبوه',
                'error' => 'ip_banned'
            ], 403);
        }

        // التحقق من المحاولات الفاشلة
        $attempts = $this->getFailedAttempts($ip);

        // إذا تجاوز عدد المحاولات الحد المسموح للحظر الدائم
        if ($attempts >= $this->maxAttemptsForPermanentBan) {
            $this->banIpPermanently($ip, $request);

            return response()->json([
                'status' => false,
                'message' => 'تم حظر عنوان IP هذا بشكل دائم',
                'error' => 'ip_banned_permanently'
            ], 403);
        }

        // إذا تجاوز عدد المحاولات الحد المسموح للحظر المؤقت
        if ($attempts >= $this->maxAttempts) {
            $this->blockIpTemporarily($ip, $request);

            $retryAfter = $this->tempBlockMinutes * 60;

            return response()->json([
                'status' => false,
                'message' => "تم حظرك مؤقتاً بسبب محاولات فاشلة متعددة. حاول مرة أخرى بعد {$this->tempBlockMinutes} دقيقة",
                'error' => 'too_many_failed_attempts',
                'retry_after' => $retryAfter
            ], 429)
            ->header('Retry-After', $retryAfter);
        }

        // تنفيذ الطلب
        $response = $next($request);

        // فحص الاستجابة للكشف عن الفشل
        if ($this->isFailedAttempt($request, $response)) {
            $this->incrementFailedAttempts($ip, $request);
        } else {
            // إذا نجح، امسح العداد
            $this->clearFailedAttempts($ip);
        }

        return $response;
    }

    /**
     * الحصول على عدد المحاولات الفاشلة
     */
    protected function getFailedAttempts(string $ip): int
    {
        return (int) Cache::get($this->getAttemptsKey($ip), 0);
    }

    /**
     * زيادة عدد المحاولات الفاشلة
     */
    protected function incrementFailedAttempts(string $ip, Request $request): void
    {
        $key = $this->getAttemptsKey($ip);
        $attempts = $this->getFailedAttempts($ip) + 1;

        // حفظ العداد لمدة ساعة
        Cache::put($key, $attempts, now()->addHour());

        // تسجيل المحاولة الفاشلة
        $this->logAttempt($ip, $request, 'failed_attempt', [
            'attempts' => $attempts,
            'max_attempts' => $this->maxAttempts
        ]);
    }

    /**
     * مسح المحاولات الفاشلة
     */
    protected function clearFailedAttempts(string $ip): void
    {
        Cache::forget($this->getAttemptsKey($ip));
    }

    /**
     * الحصول على مفتاح الكاش للمحاولات
     */
    protected function getAttemptsKey(string $ip): string
    {
        return "brute_force:attempts:{$ip}";
    }

    /**
     * حظر IP مؤقتاً
     */
    protected function blockIpTemporarily(string $ip, Request $request): void
    {
        $key = "brute_force:blocked:{$ip}";
        Cache::put($key, true, now()->addMinutes($this->tempBlockMinutes));

        $this->logAttempt($ip, $request, 'temporary_block', [
            'duration_minutes' => $this->tempBlockMinutes
        ]);
    }

    /**
     * حظر IP بشكل دائم
     */
    protected function banIpPermanently(string $ip, Request $request): void
    {
        try {
            BannedIp::ban(
                $ip,
                'حظر تلقائي بسبب محاولات فاشلة متعددة (Brute Force)',
                null // null = حظر دائم
            );

            $this->logAttempt($ip, $request, 'permanent_ban', [
                'total_attempts' => $this->getFailedAttempts($ip)
            ]);

            // مسح الكاش
            $this->clearFailedAttempts($ip);
        } catch (\Exception $e) {
            Log::error('فشل في حظر IP', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * التحقق مما إذا كانت المحاولة فاشلة
     */
    protected function isFailedAttempt(Request $request, $response): bool
    {
        // التحقق من كود الاستجابة
        $statusCode = method_exists($response, 'status') ? $response->status() : 200;

        // إذا كان المسار حساس
        if ($this->isSensitivePath($request)) {
            // محاولة فاشلة إذا كان الكود 401, 403, أو 422
            return in_array($statusCode, [401, 403, 422]);
        }

        return false;
    }

    /**
     * التحقق مما إذا كان المسار حساس
     */
    protected function isSensitivePath(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->sensitivePaths as $sensitivePath) {
            if (str_contains($path, $sensitivePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تسجيل المحاولة
     */
    protected function logAttempt(string $ip, Request $request, string $eventType, array $extra = []): void
    {
        try {
            SecurityLog::log(
                $eventType,
                $eventType === 'permanent_ban' ? SecurityLog::SEVERITY_LEVELS['CRITICAL'] : SecurityLog::SEVERITY_LEVELS['WARNING'],
                array_merge([
                    'ip' => $ip,
                    'path' => $request->path(),
                    'method' => $request->method(),
                ], $extra),
                $request
            );
        } catch (\Exception $e) {
            Log::error('فشل تسجيل محاولة Brute Force', [
                'error' => $e->getMessage(),
                'ip' => $ip
            ]);
        }
    }
}
