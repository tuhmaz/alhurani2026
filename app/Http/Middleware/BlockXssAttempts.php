<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BannedIp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class BlockXssAttempts
{
  protected $xssPatterns = [
    '/<script\b[^>]*>(.*?)<\/script>/is',
    '/on\w+\s*=/i',
    '/javascript\s*:/i',
    '/<\?php/i',
    '/<%\?/i',
    '/eval\s*\(/i',
    '/document\./i',
    '/alert\s*\(/i',
    '/<iframe/i',
    '/<object/i',
    '/<embed/i',
    '/<applet/i',
    '/<meta/i',
    '/<link/i',
    '/<style/i',
    // ملاحظة: السماح بعناصر الصور كي يعمل إدراج الصور في المحرر
    '/<svg/i',
    '/<form/i',
    '/<input/i',
    '/<button/i',
    '/<select/i',
    '/<textarea/i',
  ];
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    // تخطي طلبات API
    if ($request->is('api/*')) {
      return $next($request);
    }

    // فحص جميع البيانات الواردة
    $input = $request->all();
    $ip = $request->ip();

    foreach ($input as $key => $value) {
      if (is_array($value) || is_object($value)) {
        continue;
      }

      if ($this->isXssAttempt($value)) {
        // تسجيل المحاولة
        Log::warning('XSS attempt detected', [
          'ip' => $ip,
          'method' => $request->method(),
          'url' => $request->fullUrl(),
          'input' => $input
        ]);

        // حظر الأيبي
        $this->banIp($ip, 'XSS attempt detected in ' . $key);

        return response()->json([
          'error' => 'Access denied'
        ], 403);
      }
    }

    return $next($request);
  }

  protected function isXssAttempt($input)
  {
    foreach ($this->xssPatterns as $pattern) {
      if (preg_match($pattern, $input)) {
        return true;
      }
    }
    return false;
  }

  protected function banIp($ip, $reason)
  {
    BannedIp::updateOrCreate(
      ['ip' => $ip],
      [
        'reason' => $reason,
        'banned_until' => now()->addDays(30) // حظر لمدة 30 يوم
      ]
    );
  }
}
