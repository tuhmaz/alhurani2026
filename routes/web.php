<?php

use App\Http\Controllers\Api\SocialAuthController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageProxyController;
use App\Http\Middleware\CacheControlMiddleware;

use App\Http\Controllers\{
  AuthController,
  DashboardController,
  UserController,
  RoleController,
  PermissionController,
  SchoolClassController,
  SubjectController,
  SemesterController,
  FileController,
  ArticleController,
  ImageUploadController,
  CategoryController,
  PostController,
  FrontendPostController,
  KeywordController,
  MessageController,
  SettingsController,
  HomeController,
  NotificationController,
  PerformanceController,
  SecurityLogController,
  SecurityMonitorController,
  RedisController,
  CalendarController,
  CommentController,
  ReactionController,
  LegalController,
  SitemapController,
  GradeOneController,
  FilterController,
  FrontController,
  VerifyEmailController,
  OneSignalSettingsController,
  ActivityController, // Added ActivityController
  RateLimitLogController, // Added RateLimitLogController
  AnalyticsController, // Added AnalyticsController
  SecureFileController // Added SecureFileController
};

use App\Http\Controllers\Language\LanguageController;
use App\Http\Controllers\Dashboard\Monitoring\VisitorController;
use App\Http\Controllers\Dashboard\Monitoring\SecurityController;
use App\Http\Controllers\Dashboard\Monitoring\BanController;
use App\Http\Controllers\Dashboard\LegacyImportController;
use App\Http\Controllers\Dashboard\chating\ChatController;
use App\Http\Controllers\Dashboard\chating\ChatPageController;
use App\Http\Controllers\Dashboard\chating\ChatActionController;
use App\Http\Controllers\Dashboard\settings\ReportsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
// مثال: إعادة توجيه روابط vb/node/{id} إلى صفحة مقالات جديدة
Route::get('/vb/node/{id}', function ($id) {
    // إذا عندك جدول مقالات قديم يربط ID القديم بالجديد، استبدل هذا حسب الحاجة
    // مؤقتًا نعيد التوجيه للصفحة الرئيسية أو صفحة المقالات
    return redirect('https://alemancenter.com/articles/' . $id, 301);
});

// مثال: إعادة توجيه vb/search إلى صفحة البحث الجديدة
Route::get('/vb/search', function () {
    return redirect('https://alemancenter.com/search', 301);
});

// أي روابط vb أخرى غير معرفة → تحويل للصفحة الرئيسية
Route::get('/vb/{any}', function () {
    return redirect('https://alemancenter.com', 301);
})->where('any', '.*');
// أي رابط قديم من نظام vb (node, search, الخ) → توجيه للصفحة الرئيسية

// نفس الشيء لو عندك forum/ أو threads/ من النظام القديم
Route::get('/forum/{any?}', function () {
    return redirect('https://alemancenter.com', 301);
})->where('any', '.*');

Route::get('/threads/{any?}', function () {
    return redirect('https://alemancenter.com', 301);
})->where('any', '.*');

// أي رابط يبدأ بـ /up/ → إعادة توجيه للصفحة الرئيسية
Route::get('/up/{any?}', function () {
    return redirect('https://alemancenter.com', 301);
})->where('any', '.*');

// إعادة توجيه مباشر للملف do.php وأي استعلام (id=...) → الصفحة الرئيسية
Route::get('/up/do.php', function () {
    return redirect('https://alemancenter.com', 301);
});

// الصفحة الرئيسية
Route::get('/', [HomeController::class, 'index'])->name('home');

// تغيير اللغة
Route::post('/set-database', [HomeController::class, 'setDatabase'])->name('setDatabase');
Route::get('/lang/{locale}', [LanguageController::class, 'swap'])->name('dashboard.lang-swap');

