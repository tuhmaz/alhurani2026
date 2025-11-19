# ⚡ اختبار سريع - Google AdSense

## 🎯 افتح هذا الرابط:
```
http://127.0.0.1:8000/dashboard/settings
```

---

## 📋 انسخ والصق هذا الكود للاختبار:

```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-1234567890"
     data-ad-slot="9876543210"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```

---

## ✅ النتيجة المتوقعة:
- ✅ يتم الحفظ بنجاح
- ✅ لا توجد رسالة "AdSense snippet is empty"
- ✅ لا توجد أخطاء في Console

---

## ❌ إذا ظهر خطأ:

### افحص السجل:
```bash
cd "D:\2026\alhurani2026"
tail -f storage/logs/laravel.log
```

### امسح الكاش:
```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

---

## 📚 للمزيد من التفاصيل:
- [TESTING_GUIDE_AR.md](TESTING_GUIDE_AR.md) - دليل الاختبار الكامل
- [FIX_STATUS_AR.md](FIX_STATUS_AR.md) - حالة الإصلاحات
- [TEST_ADSENSE_CODES.md](TEST_ADSENSE_CODES.md) - أمثلة إضافية

---

**الحالة:** ✅ جاهز للاختبار
**التاريخ:** 2025-11-19
