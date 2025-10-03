<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class SecurityHeaders
{
    /**
     * رؤوس الأمان الافتراضية
     */
    protected $securityHeaders = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), payment=(), usb=(), screen-wake-lock=(), accelerometer=(), gyroscope=(), magnetometer=(), midi=()',
        // تغيير من 'require-corp' إلى 'credentialless' لحل مشكلة COEP
        'Cross-Origin-Embedder-Policy' => 'credentialless',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        // تغيير من 'same-origin' إلى 'cross-origin' لسماح بتحميل الموارد من مصادر خارجية
        'Cross-Origin-Resource-Policy' => 'cross-origin',
    ];

    /**
     * معالجة الطلب
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // التحقق مما إذا كان الطلب لصفحة المراقبة
        $isMonitoringPage = $this->isMonitoringPage($request);
        // الصفحات العامة (ليست لوحة التحكم) — نحتاج تخفيف بعض الرؤوس لدعم الإعلانات
        $isPublicPage = $this->isPublicPage($request);

        // إضافة رؤوس الأمان الأساسية
        foreach ($this->securityHeaders as $header => $value) {
            // إذا كانت صفحة المراقبة، تخطي بعض رؤوس الأمان التي تسبب مشاكل مع الخرائط
            if ($isMonitoringPage && in_array($header, ['Cross-Origin-Embedder-Policy', 'Cross-Origin-Resource-Policy'])) {
                continue;
            }
            // إذا كانت صفحة عامة، لا نضيف COEP/COOP/CORP لتفادي تعارض شبكات الإعلانات
            if ($isPublicPage && in_array($header, ['Cross-Origin-Embedder-Policy', 'Cross-Origin-Opener-Policy', 'Cross-Origin-Resource-Policy'])) {
                continue;
            }
            $response->headers->set($header, $value);
        }

        // تكوين سياسة CSP المحسنة (لا تضف الهيدر إذا كانت السلسلة فارغة)
        $cspHeader = $this->getEnhancedCSP($isMonitoringPage);
        if (!empty($cspHeader)) {
            $response->headers->set('Content-Security-Policy', $cspHeader);
        }

        // تحسين إعدادات ملفات تعريف الارتباط
        if ($response->headers->has('Set-Cookie')) {
            $cookies = $response->headers->getCookies();
            $response->headers->remove('Set-Cookie');

            foreach ($cookies as $cookie) {
                $response->withCookie(
                    cookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        true, // secure
                        true, // httpOnly
                        true, // raw
                        'strict' // sameSite
                    )
                );
            }
        }

        // إضافة رؤوس CORS إذا كان ضرورياً
        if ($this->shouldAllowCORS($request) || $isMonitoringPage) {
            $frontendUrl = config('app.frontend_url', '*');
            $response->headers->set('Access-Control-Allow-Origin', $frontendUrl);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // إضافة HSTS (اختياري) في بيئة الإنتاج مع تجنّب الازدواجية (قد يضيفه الخادم/CDN)
        // فعّل عبر APP_ADD_HSTS=true في .env إذا لم يكن الخادم يضيف HSTS
        if (App::environment('production') && env('APP_ADD_HSTS', false)) {
            if (!$response->headers->has('Strict-Transport-Security')) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }
        }

        // إضافة رؤوس أمان إضافية
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Download-Options', 'noopen');

        return $response;
    }

    /**
     * الحصول على سياسة CSP المحسنة
     */
    protected function getEnhancedCSP(bool $isMonitoringPage = false): string
    {
        // التحقق من تفعيل CSP
        if (!Config::get('csp.enabled', true)) {
            return '';
        }

        // الحصول على إعدادات CSP من ملف التكوين
        $csp = Config::get('csp.directives', []);

        // إذا كانت إعدادات csp فارغة في ملف التكوين، لا تقم بإرسال رأس CSP من الأساس لتجنّب سياسات افتراضية متعارضة
        if (empty($csp)) {
            return '';
        }
        
        // تطبيق إعدادات خاصة لصفحات المراقبة
        if ($isMonitoringPage) {
            $monitoringOverrides = Config::get('csp.monitoring_overrides', []);
            foreach ($monitoringOverrides as $directive => $values) {
                $csp[$directive] = array_merge($csp[$directive] ?? [], $values);
            }
        }
    
        return $this->buildCSPString($csp);
    }
    

    protected function buildCSPString(array $csp): string
    {
        return implode('; ', array_map(function ($key, $values) {
            return $key . ' ' . implode(' ', $values);
        }, array_keys($csp), $csp));
    }

    /**
     * تحديد ما إذا كان يجب السماح بـ CORS للطلب
     */
    protected function shouldAllowCORS(Request $request): bool
    {
        return $request->headers->has('Origin') &&
               $request->headers->get('Origin') !== $request->getSchemeAndHttpHost();
    }

    /**
     * التحقق مما إذا كان الطلب لصفحة المراقبة
     */
    protected function isMonitoringPage(Request $request): bool
    {
        $path = $request->path();
        return strpos($path, 'dashboard/monitoring') !== false || 
               strpos($path, 'dashboard/security') !== false;
    }

    /**
     * تحديد ما إذا كانت الصفحة عامة (ليست ضمن لوحة التحكم)
     */
    protected function isPublicPage(Request $request): bool
    {
        $path = trim($request->path(), '/');
        // اعتبر جميع المسارات التي لا تبدأ بـ dashboard أو api صفحات عامة
        return !str_starts_with($path, 'dashboard') && !str_starts_with($path, 'api');
    }
}