// Authentication Routes
Route::middleware('guest')->group(function () {
  Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
  Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
  Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
  Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

  // Google OAuth Routes
  Route::get('login/google', [AuthController::class, 'googleRedirect'])->name('login.google');
  Route::get('login/google/callback', [AuthController::class, 'googleCallback'])->name('login.google.callback');

  // Password Reset Routes
  Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
  Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
  Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
  Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Email Verification Routes
Route::middleware('auth')->group(function () {
  Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
  Route::post('/email/verification-notification', [AuthController::class, 'verificationResend'])
    ->middleware('throttle:6,1')
    ->name('verification.send');
  Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');
});

// Upload Routes
Route::prefix('upload')->group(function () {
  Route::post('/image', [ImageUploadController::class, 'upload'])->name('upload.image');
  Route::post('/file', [ImageUploadController::class, 'uploadFile'])->name('upload.file');
});

// Dashboard Routes (Authenticated and Verified Users)
Route::middleware(['auth', 'verified', \App\Http\Middleware\MarkUserOnline::class])
  ->prefix('dashboard')
  ->name('dashboard.')
  ->group(function () {
    // Dashboard Home
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');

    // Static pages: Documentation and Support (Dashboard)
    Route::get('/documentation', function () {
      return view('content.dashboard.documentation');
    })->name('documentation');

    Route::get('/support', function () {
      return view('content.dashboard.support');
    })->name('support');

    //
    Route::get('/legacy-import', [LegacyImportController::class, 'create'])
      ->name('legacy-import.create');
    Route::post('/legacy-import/test', [LegacyImportController::class, 'test'])
      ->name('legacy-import.test');
    Route::post('/legacy-import/run', [LegacyImportController::class, 'run'])
      ->name('legacy-import.run');
    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->middleware(['can:manage monitoring'])->group(function () {
      Route::get('/visitors', [AnalyticsController::class, 'index'])->name('visitors');
      Route::get('/visitors/data', [AnalyticsController::class, 'visitors'])->name('visitors.data');
    });

    // تطبيق تقييد معدل الطلبات على مسارات المسؤول الحساسة
    Route::middleware(['throttle:60,1'])->group(function () {
      // User Management Routes
      Route::prefix('users')->name('users.')->middleware(['can:manage users'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
      });

      // Role Management Routes
      Route::prefix('roles')->name('roles.')->middleware(['can:manage roles'])->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
      });

      // Permission Management Routes
      Route::prefix('permissions')->name('permissions.')->middleware(['can:manage permissions'])->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/create', [PermissionController::class, 'create'])->name('create');
        Route::post('/', [PermissionController::class, 'store'])->name('store');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
      });

      // Settings Routes
      Route::prefix('settings')->name('settings.')->middleware(['can:manage settings'])->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('/test-smtp', [SettingsController::class, 'testSMTPConnection'])->name('test-smtp');
        Route::post('/test-email', [SettingsController::class, 'sendTestEmail'])->name('test-email');
        Route::get('/onesignal', [OneSignalSettingsController::class, 'index'])->name('onesignal');
        Route::put('/onesignal', [OneSignalSettingsController::class, 'update'])->name('updateOneSignal');
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports')->middleware('can:manage reports');
        Route::get('/reports/{report}', [ReportsController::class, 'show'])->name('reports.show')->middleware('can:manage reports');
        Route::post('/reports/{report}/message', [ReportsController::class, 'message'])->name('reports.message')->middleware('can:manage reports');
        Route::post('/reports/{report}/status', [ReportsController::class, 'updateStatus'])->name('reports.status')->middleware('can:manage reports');
      });

      // مسارات إدارة سجلات تقييد معدل الطلبات
      Route::prefix('security')->name('security.')->middleware(['can:manage security'])->group(function () {
        // مسارات سجلات تقييد معدل الطلبات
        Route::get('/rate-limit-logs', [RateLimitLogController::class, 'index'])->name('rate-limit-logs.index');
        Route::delete('/rate-limit-logs/{log}', [RateLimitLogController::class, 'destroy'])->name('rate-limit-logs.destroy');
        Route::delete('/rate-limit-logs', [RateLimitLogController::class, 'destroyAll'])->name('rate-limit-logs.destroy-all');
        Route::post('/rate-limit-logs/block-ip', [RateLimitLogController::class, 'blockIp'])->name('rate-limit-logs.block-ip');

        // مسارات مراقبة الأمان
        Route::get('/monitor', [SecurityMonitorController::class, 'dashboard'])->name('monitor');
        Route::get('/alerts', [SecurityMonitorController::class, 'alerts'])->name('alerts');
        Route::get('/alerts/{log}', [SecurityMonitorController::class, 'showAlert'])->name('alerts.show');
        Route::put('/alerts/{log}', [SecurityMonitorController::class, 'updateAlert'])->name('alerts.update');
        Route::get('/export-report', [SecurityMonitorController::class, 'exportReport'])->name('export-report');

        // مسارات سجلات الأمان
        Route::get('/logs', [SecurityLogController::class, 'logs'])->name('logs');
        Route::get('/logs/{log}', [SecurityLogController::class, 'show'])->name('logs.show');
        Route::post('/run-scan', [SecurityMonitorController::class, 'runScan'])->name('run-scan');
        Route::post('/logs/{log}/resolve', [SecurityLogController::class, 'resolve'])->name('logs.resolve');
        Route::delete('/logs/{log}', [SecurityLogController::class, 'destroy'])->name('logs.destroy');
        Route::delete('/logs', [SecurityLogController::class, 'destroyAll'])->name('logs.destroyAll');
        Route::get('/export', [SecurityLogController::class, 'export'])->name('logs.export');

        // مسارات عناوين IP المحظورة والموثوقة
        Route::get('/blocked-ips', [SecurityLogController::class, 'blockedIps'])->name('blocked-ips');
        Route::get('/trusted-ips', [SecurityLogController::class, 'trustedIps'])->name('trusted-ips');
        Route::post('/block-ip', [SecurityLogController::class, 'blockIp'])->name('block-ip');
        Route::post('/trust-ip', [SecurityLogController::class, 'trustIp'])->name('trust-ip');
        Route::post('/unblock-ip', [SecurityLogController::class, 'unblockIp'])->name('unblock-ip');
        Route::post('/untrust-ip', [SecurityLogController::class, 'untrustIp'])->name('untrust-ip');
        Route::get('/ip-details/{ip}', [SecurityLogController::class, 'ipDetails'])->name('ip-details');
      });
    });

    // Chat Page
    Route::get('chating', [ChatPageController::class, 'index'])->name('chating')->middleware('can:manage chating');
    // Chat
    Route::prefix('chat')->name('chat.')->middleware('can:manage chating')->group(function () {
      // get all conversations available to the user
      Route::get('/conversations', [ChatController::class, 'conversations'])->name('conversations');
      // get messages for a specific conversation
      Route::get('/conversations/{id}/messages', [ChatController::class, 'messages'])->name('messages');
      // send a message to a conversation
      Route::post('/conversations/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
      // Public chat get-or-create and return id/title
      Route::get('/public', [ChatController::class, 'publicConversation'])->name('public');
      // create or get a private conversation between users
      Route::post('/conversations/private', [ChatController::class, 'createPrivateConversation'])->name('createPrivateConversation');
      // create or get a public conversation (by name)
      Route::post('/conversations/public', [ChatController::class, 'createPublicConversation'])->name('createPublicConversation');
      Route::get('/unread-counts', [ChatController::class, 'unreadCounts'])->name('unread-counts');
      // جلب جميع المستخدمين (عدا المستخدم الحالي)
      Route::get('/users', [ChatController::class, 'users'])->name('users');
      Route::get('/user/{id}', [ChatController::class, 'getUser'])->name('user');
      // حظر مستخدم من الدردشة
      Route::post('block/{id}', [ChatActionController::class, 'blockChat'])->name('block');
      // فك الحظر عن مستخدم
      Route::post('unblock/{id}', [ChatActionController::class, 'unblockChat'])->name('unblock');
      // التحقق من حالة الحظر بين المستخدمين
      Route::get('block-status/{id}', [ChatActionController::class, 'checkBlockStatus'])->name('block-status');
      // إرسال تقرير إساءة
      Route::post('report/{id}', [ChatActionController::class, 'reportAbuse'])->name('report');
      // إرسال رسالة (نسخة مع مراعاة الحظر)
      Route::post('send-message/{conversation}', [ChatActionController::class, 'sendMessage'])->name('send-message');
      // Clear chat messages (support both POST and DELETE)
      Route::match(['POST', 'DELETE'], 'clear/{conversation}', [ChatActionController::class, 'clearChat'])->name('clear');
      // Hide (remove from my list) a private conversation
      Route::delete('hide/{conversation}', [ChatActionController::class, 'hideConversation'])->name('hide');
    });

    // Comments Routes (Dashboard)
    Route::get('/comments', [\App\Http\Controllers\Dashboard\CommentsController::class, 'index'])
        ->name('comments.index')->middleware('can:manage posts');

    // Message Routes
    Route::prefix('messages')->name('messages.')->group(function () {
      Route::get('/', [MessageController::class, 'index'])->name('index');
      Route::get('/compose', [MessageController::class, 'compose'])->name('compose');
      Route::post('/send', [MessageController::class, 'send'])->name('send');
      Route::post('/draft', [MessageController::class, 'saveDraft'])->name('draft.save');
      Route::get('/sent', [MessageController::class, 'sent'])->name('sent');
      Route::get('/drafts', [MessageController::class, 'drafts'])->name('drafts');
      Route::get('/trash', [MessageController::class, 'trash'])->name('trash');
      Route::get('/received', [MessageController::class, 'received'])->name('received');
      Route::get('/important', [MessageController::class, 'important'])->name('important');
      Route::get('/{id}/reply', [MessageController::class, 'reply'])->name('reply');
      Route::post('/{id}/send-reply', [MessageController::class, 'sendReply'])->name('send-reply');
      Route::post('/{id}/mark-as-read', [MessageController::class, 'markAsRead'])->name('mark-as-read');
      Route::post('/{id}/mark-as-unread', [MessageController::class, 'markAsUnread'])->name('mark-as-unread');
      Route::get('/{id}', [MessageController::class, 'show'])->name('show');
      Route::delete('/{id}', [MessageController::class, 'delete'])->name('delete');
    });

    // User Management Routes
    // مسارات خاصة بالمسؤولين فقط

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::middleware(['can:admin users'])->group(function () {
      Route::get('users/create', [UserController::class, 'create'])->name('users.create');
      Route::post('users', [UserController::class, 'store'])->name('users.store');
      Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
      Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    });

    // مسارات يمكن للمستخدم العادي الوصول إليها (للملف الشخصي)

    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show')->middleware('can:manage users');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:manage users');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:manage users');

    // مسارات إدارة الأدوار والصلاحيات (للمسؤولين فقط)
    Route::get('users/{user}/permissions-roles', [UserController::class, 'permissions_roles'])
      ->name('users.permissions-roles')->middleware('can:manage roles');
    Route::put('users/{user}/permissions-roles', [UserController::class, 'update_permissions_roles'])
      ->name('users.update-permissions-roles')->middleware('can:manage permissions');

    // Role Management Routes

    // Permission Management Routes
    // School Class Management Routes
    Route::resource('school-classes', SchoolClassController::class)->middleware(['can:manage school classes']);

    // Subject Management Routes
    Route::resource('subjects', SubjectController::class)->middleware(['can:manage subjects']);

    // Semester Management Routes
    Route::resource('semesters', SemesterController::class)->middleware(['can:manage semesters']);

    // Article Management Routes
    Route::prefix('articles')->name('articles.')->group(function () {
      Route::get('/', [ArticleController::class, 'index'])->name('index')->middleware(['can:manage articles']);
      Route::get('/create', [ArticleController::class, 'create'])->name('create')->middleware(['can:manage articles']);
      Route::post('/', [ArticleController::class, 'store'])->name('store');
      Route::get('/{article}', [ArticleController::class, 'show'])->name('show');
      Route::get('/{article}/edit', [ArticleController::class, 'edit'])->name('edit');
      Route::put('/{article}', [ArticleController::class, 'update'])->name('update');
      Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('destroy');
      Route::post('/{article}/publish', [ArticleController::class, 'publish'])->name('publish');
      Route::post('/{article}/unpublish', [ArticleController::class, 'unpublish'])->name('unpublish');
      Route::post('/upload-file', [ArticleController::class, 'uploadFile'])->name('upload-file');
      Route::post('/remove-file', [ArticleController::class, 'removeFile'])->name('remove-file');
    });

    // Posts Management Routes (renamed from posts)
    Route::prefix('posts')->name('posts.')->group(function () {
      Route::get('/', [PostController::class, 'index'])->name('index')->middleware(['can:manage posts']);
      Route::get('/create', [PostController::class, 'create'])->name('create')->middleware(['can:manage posts']);
      Route::post('/', [PostController::class, 'store'])->name('store');
      Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
      Route::put('/{post}', [PostController::class, 'update'])->name('update');
      Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
      Route::patch('/{post}/toggle-status', [PostController::class, 'toggleStatus'])->name('toggle-status');
      Route::patch('/{post}/toggle-featured', [PostController::class, 'toggleFeatured'])->name('toggle-featured');
      Route::get('/data', [PostController::class, 'getData'])->name('data');
      // Post Attachments
      Route::delete('/{post}/attachments/{file}', [PostController::class, 'destroyAttachment'])->name('attachments.destroy');
    });

    // Category Management Routes
    Route::prefix('categories')->name('categories.')->group(function () {
      Route::get('/', [CategoryController::class, 'index'])->name('index')->middleware(['can:manage categories']);
      Route::get('/create', [CategoryController::class, 'create'])->name('create')->middleware(['can:manage categories']);
      Route::post('/', [CategoryController::class, 'store'])->name('store');
      Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
      Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
      Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
      Route::post('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    // File Management Routes
    Route::prefix('files')->name('files.')->group(function () {
      Route::get('/', [FileController::class, 'index'])->name('index');
      Route::get('/create', [FileController::class, 'create'])->name('create');
      Route::post('/', [FileController::class, 'store'])->name('store');
      Route::get('/{file}', [FileController::class, 'show'])->name('show');
      Route::get('/{file}/edit', [FileController::class, 'edit'])->name('edit');
      Route::put('/{file}', [FileController::class, 'update'])->name('update');
      Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
      Route::get('/{file}/download', [FileController::class, 'download'])->name('download');
    });

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
      Route::get('/', [NotificationController::class, 'index'])->name('index');
      Route::get('/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
      Route::get('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
      Route::post('/delete-selected', [NotificationController::class, 'deleteSelected'])->name('delete-selected');
      Route::post('/handle-actions', [NotificationController::class, 'handleActions'])->name('handle-actions');
      Route::get('/delete/{id}', [NotificationController::class, 'delete'])->name('delete');
      // JSON endpoint for navbar bell (session-auth)
      Route::get('/json', [NotificationController::class, 'json'])->name('json');
    });


    // مسارات المراقبة
    // Monitoring Routes
    Route::prefix('monitoring')->name('monitoring.')->middleware(['can:manage monitoring'])->group(function () {
      // Visitors
      Route::get('visitors', [VisitorController::class, 'index'])->name('visitors.index');
      Route::get('visitors/{session}', [VisitorController::class, 'show'])->name('visitors.show');
      Route::delete('visitors/{session}', [VisitorController::class, 'destroy'])->name('visitors.destroy');

      // Security Logs
      Route::get('security', [SecurityController::class, 'index'])->name('security.index');
      Route::get('security/export', [SecurityController::class, 'export'])->name('security.export');
      Route::get('security/{log}', [SecurityController::class, 'show'])->name('security.show');

      // IP Bans
      Route::get('bans', [BanController::class, 'index'])->name('bans.index');
      Route::post('bans', [BanController::class, 'store'])->name('bans.store');
      Route::delete('bans/{ban}', [BanController::class, 'destroy'])->name('bans.destroy');
      Route::post('bans/{ban}/unban', [BanController::class, 'unban'])->name('bans.unban');

      // API Endpoints
      Route::prefix('api')->name('api.')->group(function () {
        Route::get('visitors', [VisitorController::class, 'getVisitors'])->name('visitors.list');
        Route::get('visitors/{session}/activity', [VisitorController::class, 'getActivity'])->name('visitors.activity');
        Route::post('bans/check', [BanController::class, 'check'])->name('bans.check');
        Route::post('bans/bulk', [BanController::class, 'bulkAction'])->name('bans.bulk');
        Route::post('bans/bulk/unban', [BanController::class, 'bulkUnban'])->name('bans.bulk.unban');
      });
    });



    // Performance Routes
    Route::prefix('performance')->name('performance.')->group(function () {
      Route::get('/', [PerformanceController::class, 'index'])->name('index')->middleware(['can:manage performance']);
      Route::get('/metrics', [PerformanceController::class, 'getMetrics'])->name('metrics');
      Route::get('/metrics/data', [PerformanceController::class, 'getMetricsData'])->name('data');
    });

    // Security Routes
    Route::prefix('security')->name('security.')->group(function () {
      Route::get('/', [SecurityLogController::class, 'index'])->name('index')->middleware(['can:manage security']);
      Route::get('/logs', [SecurityLogController::class, 'logs'])->name('logs');
      Route::get('/analytics', [SecurityLogController::class, 'analytics'])->name('analytics')->middleware(['can:manage security']);
      Route::get('/blocked-ips', [SecurityLogController::class, 'blockedIps'])->name('blocked-ips');
      Route::get('/trusted-ips', [SecurityLogController::class, 'trustedIps'])->name('trusted-ips');
      Route::get('/logs/{log}', [SecurityLogController::class, 'show'])->name('logs.show');
      Route::post('/logs/{log}/resolve', [SecurityLogController::class, 'resolve'])->name('logs.resolve');
      Route::delete('/logs/{log}', [SecurityLogController::class, 'destroy'])->name('logs.destroy');
      Route::post('/blocked-ips', [SecurityLogController::class, 'blockIp'])->name('security.block-ip');
      Route::post('/trusted-ips', [SecurityLogController::class, 'trustIp'])->name('security.trust-ip');
      Route::get('/export', [SecurityLogController::class, 'export'])->name('export');
    });

    // Sitemap Routes
    Route::prefix('sitemap')->name('sitemap.')->group(function () {
      Route::get('/', [SitemapController::class, 'index'])->name('index')->middleware('can:manage sitemap');
      Route::get('/manage', [SitemapController::class, 'manageIndex'])->name('manage');
      Route::match(['get', 'post'], '/generate', [SitemapController::class, 'generate'])->name('generate');
      Route::post('/update-inclusion', [SitemapController::class, 'updateResourceInclusion'])->name('update-inclusion');
      Route::delete('/{type}/{database}', [SitemapController::class, 'delete'])->name('delete');
    });

    // Calendar Routes
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar-events', [CalendarController::class, 'getEvents'])->name('calendar.events');
    Route::get('/dashboard/calendar-events', [CalendarController::class, 'getEvents'])->name('dashboard.calendar.events');
    Route::post('/calendar/store', [CalendarController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{event}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    // Redis Routes
    Route::prefix('redis')->name('redis.')->middleware('can:manage redis')->group(function () {
      Route::get('/', [RedisController::class, 'index'])->name('index');
      Route::post('/add', [RedisController::class, 'addKey'])->name('addKey');
      Route::delete('/delete/{key}', [RedisController::class, 'deleteKey'])->name('deleteKey');
      Route::post('/clean-keys', [RedisController::class, 'cleanKeys'])->name('cleanKeys');
      Route::get('/env-settings', [RedisController::class, 'showEnvSettings'])->name('envSettings');
      Route::post('/update-env', [RedisController::class, 'updateEnvSettings'])->name('updateEnv');
      Route::get('/test', [RedisController::class, 'testRedisConnection'])->name('testRedisConnection');

      // Cache Monitoring Routes
      Route::get('/monitoring', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'monitoring'])->name('monitoring');
      Route::get('/stats', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'getStats'])->name('stats');
      Route::post('/optimize', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'optimize'])->name('optimize');
      Route::post('/analyze-key', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'analyzeKey'])->name('analyze-key');
      Route::post('/apply-recommendation', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'applyRecommendation'])->name('apply-recommendation');
      Route::post('/clean-expired', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'cleanExpiredKeys'])->name('clean-expired');
      Route::post('/add-key', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'addKey'])->name('add-key');
      Route::delete('/delete-key/{key}', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'deleteKey'])->name('delete-key');
      Route::get('/export-report', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'exportReport'])->name('export-report');
      Route::get('/search-keys', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'searchKeys'])->name('search-keys');
      Route::get('/key-details/{key}', [\App\Http\Controllers\Dashboard\RedisCacheController::class, 'getKeyDetails'])->name('key-details');
    });

    // Activities routes
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/load-more', [ActivityController::class, 'loadMore'])->name('activities.load-more');
    Route::get('/activities/clean-old', [ActivityController::class, 'cleanOldActivities'])->name('activities.clean-old')->middleware('can:manage activities');
  });

