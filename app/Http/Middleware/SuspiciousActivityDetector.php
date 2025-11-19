<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\SecurityLog;
use App\Models\BannedIp;

/**
 * Middleware للكشف عن الأنشطة المشبوهة
 * يقوم بتحليل سلوك المستخدم والكشف عن الأنماط المشبوهة
 */
class SuspiciousActivityDetector
{
    /**
     * عدد الطلبات المسموحة في فترة قصيرة جداً
     */
    protected int $rapidRequestsThreshold = 10;

    /**
     * الفترة الزمنية لفحص الطلبات السريعة (بالثواني)
     */
    protected int $rapidRequestsWindow = 5;

    /**
     * User Agents المشبوهة
     */
    protected array $suspiciousUserAgents = [
        'python-requests',
        'curl',
        'wget',
        'scrapy',
        'bot',
        'crawler',
        'spider',
        'scraper',
        'nikto',
        'sqlmap',
        'havij',
        'acunetix',
        'nmap',
        'masscan',
        'metasploit',
    ];

    /**
     * امتدادات الملفات المشبوهة في الطلب
     */
    protected array $suspiciousExtensions = [
        '.php',
        '.asp',
        '.aspx',
        '.jsp',
        '.cgi',
        '.exe',
        '.dll',
        '.bat',
        '.sh',
        '.py',
        '.pl',
        '.rb',
    ];

    /**
     * أنماط مشبوهة في الطلب
     */
    protected array $suspiciousPatterns = [
        '/\.\.\//',              // Directory Traversal
        '/<script/i',            // XSS
        '/union.*select/i',      // SQL Injection
        '/exec\s*\(/i',          // Command Injection
        '/system\s*\(/i',        // Command Execution
        '/eval\s*\(/i',          // Code Evaluation
        '/base64_decode/i',      // Obfuscation
        '/cmd\.exe/i',           // Windows Command
        '/\/etc\/passwd/i',      // Linux System File
        '/\/proc\/self/i',       // Linux Process Info
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $suspicionScore = 0;
        $suspicionReasons = [];

        // 1. فحص Rapid Requests (طلبات سريعة جداً)
        if ($this->isRapidRequests($ip)) {
            $suspicionScore += 30;
            $suspicionReasons[] = 'rapid_requests';
        }

        // 2. فحص User Agent المشبوه
        if ($this->hasSuspiciousUserAgent($request)) {
            $suspicionScore += 25;
            $suspicionReasons[] = 'suspicious_user_agent';
        }

        // 3. فحص الامتدادات المشبوهة في URL
        if ($this->hasSuspiciousExtension($request)) {
            $suspicionScore += 20;
            $suspicionReasons[] = 'suspicious_file_extension';
        }

        // 4. فحص الأنماط المشبوهة في الطلب
        if ($this->hasSuspiciousPattern($request)) {
            $suspicionScore += 40;
            $suspicionReasons[] = 'suspicious_pattern_detected';
        }

        // 5. فحص الطلبات من دول محظورة (إذا كانت مفعلة)
        if ($this->isFromBlockedCountry($request)) {
            $suspicionScore += 15;
            $suspicionReasons[] = 'blocked_country';
        }

        // 6. فحص طلبات غير معتادة للملفات الحساسة
        if ($this->requestsSensitiveFiles($request)) {
            $suspicionScore += 35;
            $suspicionReasons[] = 'sensitive_file_access';
        }

        // 7. فحص عدم وجود Referer لطلبات POST
        if ($this->isMissingRefererForPost($request)) {
            $suspicionScore += 10;
            $suspicionReasons[] = 'missing_referer';
        }

        // إذا تجاوز مجموع الشك الحد المسموح
        if ($suspicionScore >= 50) {
            $this->handleSuspiciousActivity($ip, $request, $suspicionScore, $suspicionReasons);

            // إذا كان مجموع الشك عالي جداً (>= 80)، احظر مباشرة
            if ($suspicionScore >= 80) {
                $this->banIpImmediately($ip, $request, $suspicionReasons);

                return response()->json([
                    'status' => false,
                    'message' => 'تم اكتشاف نشاط مشبوه. تم حظر عنوان IP',
                    'error' => 'suspicious_activity_detected'
                ], 403);
            }

            // تحذير للنشاط المشبوه
            return response()->json([
                'status' => false,
                'message' => 'تم اكتشاف نشاط غير اعتيادي',
                'error' => 'unusual_activity'
            ], 403);
        }

        // تسجيل طلب عادي لأغراض التحليل
        $this->trackRequest($ip, $request);

        return $next($request);
    }

