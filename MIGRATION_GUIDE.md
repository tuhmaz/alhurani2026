# دليل الترحيل - توحيد نظام حظر IP

## التغييرات المطبقة

### 1. توحيد نظام حظر IP

تم دمج نظامي `BlockedIp` و `BannedIp` في نظام واحد موحد (`BannedIp`).

#### الملفات المتأثرة:

##### تم التحديث:
- ✅ `app/Models/BannedIp.php` - تم إضافة Accessors/Mutators للتوافق
- ✅ `app/Models/BlockedIp.php` - تم تحويله إلى Alias لـ BannedIp
- ✅ `app/Http/Controllers/BlockedIpsController.php` - تم تحديثه لاستخدام BannedIp

##### تم الإنشاء:
- ✅ `config/secure-connections.php` - ملف التكوين المفقود
- ✅ `database/migrations/2025_01_23_000000_migrate_blocked_ips_to_banned_ips.php` - Migration لنقل البيانات

---

## خطوات تطبيق التغييرات على قاعدة البيانات

### الخطوة 1: نسخ احتياطي لقاعدة البيانات
```bash
# نسخ احتياطي لقاعدة البيانات قبل التطبيق
mysqldump -u [user] -p [database_name] > backup_before_ip_migration.sql
```

### الخطوة 2: تشغيل Migration
```bash
php artisan migrate
```

هذا سيقوم بـ:
1. نقل جميع البيانات من `blocked_ips` إلى `banned_ips`
2. حذف جدول `blocked_ips` القديم

### الخطوة 3: التحقق من نجاح الترحيل
```bash
# التحقق من البيانات المنقولة
php artisan tinker

# في Tinker:
\App\Models\BannedIp::count(); // يجب أن يظهر العدد الكلي للـ IPs المحظورة
```

---

## التوافق العكسي (Backward Compatibility)

تم الحفاظ على التوافق العكسي بالطرق التالية:

### 1. Model Alias
```php
// الكود القديم سيستمر في العمل:
use App\Models\BlockedIp;

$blocked = BlockedIp::all(); // يعمل بشكل طبيعي
```

### 2. Accessors & Mutators
تم إضافة accessors في `BannedIp` للتعامل مع الأسماء القديمة:

```php
// كلاهما يعمل:
$bannedIp->ip;          // الطريقة الجديدة
$bannedIp->ip_address;  // الطريقة القديمة (للتوافق)

// كلاهما يعمل:
$bannedIp->created_at;  // الطريقة الجديدة
$bannedIp->blocked_at;  // الطريقة القديمة (للتوافق)
```

### 3. Relationships
```php
// كلاهما يعمل:
$bannedIp->admin();      // الطريقة الجديدة
$bannedIp->blockedBy();  // الطريقة القديمة (للتوافق)
```

---

## الفروقات بين النظامين

| الميزة | blocked_ips (قديم) | banned_ips (جديد) |
|--------|-------------------|------------------|
| اسم حقل IP | `ip_address` | `ip` |
| تاريخ الحظر | `blocked_at` | `created_at` |
| مدة الحظر | غير موجود | `banned_until` (nullable) |
| الحالة | دائماً نشط | يدعم الحظر المؤقت |
| Scopes | لا يوجد | `active()`, `expired()` |

---

## API الجديدة

### التحقق من حظر IP
```php
use App\Models\BannedIp;

// الطريقة القديمة (لا تزال تعمل):
if (BlockedIp::where('ip_address', $ip)->exists()) {
    // IP محظور
}

// الطريقة الجديدة (موصى بها):
if (BannedIp::isBanned($ip)) {
    // IP محظور
}
```

### حظر IP
```php
// الطريقة القديمة:
BlockedIp::create([
    'ip_address' => $ip,
    'reason' => $reason,
    'blocked_at' => now(),
    'blocked_by' => auth()->id()
]);

// الطريقة الجديدة (موصى بها):
BannedIp::ban($ip, $reason, $days = 30); // حظر لمدة 30 يوم
BannedIp::ban($ip, $reason, null);       // حظر دائم
```

### استعلامات متقدمة
```php
// الحصول على جميع الحظر النشط فقط
$activeBans = BannedIp::active()->get();

// الحصول على الحظر المنتهي
$expiredBans = BannedIp::expired()->get();

// الحصول على الحظر الدائم فقط
$permanentBans = BannedIp::whereNull('banned_until')->get();
```

---

## التحديثات الموصى بها في الكود

### في Controllers:
```php
// قديم (لا يزال يعمل):
use App\Models\BlockedIp;
$blocked = BlockedIp::all();

// جديد (موصى به):
use App\Models\BannedIp;
$banned = BannedIp::all();
```

### في Middleware:
تم بالفعل تحديث:
- ✅ `SecurityScanMiddleware.php`
- ✅ `BlockXssAttempts.php`

للاستخدام `BannedIp::isBanned()` بشكل صحيح.

---

## التراجع عن التغييرات

إذا احتجت للتراجع:

```bash
php artisan migrate:rollback --step=1
```

هذا سيقوم بـ:
1. إعادة إنشاء جدول `blocked_ips`
2. **ملاحظة:** لن يتم استرجاع البيانات تلقائياً، استخدم النسخة الاحتياطية

---

## المتغيرات البيئية الجديدة (.env)

أضف إلى ملف `.env`:

```env
# Force HTTPS in production
FORCE_HTTPS=true

# Secure cookies settings
SECURE_COOKIES=true
HTTP_ONLY_COOKIES=true
COOKIE_SAME_SITE=lax
```

---

## الاختبار

### اختبار نظام الحظر:
```php
// اختبار حظر IP
$ip = '192.168.1.100';
BannedIp::ban($ip, 'Testing ban system', 7); // حظر لمدة 7 أيام

// التحقق
if (BannedIp::isBanned($ip)) {
    echo "IP is banned ✓";
}

// اختبار الحظر المنتهي
$ban = BannedIp::where('ip', $ip)->first();
$ban->update(['banned_until' => now()->subDay()]); // جعله منتهي

if (!BannedIp::isBanned($ip)) {
    echo "Expired ban is not active ✓";
}
```

---

## الدعم

إذا واجهت أي مشاكل:

1. تحقق من الـ logs: `storage/logs/laravel.log`
2. تأكد من تشغيل Migration بنجاح
3. تحقق من البيانات في جدول `banned_ips`

---

## المراجع

- [BannedIp Model](app/Models/BannedIp.php)
- [BlockedIp Alias](app/Models/BlockedIp.php)
- [Migration File](database/migrations/2025_01_23_000000_migrate_blocked_ips_to_banned_ips.php)
- [Secure Connections Config](config/secure-connections.php)
