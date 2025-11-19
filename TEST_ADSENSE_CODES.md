# 🧪 اختبار أكواد AdSense

## ✅ أكواد صحيحة يجب أن تُقبل

### 1. **Display Ad (عادي)**
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

### 2. **Auto Ads**
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890"
     crossorigin="anonymous"></script>
```

### 3. **In-feed Ad**
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-format="fluid"
     data-ad-layout-key="-fb+5w+4e-db+86"
     data-ad-client="ca-pub-1234567890"
     data-ad-slot="9876543210"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```

### 4. **Multiplex Ad**
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-format="autorelaxed"
     data-ad-client="ca-pub-1234567890"
     data-ad-slot="9876543210"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```

### 5. **Article Ad (AMP)**
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:block; text-align:center;"
     data-ad-layout="in-article"
     data-ad-format="fluid"
     data-ad-client="ca-pub-1234567890"
     data-ad-slot="9876543210"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```

---

## ❌ أكواد خطيرة يجب أن تُرفض

### 1. **XSS Attack**
```html
<script>alert('XSS')</script>
```
**السبب:** يحتوي على `alert()` خارج سياق adsbygoogle

### 2. **Event Handler Injection**
```html
<ins class="adsbygoogle" onclick="alert('XSS')"></ins>
```
**السبب:** يحتوي على event handler (onclick)

### 3. **JavaScript Injection**
```html
<script>eval(userInput)</script>
```
**السبب:** يحتوي على `eval()`

### 4. **Malicious Iframe**
```html
<iframe src="http://malicious-site.com"></iframe>
```
**السبب:** iframe من نطاق غير Google

### 5. **innerHTML Manipulation**
```html
<script>document.body.innerHTML = '<img src=x onerror=alert(1)>';</script>
```
**السبب:** يحتوي على `innerHTML`

---

## 🧪 كيفية الاختبار

### في لوحة التحكم:

1. **اذهب إلى:**
   ```
   http://127.0.0.1:8000/dashboard/settings
   ```

2. **انسخ والصق أحد الأكواد الصحيحة في حقل الإعلانات**

3. **اضغط حفظ**

4. **النتيجة المتوقعة:**
   - ✅ يتم الحفظ بنجاح
   - ✅ لا توجد رسالة خطأ

### اختبار الأكواد الخطيرة:

1. **انسخ والصق أحد الأكواد الخطيرة**

2. **اضغط حفظ**

3. **النتيجة المتوقعة:**
   - ❌ رسالة خطأ تظهر
   - ❌ لا يتم الحفظ
   - ✅ النظام يحميك من الهجمات

---

## 📝 ملاحظات مهمة

### **Attributes المسموحة في `<ins>`:**
- `class="adsbygoogle"` (مطلوب)
- `style` (أي CSS)
- `data-ad-client` (اختياري - إذا موجود يجب أن يكون بصيغة `ca-pub-XXXXX`)
- `data-ad-slot`
- `data-ad-format`
- `data-full-width-responsive`
- `data-ad-layout`
- `data-ad-layout-key`
- أي `data-*` attribute آخر

### **Attributes الممنوعة:**
- `onclick`, `onerror`, `onload` (أي event handler)
- أي attribute غير متعلق بـ AdSense

### **Scripts المسموحة:**
- فقط من `pagead2.googlesyndication.com`
- يجب أن تحتوي على `adsbygoogle`
- الـ `crossorigin="anonymous"` مسموح

---

## ✅ اختبارات موصى بها

قم بتجربة كل من الأكواد التالية:

### Test 1: Display Ad مع crossorigin ✅
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-1234567890"
     data-ad-slot="9876543210"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```
**المتوقع:** ✅ يُقبل

### Test 2: Auto Ads فقط ✅
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890"
     crossorigin="anonymous"></script>
```
**المتوقع:** ✅ يُقبل

### Test 3: كود بدون data-ad-client ✅
```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-slot="9876543210"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```
**المتوقع:** ✅ يُقبل (data-ad-client اختياري)

### Test 4: XSS Attack ❌
```html
<script>alert('XSS')</script>
```
**المتوقع:** ❌ يُرفض بخطأ "suspicious script content"

### Test 5: Event Handler ❌
```html
<ins class="adsbygoogle" onclick="alert('XSS')"></ins>
```
**المتوقع:** ❌ يُرفض بخطأ "event handlers not allowed"

---

**تم إعداده في:** 2025-11-19 11:40
