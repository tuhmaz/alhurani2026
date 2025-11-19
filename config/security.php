<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | تكوينات أمنية شاملة للتطبيق
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Brute Force Protection
    |--------------------------------------------------------------------------
    */
    'brute_force' => [
        'enabled' => env('SECURITY_BRUTE_FORCE_ENABLED', true),
        'max_attempts' => env('SECURITY_BRUTE_FORCE_MAX_ATTEMPTS', 5),
        'temp_block_minutes' => env('SECURITY_BRUTE_FORCE_TEMP_BLOCK', 15),
        'permanent_ban_attempts' => env('SECURITY_BRUTE_FORCE_PERMANENT_BAN', 20),
        'sensitive_paths' => [
            'login',
            'register',
            'password/email',
            'password/reset',
            'two-factor-challenge',
            'api/login',
            'api/register',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Activity Detection
    |--------------------------------------------------------------------------
    */
    'suspicious_activity' => [
        'enabled' => env('SECURITY_SUSPICIOUS_ACTIVITY_ENABLED', true),
        'rapid_requests_threshold' => env('SECURITY_RAPID_REQUESTS_THRESHOLD', 10),
        'rapid_requests_window' => env('SECURITY_RAPID_REQUESTS_WINDOW', 5), // seconds
        'auto_ban_score' => env('SECURITY_AUTO_BAN_SCORE', 80),
        'warning_score' => env('SECURITY_WARNING_SCORE', 50),

        'suspicious_user_agents' => [
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
        ],

        'suspicious_extensions' => [
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
        ],

        'sensitive_files' => [
            '.env',
            'wp-config.php',
            'config.php',
            'database.php',
            'phpinfo.php',
            'web.config',
            'settings.php',
            'configuration.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Filtering
    |--------------------------------------------------------------------------
    */
    'ip_filtering' => [
        'enabled' => env('SECURITY_IP_FILTERING_ENABLED', true),

        // قائمة بيضاء - السماح فقط لهذه IPs (إذا كانت فارغة = السماح للجميع)
        'whitelist' => array_filter(explode(',', env('SECURITY_IP_WHITELIST', ''))),

        // قائمة سوداء - حظر هذه IPs
        'blacklist' => array_filter(explode(',', env('SECURITY_IP_BLACKLIST', ''))),

        // السماح بـ localhost
        'allow_localhost' => env('SECURITY_ALLOW_LOCALHOST', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),

        'content_security_policy' => env('SECURITY_CSP',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google-analytics.com https://pagead2.googlesyndication.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https: http:; connect-src 'self' https://www.google-analytics.com; frame-src https://www.google.com https://www.youtube.com https://googleads.g.doubleclick.net https://tpc.googlesyndication.com"
        ),

        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'strict_transport_security' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains'),

        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY',
            'geolocation=(), microphone=(), camera=(), payment=()'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    */
    'input_validation' => [
        'enabled' => env('SECURITY_INPUT_VALIDATION_ENABLED', true),

        // فحص SQL Injection
        'sql_injection_check' => env('SECURITY_SQL_INJECTION_CHECK', true),

        // فحص XSS
        'xss_check' => env('SECURITY_XSS_CHECK', true),

        // فحص Path Traversal
        'path_traversal_check' => env('SECURITY_PATH_TRAVERSAL_CHECK', true),

        // الحد الأقصى لحجم الطلب (بالميجابايت)
        'max_request_size' => env('SECURITY_MAX_REQUEST_SIZE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    */
    'file_upload' => [
        'enabled' => env('SECURITY_FILE_UPLOAD_ENABLED', true),

        // أنواع MIME المسموحة
        'allowed_mime_types' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],

        // الحد الأقصى لحجم الملف (بالميجابايت)
        'max_file_size' => env('SECURITY_MAX_FILE_SIZE', 5),

        // فحص محتوى الملف (deep scan)
        'deep_scan' => env('SECURITY_FILE_DEEP_SCAN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'enabled' => env('SECURITY_SESSION_ENABLED', true),

        // فترة انتهاء الجلسة (بالدقائق)
        'lifetime' => env('SECURITY_SESSION_LIFETIME', 120),

        // انتهاء الجلسة المطلق (بالدقائق)
        'absolute_timeout' => env('SECURITY_SESSION_ABSOLUTE_TIMEOUT', 480),

        // فحص IP للجلسة (منع اختطاف الجلسة)
        'ip_check' => env('SECURITY_SESSION_IP_CHECK', true),

        // فحص User Agent للجلسة
        'user_agent_check' => env('SECURITY_SESSION_USER_AGENT_CHECK', true),

        // تجديد الجلسة بعد تسجيل الدخول
        'regenerate_on_login' => env('SECURITY_SESSION_REGENERATE_LOGIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */
    'csrf' => [
        'enabled' => env('SECURITY_CSRF_ENABLED', true),

        // فترة صلاحية CSRF Token (بالدقائق)
        'token_lifetime' => env('SECURITY_CSRF_TOKEN_LIFETIME', 120),

        // تجديد Token تلقائياً
        'auto_regenerate' => env('SECURITY_CSRF_AUTO_REGENERATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('SECURITY_LOGGING_ENABLED', true),

        // تسجيل محاولات تسجيل الدخول الفاشلة
        'log_failed_logins' => env('SECURITY_LOG_FAILED_LOGINS', true),

        // تسجيل الوصول غير المصرح به
        'log_unauthorized_access' => env('SECURITY_LOG_UNAUTHORIZED_ACCESS', true),

        // تسجيل التغييرات الحساسة
        'log_sensitive_changes' => env('SECURITY_LOG_SENSITIVE_CHANGES', true),

        // تسجيل الأنشطة المشبوهة
        'log_suspicious_activities' => env('SECURITY_LOG_SUSPICIOUS_ACTIVITIES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Requirements
    |--------------------------------------------------------------------------
    */
    'password' => [
        'enabled' => env('SECURITY_PASSWORD_REQUIREMENTS_ENABLED', true),

        // الحد الأدنى لطول كلمة المرور
        'min_length' => env('SECURITY_PASSWORD_MIN_LENGTH', 8),

        // يجب أن تحتوي على حروف كبيرة
        'require_uppercase' => env('SECURITY_PASSWORD_REQUIRE_UPPERCASE', true),

        // يجب أن تحتوي على حروف صغيرة
        'require_lowercase' => env('SECURITY_PASSWORD_REQUIRE_LOWERCASE', true),

        // يجب أن تحتوي على أرقام
        'require_numbers' => env('SECURITY_PASSWORD_REQUIRE_NUMBERS', true),

        // يجب أن تحتوي على رموز خاصة
        'require_special_chars' => env('SECURITY_PASSWORD_REQUIRE_SPECIAL', true),

        // منع إعادة استخدام آخر N كلمات مرور
        'prevent_reuse' => env('SECURITY_PASSWORD_PREVENT_REUSE', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'enabled' => env('SECURITY_2FA_ENABLED', true),

        // إلزامي للمسؤولين
        'required_for_admins' => env('SECURITY_2FA_REQUIRED_ADMINS', true),

        // اختياري للمستخدمين
        'optional_for_users' => env('SECURITY_2FA_OPTIONAL_USERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    */
    'database' => [
        // منع Mass Assignment
        'strict_mass_assignment' => env('SECURITY_STRICT_MASS_ASSIGNMENT', true),

        // تشفير البيانات الحساسة
        'encrypt_sensitive_data' => env('SECURITY_ENCRYPT_SENSITIVE_DATA', true),

        // استخدام Prepared Statements فقط
        'use_prepared_statements' => env('SECURITY_USE_PREPARED_STATEMENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Security
    |--------------------------------------------------------------------------
    */
    'email' => [
        // منع email enumeration
        'prevent_enumeration' => env('SECURITY_PREVENT_EMAIL_ENUMERATION', true),

        // فحص صحة عنوان البريد الإلكتروني
        'validate_mx_records' => env('SECURITY_VALIDATE_MX_RECORDS', false),

        // الحد الأقصى لإرسال الرسائل في الساعة
        'max_emails_per_hour' => env('SECURITY_MAX_EMAILS_PER_HOUR', 10),
    ],
];