    /**
     * فحص الطلبات السريعة
     */
    protected function isRapidRequests(string $ip): bool
    {
        $key = "suspicious:rapid:{$ip}";
        $requests = (int) Cache::get($key, 0);

        // زيادة العداد
        Cache::put($key, $requests + 1, now()->addSeconds($this->rapidRequestsWindow));

        return $requests >= $this->rapidRequestsThreshold;
    }

    /**
     * فحص User Agent المشبوه
     */
    protected function hasSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // فحص إذا كان User Agent فارغ
        if (empty($userAgent)) {
            return true;
        }

        foreach ($this->suspiciousUserAgents as $suspicious) {
            if (str_contains($userAgent, strtolower($suspicious))) {
                return true;
            }
        }

        return false;
    }

    /**
     * فحص الامتدادات المشبوهة
     */
    protected function hasSuspiciousExtension(Request $request): bool
    {
        $path = strtolower($request->path());

        foreach ($this->suspiciousExtensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * فحص الأنماط المشبوهة
     */
    protected function hasSuspiciousPattern(Request $request): bool
    {
        // فحص URL
        $url = $request->fullUrl();

        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        // فحص المعاملات
        $params = $request->all();
        $paramsString = json_encode($params);

        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $paramsString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * فحص الدول المحظورة
     */
    protected function isFromBlockedCountry(Request $request): bool
    {
        // يمكن تطبيق GeoIP هنا إذا كان متوفراً
        // مثال: استخدام مكتبة GeoIP2
        return false;
    }

    /**
     * فحص طلبات الملفات الحساسة
     */
    protected function requestsSensitiveFiles(Request $request): bool
    {
        $sensitiveFiles = [
            '.env',
            'wp-config.php',
            'config.php',
            'database.php',
            'phpinfo.php',
            'web.config',
            'settings.php',
            'configuration.php',
        ];

        $path = strtolower($request->path());

        foreach ($sensitiveFiles as $file) {
            if (str_contains($path, $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * فحص عدم وجود Referer لطلبات POST
     */
    protected function isMissingRefererForPost(Request $request): bool
    {
        return $request->isMethod('POST') && !$request->header('Referer');
    }

    /**
     * معالجة النشاط المشبوه
     */
    protected function handleSuspiciousActivity(string $ip, Request $request, int $score, array $reasons): void
    {
        try {
            SecurityLog::log(
                'suspicious_activity',
                $score >= 80 ? SecurityLog::SEVERITY_LEVELS['CRITICAL'] : SecurityLog::SEVERITY_LEVELS['WARNING'],
                [
                    'ip' => $ip,
                    'suspicion_score' => $score,
                    'reasons' => $reasons,
                    'user_agent' => $request->userAgent(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'params' => $request->except(['password', '_token']),
                ],
                $request
            );

            Log::warning('نشاط مشبوه تم اكتشافه', [
                'ip' => $ip,
                'score' => $score,
                'reasons' => $reasons,
            ]);
        } catch (\Exception $e) {
            Log::error('فشل تسجيل النشاط المشبوه', [
                'error' => $e->getMessage(),
                'ip' => $ip
            ]);
        }
    }

    /**
     * حظر IP فوراً
     */
    protected function banIpImmediately(string $ip, Request $request, array $reasons): void
    {
        try {
            BannedIp::ban(
                $ip,
                'حظر تلقائي بسبب نشاط مشبوه: ' . implode(', ', $reasons),
                30 // حظر لمدة 30 يوم
            );

            SecurityLog::log(
                'automatic_ban',
                SecurityLog::SEVERITY_LEVELS['CRITICAL'],
                [
                    'ip' => $ip,
                    'reasons' => $reasons,
                    'ban_duration' => '30 days',
                ],
                $request
            );
        } catch (\Exception $e) {
            Log::error('فشل حظر IP المشبوه', [
                'error' => $e->getMessage(),
                'ip' => $ip
            ]);
        }
    }

    /**
     * تتبع الطلب
     */
    protected function trackRequest(string $ip, Request $request): void
    {
        // يمكن إضافة تتبع للطلبات العادية لتحليل الأنماط
        $key = "requests:track:{$ip}:" . date('Y-m-d-H');
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key), now()->addHours(24));
    }
}
