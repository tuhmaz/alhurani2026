<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PermissionsTableSeeder extends Seeder
{
  public function run()
  {
    $timestamp = now();

    $permissions = [
      // ============================================
      // 📊 صلاحيات لوحة التحكم الرئيسية
      // ============================================
      ['name' => 'access dashboard', 'guard_name' => 'sanctum'],
      ['name' => 'dashboard.view', 'guard_name' => 'sanctum'],

      // ============================================
      // 👥 صلاحيات إدارة المستخدمين
      // ============================================
      ['name' => 'manage users', 'guard_name' => 'sanctum'],
      ['name' => 'users.view', 'guard_name' => 'sanctum'],
      ['name' => 'users.create', 'guard_name' => 'sanctum'],
      ['name' => 'users.edit', 'guard_name' => 'sanctum'],
      ['name' => 'users.delete', 'guard_name' => 'sanctum'],

      // 🎭 صلاحيات الأدوار والصلاحيات
      ['name' => 'manage roles', 'guard_name' => 'sanctum'],
      ['name' => 'roles.view', 'guard_name' => 'sanctum'],
      ['name' => 'roles.create', 'guard_name' => 'sanctum'],
      ['name' => 'roles.edit', 'guard_name' => 'sanctum'],
      ['name' => 'roles.delete', 'guard_name' => 'sanctum'],

      ['name' => 'manage permissions', 'guard_name' => 'sanctum'],
      ['name' => 'permissions.view', 'guard_name' => 'sanctum'],
      ['name' => 'permissions.assign', 'guard_name' => 'sanctum'],

      // ============================================
      // 📝 صلاحيات إدارة المحتوى
      // ============================================

      // المقالات
      ['name' => 'manage articles', 'guard_name' => 'sanctum'],
      ['name' => 'articles.view', 'guard_name' => 'sanctum'],
      ['name' => 'articles.create', 'guard_name' => 'sanctum'],
      ['name' => 'articles.edit', 'guard_name' => 'sanctum'],
      ['name' => 'articles.delete', 'guard_name' => 'sanctum'],
      ['name' => 'articles.publish', 'guard_name' => 'sanctum'],

      // المنشورات
      ['name' => 'manage posts', 'guard_name' => 'sanctum'],
      ['name' => 'posts.view', 'guard_name' => 'sanctum'],
      ['name' => 'posts.create', 'guard_name' => 'sanctum'],
      ['name' => 'posts.edit', 'guard_name' => 'sanctum'],
      ['name' => 'posts.delete', 'guard_name' => 'sanctum'],

      // الفئات
      ['name' => 'manage categories', 'guard_name' => 'sanctum'],
      ['name' => 'categories.view', 'guard_name' => 'sanctum'],
      ['name' => 'categories.create', 'guard_name' => 'sanctum'],
      ['name' => 'categories.edit', 'guard_name' => 'sanctum'],
      ['name' => 'categories.delete', 'guard_name' => 'sanctum'],

      // ============================================
      // 📁 صلاحيات إدارة الملفات
      // ============================================
      ['name' => 'manage files', 'guard_name' => 'sanctum'],
      ['name' => 'files.view', 'guard_name' => 'sanctum'],
      ['name' => 'files.upload', 'guard_name' => 'sanctum'],
      ['name' => 'files.download', 'guard_name' => 'sanctum'],
      ['name' => 'files.delete', 'guard_name' => 'sanctum'],

      // ============================================
      // 💬 صلاحيات التعليقات والتفاعلات
      // ============================================
      ['name' => 'manage comments', 'guard_name' => 'sanctum'],
      ['name' => 'comments.view', 'guard_name' => 'sanctum'],
      ['name' => 'comments.create', 'guard_name' => 'sanctum'],
      ['name' => 'comments.moderate', 'guard_name' => 'sanctum'],
      ['name' => 'comments.delete', 'guard_name' => 'sanctum'],

      // ============================================
      // 🎓 صلاحيات النظام التعليمي
      // ============================================

      // الصفوف الدراسية
      ['name' => 'manage school classes', 'guard_name' => 'sanctum'],
      ['name' => 'classes.view', 'guard_name' => 'sanctum'],
      ['name' => 'classes.create', 'guard_name' => 'sanctum'],
      ['name' => 'classes.edit', 'guard_name' => 'sanctum'],
      ['name' => 'classes.delete', 'guard_name' => 'sanctum'],

      // المواد الدراسية
      ['name' => 'manage subjects', 'guard_name' => 'sanctum'],
      ['name' => 'subjects.view', 'guard_name' => 'sanctum'],
      ['name' => 'subjects.create', 'guard_name' => 'sanctum'],
      ['name' => 'subjects.edit', 'guard_name' => 'sanctum'],
      ['name' => 'subjects.delete', 'guard_name' => 'sanctum'],

      // الفصول الدراسية
      ['name' => 'manage semesters', 'guard_name' => 'sanctum'],
      ['name' => 'semesters.view', 'guard_name' => 'sanctum'],
      ['name' => 'semesters.create', 'guard_name' => 'sanctum'],
      ['name' => 'semesters.edit', 'guard_name' => 'sanctum'],
      ['name' => 'semesters.delete', 'guard_name' => 'sanctum'],

      // الحضور
      ['name' => 'manage attendance', 'guard_name' => 'sanctum'],
      ['name' => 'attendance.view', 'guard_name' => 'sanctum'],
      ['name' => 'attendance.mark', 'guard_name' => 'sanctum'],

      // ============================================
      // 📧 صلاحيات الرسائل والإشعارات
      // ============================================

      // الرسائل
      ['name' => 'manage messages', 'guard_name' => 'sanctum'],
      ['name' => 'messages.view', 'guard_name' => 'sanctum'],
      ['name' => 'messages.send', 'guard_name' => 'sanctum'],
      ['name' => 'messages.delete', 'guard_name' => 'sanctum'],

      // الإشعارات
      ['name' => 'manage notifications', 'guard_name' => 'sanctum'],
      ['name' => 'notifications.view', 'guard_name' => 'sanctum'],
      ['name' => 'notifications.send', 'guard_name' => 'sanctum'],

      // ============================================
      // 🛡️ صلاحيات الأمان والمراقبة
      // ============================================

      // المراقبة
      ['name' => 'manage monitoring', 'guard_name' => 'sanctum'],
      ['name' => 'monitor.view', 'guard_name' => 'sanctum'],

      // الأمان
      ['name' => 'manage security', 'guard_name' => 'sanctum'],
      ['name' => 'security.view', 'guard_name' => 'sanctum'],
      ['name' => 'security.logs', 'guard_name' => 'sanctum'],
      ['name' => 'security.blocked-ips', 'guard_name' => 'sanctum'],

      // الأداء
      ['name' => 'manage performance', 'guard_name' => 'sanctum'],
      ['name' => 'performance.view', 'guard_name' => 'sanctum'],

      // Redis
      ['name' => 'manage redis', 'guard_name' => 'sanctum'],
      ['name' => 'redis.view', 'guard_name' => 'sanctum'],
      ['name' => 'redis.clear', 'guard_name' => 'sanctum'],

      // ============================================
      // 📊 صلاحيات التحليلات
      // ============================================
      ['name' => 'manage analytics', 'guard_name' => 'sanctum'],
      ['name' => 'analytics.view', 'guard_name' => 'sanctum'],
      ['name' => 'analytics.export', 'guard_name' => 'sanctum'],

      // ============================================
      // 📅 صلاحيات التقويم
      // ============================================
      ['name' => 'manage calendar', 'guard_name' => 'sanctum'],
      ['name' => 'calendar.view', 'guard_name' => 'sanctum'],
      ['name' => 'calendar.create', 'guard_name' => 'sanctum'],
      ['name' => 'calendar.edit', 'guard_name' => 'sanctum'],
      ['name' => 'calendar.delete', 'guard_name' => 'sanctum'],

      // ============================================
      // 🗺️ صلاحيات خريطة الموقع
      // ============================================
      ['name' => 'manage sitemap', 'guard_name' => 'sanctum'],
      ['name' => 'sitemap.view', 'guard_name' => 'sanctum'],
      ['name' => 'sitemap.generate', 'guard_name' => 'sanctum'],

      // ============================================
      // ⚙️ صلاحيات الإعدادات
      // ============================================
      ['name' => 'manage settings', 'guard_name' => 'sanctum'],
      ['name' => 'settings.view', 'guard_name' => 'sanctum'],
      ['name' => 'settings.edit', 'guard_name' => 'sanctum'],

      // ============================================
      // 👤 صلاحيات الملف الشخصي
      // ============================================
      ['name' => 'profile.view', 'guard_name' => 'sanctum'],
      ['name' => 'profile.edit', 'guard_name' => 'sanctum'],

      // ============================================
      // 🔧 صلاحيات النظام المتقدمة
      // ============================================
      ['name' => 'manage cache', 'guard_name' => 'sanctum'],
      ['name' => 'manage reports', 'guard_name' => 'sanctum'],

      // ============================================
      // 🔙 صلاحيات للتوافق مع النظام القديم
      // ============================================
      ['name' => 'admin users', 'guard_name' => 'sanctum'],
      ['name' => 'view analytics', 'guard_name' => 'sanctum'],
      ['name' => 'manage news', 'guard_name' => 'sanctum'],
      ['name' => 'view messages', 'guard_name' => 'sanctum'],
      ['name' => 'send messages', 'guard_name' => 'sanctum'],
      ['name' => 'view activity', 'guard_name' => 'sanctum'],
      ['name' => 'monitor redis', 'guard_name' => 'sanctum'],
      ['name' => 'view redis stats', 'guard_name' => 'sanctum'],
      ['name' => 'view security', 'guard_name' => 'sanctum'],
      ['name' => 'view security logs', 'guard_name' => 'sanctum'],
      ['name' => 'view security analytics', 'guard_name' => 'sanctum'],
      ['name' => 'manage blocked ips', 'guard_name' => 'sanctum'],
      ['name' => 'manage chating', 'guard_name' => 'sanctum'],
      ['name' => 'legacy', 'guard_name' => 'sanctum'],
    ];

    foreach ($permissions as $permission) {
      // التحقق من وجود الصلاحية قبل إنشائها
      if (!DB::table('permissions')->where('name', $permission['name'])->where('guard_name', $permission['guard_name'])->exists()) {
        $permission['created_at'] = $timestamp;
        $permission['updated_at'] = $timestamp;
        DB::table('permissions')->insert($permission);
      }
    }
  }
}