// Image proxy for responsive resized assets (cached)
Route::get('/img/fit/{size}/{path}', [ImageProxyController::class, 'fit'])
  ->where(['path' => '.*', 'size' => '\\d+x\\d+'])
  ->name('img.fit');

// Frontend Routes
Route::get('/members', [FrontController::class, 'members'])->name('front.members');
Route::get('/members/{id}', [FrontController::class, 'showMember'])->name('front.members.show');
Route::post('/members/{id}/contact', [FrontController::class, 'contactMember'])->name('front.members.contact');

Route::prefix('{database}')
  ->where(['database' => 'jo|sa|eg|ps'])
  ->group(function () {
  Route::prefix('lesson')->group(function () {
    Route::get('/', [GradeOneController::class, 'index'])->name('frontend.lesson.index');
    Route::get('/{id}', [GradeOneController::class, 'show'])->name('frontend.lesson.show');
    Route::get('subjects/{subject}', [GradeOneController::class, 'showSubject'])->name('frontend.subjects.show');
    Route::get('subjects/{subject}/articles/{semester}/{category}', [GradeOneController::class, 'subjectArticles'])->name('frontend.subject.articles');
    Route::get('/articles/{article}', [GradeOneController::class, 'showArticle'])->name('frontend.articles.show');
    Route::get('files/download/{id}', [FileController::class, 'downloadFile'])->name('files.download');
  });

  // Keywords for the frontend
  Route::get('/keywords', [KeywordController::class, 'index'])->name('frontend.keywords.index');
  Route::get('/keywords/{keywords}', [KeywordController::class, 'indexByKeyword'])->name('keywords.indexByKeyword');

  // Posts Routes for the frontend (renamed from posts)
  Route::prefix('posts')->group(function () {
    Route::get('/', [FrontendPostController::class, 'index'])->name('content.frontend.posts.index');
    Route::get('/category/{category}', [FrontendPostController::class, 'category'])->name('content.frontend.posts.category');
    Route::get('/filter', [FrontendPostController::class, 'filterNewsByCategory'])->name('content.frontend.posts.filter');
    Route::get('/{id}', [FrontendPostController::class, 'show'])->name('content.frontend.posts.show');

    // Comments & Reactions Routes
    Route::middleware(['auth'])->group(function () {
      Route::post('/comments', [CommentController::class, 'store'])->name('frontend.comments.store');
      Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('frontend.comments.destroy');
      Route::post('/reactions', [ReactionController::class, 'store'])->name('frontend.reactions.store');
    });
  });
});

