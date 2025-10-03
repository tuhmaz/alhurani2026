<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SecurityLog;
use App\Services\SecurityAlertService;
use App\Models\BannedIp;
use Illuminate\Support\Facades\Auth;

class SecurityScanMiddleware
{
    /**
     * خدمة تنبيهات الأمان
     *
     * @var SecurityAlertService
     */
    protected $securityAlertService;

    /**
     * إنشاء مثيل جديد للوسيط.
     *
     * @param SecurityAlertService $securityAlertService
     * @return void
     */
    public function __construct(SecurityAlertService $securityAlertService)
    {
        $this->securityAlertService = $securityAlertService;
    }

    /**
     * معالجة الطلب الوارد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تجاهل فحص الحذف من السجلات الأمنية
        if (
            ($request->isMethod('DELETE') || ($request->isMethod('POST') && $request->input('_method') === 'DELETE')) &&
            ($request->route() && (
                str_contains($request->route()->getName() ?? '', 'logs.destroy') ||
                str_contains($request->getPathInfo(), 'dashboard/security/logs')
            ))
        ) {
            return $next($request);
        }

        // تخطي آمن لحالة حذف الإشعارات المحددة إذا كانت القيم UUIDs فقط
        if (
            $request->isMethod('POST') &&
            ($request->route() && str_contains($request->route()->getName() ?? '', 'dashboard.notifications.delete-selected'))
        ) {
            $ids = $request->input('selected_notifications');
            if (is_array($ids) && !empty($ids)) {
                $uuidRegex = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';
                $allValid = true;
                foreach ($ids as $id) {
                    if (!is_string($id) || !preg_match($uuidRegex, $id)) {
                        $allValid = false;
                        break;
                    }
                }
                if ($allValid) {
                    return $next($request);
                }
            }
        }

        // فحص الطلب بحثًا عن أنماط هجمات محتملة
        if ($this->detectSqlInjection($request) || $this->detectXssAttack($request)) {
            // حظر تلقائي للـ IP عند اكتشاف محاولة هجوم
            try {
                $ip = $request->ip();
                if ($ip && !BannedIp::isBanned($ip)) {
                    $eventType = $this->detectSqlInjection($request) ? 'sql_injection_attempt' : 'xss_attempt';
                    $reason = sprintf('Auto-ban: %s detected on route %s | UA: %s',
                        $eventType,
                        $request->route() ? ($request->route()->getName() ?? $request->path()) : $request->path(),
                        (string) $request->userAgent()
                    );
                    // اجعل الحظر دائمًا (يمكن تغييره لاحقًا إلى مدة معينة)
                    BannedIp::ban($ip, $reason, null, null);
                }
            } catch (\Throwable $e) {
                // لا تعطل الاستجابة في حال فشل الحظر، فقط سجّل المشكلة
                Log::channel('security')->error('Failed to auto-ban IP after attack detection', [
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                ]);
            }
            // تسجيل الحدث الأمني
            $log = $this->logSecurityEvent($request);
            
            // معالجة الحدث الأمني وإرسال التنبيهات
            $this->securityAlertService->processSecurityEvent($log);
            
            // إعادة رسالة خطأ عامة للمستخدم
            return response()->json([
                'error' => 'تم اكتشاف محتوى غير آمن في الطلب.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * اكتشاف محاولات حقن SQL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function detectSqlInjection(Request $request): bool
    {
        $patterns = [
            '/\b(union\s+select|select\s+.*\s+from|insert\s+into|update\s+.*\s+set)\b/i', // حذف delete from
            // Require the SQL keyword to be a standalone token, not part of a larger word (e.g., "selected_notifications")
            '/[\'";]\s*(?<![a-z_])(union|select|insert|update|drop|truncate|alter|exec(?:ute)?|sp_|xp_)\b/i',

            '/--\s+/',
            '/;\s*$/',
            '/\/\*.*\*\//',
            '/@@(version|servername|hostname)/i',
            '/waitfor\s+delay\s+/i',
            '/cast\(.+as\s+\w+\)/i',
            '/convert\(.+using\s+\w+\)/i',
        ];

        return $this->checkPatterns($request, $patterns, 'sql_injection_attempt');
    }

    /**
     * اكتشاف هجمات XSS.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function detectXssAttack(Request $request): bool
    {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/i',
            '/javascript\s*:/i',
            '/on(load|click|mouseover|submit|focus|blur|change|error|select)\s*=/i',
            '/<\s*img[^>]+src\s*=\s*[\'"]?\s*(javascript|data|vbscript):/i',
            '/<\s*iframe/i',
            '/<\s*object/i',
            '/<\s*embed/i',
            '/<\s*form/i',
            '/document\.(cookie|write|location|open|eval)/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
            '/base64/i',
            '/alert\s*\(/i',
            '/confirm\s*\(/i',
            '/prompt\s*\(/i',
        ];

        return $this->checkPatterns($request, $patterns, 'xss_attempt');
    }

    /**
     * فحص أنماط الهجمات في الطلب.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $patterns
     * @param  string  $attackType
     * @return bool
     */
    protected function checkPatterns(Request $request, array $patterns, string $attackType): bool
    {
        // استثناء المفاتيح الآمنة
        $inputs = $request->except(['_token', '_method', 'page', 'per_page', 'sort', 'direction']);
        // افحص القيم فقط لتجنب مطابقة أسماء المفاتيح (مثل selected_notifications)
        $values = $this->flattenValues($inputs);
        $inputString = implode("\n", $values);
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                // تسجيل تفاصيل النمط المكتشف
                Log::channel('security')->warning("تم اكتشاف نمط {$attackType}: " . $pattern, [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => $request->route() ? $request->route()->getName() : $request->path(),
                    'pattern' => $pattern,
                    'input' => $inputString,
                ]);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * قم بتسطيح قيم المصفوفة إلى قائمة نصوص لفحص الأنماط فقط على القيم دون المفاتيح.
     *
     * @param array $data
     * @return array<string>
     */
    protected function flattenValues(array $data): array
    {
        $result = [];
        $stack = [$data];
        while (!empty($stack)) {
            $current = array_pop($stack);
            foreach ((array) $current as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                } elseif (is_object($value)) {
                    $stack[] = (array) $value;
                } elseif (is_scalar($value) || $value === null) {
                    $result[] = (string) $value;
                }
            }
        }
        return $result;
    }

    /**
     * تسجيل حدث أمني.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return SecurityLog
     */
    protected function logSecurityEvent(Request $request): SecurityLog
    {
        $eventType = $this->detectSqlInjection($request) ? 'sql_injection_attempt' : 'xss_attempt';
        
        // تنظيف البيانات قبل الحفظ
        $requestData = $request->all();
        foreach ($requestData as $key => $value) {
            if (is_array($value)) {
                $requestData[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_object($value)) {
                $requestData[$key] = json_encode((array)$value, JSON_UNESCAPED_UNICODE);
            }
        }
        
        return SecurityLog::create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => $eventType,
            'description' => "تم اكتشاف محاولة {$eventType} من عنوان IP: {$request->ip()}",
            'user_id' => Auth::id(),
            'route' => $request->route() ? $request->route()->getName() : $request->path(),
            'request_data' => json_encode($requestData, JSON_UNESCAPED_UNICODE),
            'severity' => 'danger',
            'is_resolved' => false,
            'risk_score' => 80
        ]);
    }
}
