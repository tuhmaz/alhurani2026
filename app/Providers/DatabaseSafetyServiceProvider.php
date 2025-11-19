<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Database Safety Service Provider
 *
 * يوفر حماية ضد العمليات الخطيرة على قاعدة البيانات في بيئة الإنتاج
 */
class DatabaseSafetyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // تفعيل الحماية فقط في بيئة الإنتاج
        if (app()->environment('production')) {
            $this->preventDangerousOperations();
            $this->logDatabaseOperations();
        }

        // تفعيل مراقبة الاستعلامات البطيئة في جميع البيئات
        $this->monitorSlowQueries();
    }

    /**
     * منع العمليات الخطيرة في الإنتاج
     */
    protected function preventDangerousOperations(): void
    {
        // منع DROP TABLE
        DB::prohibitDestructiveCommands();

        // إضافة listener لاكتشاف المحاولات
        DB::listen(function ($query) {
            $sql = strtoupper($query->sql);

            // قائمة بالعمليات الخطيرة
            $dangerousOperations = [
                'DROP TABLE',
                'DROP DATABASE',
                'TRUNCATE TABLE',
                'DELETE FROM' => 'without WHERE', // تحذير فقط
            ];

            foreach ($dangerousOperations as $operation => $condition) {
                if (is_numeric($operation)) {
                    $operation = $condition;
                    $condition = null;
                }

                if (str_contains($sql, $operation)) {
                    // تحقق من الشروط الإضافية
                    if ($condition === 'without WHERE' && !str_contains($sql, 'WHERE')) {
                        Log::critical('محاولة تنفيذ عملية خطيرة على قاعدة البيانات', [
                            'operation' => $operation,
                            'sql' => $query->sql,
                            'bindings' => $query->bindings,
                            'time' => $query->time,
                            'environment' => app()->environment(),
                        ]);

                        // في الإنتاج، يمكن رفع استثناء لمنع التنفيذ
                        // throw new \Exception("عملية {$operation} محظورة في بيئة الإنتاج");
                    } else {
                        Log::critical('محاولة تنفيذ عملية خطيرة على قاعدة البيانات', [
                            'operation' => $operation,
                            'sql' => $query->sql,
                            'bindings' => $query->bindings,
                            'time' => $query->time,
                            'environment' => app()->environment(),
                        ]);
                    }
                }
            }
        });
    }

    /**
     * تسجيل عمليات قاعدة البيانات الحساسة
     */
    protected function logDatabaseOperations(): void
    {
        DB::listen(function ($query) {
            $sql = strtoupper($query->sql);

            // العمليات التي يجب تسجيلها
            $sensitiveOperations = [
                'INSERT',
                'UPDATE',
                'DELETE',
                'ALTER TABLE',
                'CREATE TABLE',
                'DROP',
                'TRUNCATE',
            ];

            foreach ($sensitiveOperations as $operation) {
                if (str_contains($sql, $operation)) {
                    Log::info('عملية قاعدة بيانات حساسة', [
                        'operation' => $operation,
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'connection' => $query->connectionName,
                    ]);
                    break;
                }
            }
        });
    }

    /**
     * مراقبة الاستعلامات البطيئة
     */
    protected function monitorSlowQueries(): void
    {
        // تحديد الحد الأقصى للوقت (بالميلي ثانية)
        $slowQueryThreshold = config('database.slow_query_threshold', 1000);

        DB::listen(function ($query) use ($slowQueryThreshold) {
            if ($query->time > $slowQueryThreshold) {
                Log::warning('استعلام بطيء تم اكتشافه', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                    'threshold' => $slowQueryThreshold . 'ms',
                ]);
            }
        });
    }
}