// Front Pages Routes
Route::get('/about-us', [FrontController::class, 'aboutUs'])->name('about.us');
Route::get('/contact-us', [FrontController::class, 'contactUs'])->name('contact.us');
Route::post('/contact-us', [FrontController::class, 'submitContact'])
  ->middleware('throttle:5,1')
  ->name('contact.submit');


// Categories for the frontend (خارج مجموعة database)
Route::get('/categories', [CategoryController::class, 'frontIndex'])->name('content.frontend.categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('content.frontend.categories.show');

// Legal Routes
Route::get('privacy-policy', [LegalController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-of-service', [LegalController::class, 'termsOfService'])->name('terms-of-service');
Route::get('cookie-policy', [LegalController::class, 'cookiePolicy'])->name('cookie-policy');
Route::get('disclaimer', [LegalController::class, 'disclaimer'])->name('disclaimer');

// Filter and API Routes
Route::get('/filter-files', [FilterController::class, 'index'])->name('files.filter');
Route::get('/api/subjects/{classId}', [FilterController::class, 'getSubjectsByClass']);
Route::get('/api/semesters/{subjectId}', [FilterController::class, 'getSemestersBySubject']);
Route::get('/api/files/{semesterId}', [FilterController::class, 'getFileTypesBySemester']);

// Calendar Events Route
Route::get('/app-calendar-events', [CalendarController::class, 'getEvents']);

// File Download Routes
Route::get('/download/{file}', [FileController::class, 'showDownloadPage'])->name('download.page');
Route::get('/download-wait/{file}', [FileController::class, 'processDownload'])->name('download.wait');

// Secure File Routes
Route::get('/secure-file', [SecureFileController::class, 'view'])->name('secure.file.view');
Route::middleware(['auth', 'SecureFileUpload:image'])->post('/secure-upload/image', [SecureFileController::class, 'uploadImage'])->name('secure.upload.image');
Route::middleware(['auth', 'SecureFileUpload:document'])->post('/secure-upload/document', [SecureFileController::class, 'uploadDocument'])->name('secure.upload.document');

// مسار خاص لتحميل اللوجو والصور العامة (بدون مصادقة)
Route::post('/upload/logo', [ImageUploadController::class, 'upload'])->name('upload.logo');
Route::post('/upload/file', [ImageUploadController::class, 'uploadFile'])->name('upload.file');
