# تعليمات إضافة البنر الاحترافي

## الملفات التي تم إنشاؤها:

1. **ملف CSS**: `public/assets/css/banner-professional.css`
2. **ملف JavaScript**: `public/assets/js/banner-professional.js`
3. **مكون Blade**: `resources/views/components/banner-professional.blade.php`

## خطوات التنفيذ:

### الخطوة 1: إضافة ملف CSS إلى الصفحة الرئيسية

في ملف `resources/views/content/frontend/home.blade.php`، أضف السطر التالي في قسم `@section('page-style')` بعد `@vite([...])`:

```blade
<!-- Professional Banner CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/banner-professional.css') }}">
```

### الخطوة 2: إضافة ملف JavaScript

في نفس الملف، أضف السطر التالي في قسم `@section('page-script')` بعد `@vite([...])`:

```blade
<!-- Professional Banner JavaScript -->
<script src="{{ asset('assets/js/banner-professional.js') }}"></script>
```

### الخطوة 3: إضافة البنر في الصفحة

أضف البنر بعد قسم البريدكرمب (breadcrumb) وقبل قسم الصفوف (classes). في السطر 139 تقريباً، أضف:

```blade
<!-- Professional Banner -->
<div class="container mt-4">
    @include('components.banner-professional')
</div>
```

## الكود الكامل للإضافة:

### في قسم page-style (بعد السطر 35):

```blade
@section('page-style')
@vite([
    'resources/assets/vendor/scss/but.scss',
    'resources/assets/vendor/scss/calendar.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
<!-- Professional Banner CSS -->
<link rel="stylesheet" href="{{ asset('assets/css/banner-professional.css') }}">
<style>
  /* ... باقي الأكواد ... */
</style>
@endsection
```

### في قسم page-script (بعد السطر 72):

```blade
@section('page-script')
@vite([
    'resources/assets/vendor/js/filterhome.js',
    'resources/assets/vendor/js/but.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/js/appCalender.js',
])
<!-- Professional Banner JavaScript -->
<script src="{{ asset('assets/js/banner-professional.js') }}"></script>
@endsection
```

### إضافة البنر في قسم المحتوى (بعد السطر 138):

```blade
</div>

<!-- Professional Banner -->
<div class="container mt-4 mb-4">
    @include('components.banner-professional')
</div>

<section class="section-py pt-3 " id="testimonials">
```

## ملاحظات مهمة:

1. **الأيقونات**: البنر يستخدم Material Design Icons (mdi). تأكد من أن مكتبة الأيقونات مُحمّلة في الموقع.

2. **الصورة**: البنر يستخدم صورة من المسار `public/assets/khadmatak.png`. تأكد من وجود الصورة في هذا المسار، أو قم بتعديل المسار في ملف `banner-professional.blade.php`.

3. **الرابط**: البنر يستخدم `config('app.url')` للرابط. يمكنك تغييره إلى الرابط المطلوب.

4. **التوافق**: البنر متجاوب بالكامل ويعمل على جميع الأجهزة (Desktop, Tablet, Mobile).

5. **الأداء**: تم تحسين البنر لأداء عالي مع CSS animations خفيفة.

## إذا لم يعمل البنر:

1. تأكد من تشغيل `php artisan cache:clear`
2. تأكد من أن المسارات صحيحة
3. تأكد من وجود صورة اللوجو
4. تأكد من تحميل Material Design Icons

## تخصيص البنر:

يمكنك تخصيص:
- الألوان في ملف CSS عبر CSS Variables في `:root`
- النصوص في ملف `banner-professional.blade.php`
- التفاعلات في ملف JavaScript

## دعم:

البنر يدعم:
- ✅ RTL (من اليمين لليسار)
- ✅ Responsive Design
- ✅ Accessibility (ARIA labels)
- ✅ Keyboard Navigation
- ✅ SEO Friendly
- ✅ Performance Optimized

---

**تم إنشاؤه بواسطة**: Claude Code
**التاريخ**: 2025-10-22
