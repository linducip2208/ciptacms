<div dir="rtl" lang="ar">

# ليندو CMS — منصة CMS تجارية قابلة للعلامة البيضاء (العربية)

> متوفر أيضًا بـ [English](README.en.md) · [Indonesia](README.id.md) · [Indonesia ringkas](README.md)

**ليندو CMS** نظام إدارة محتوى تجاري قابل لإعادة العلامة مبني على **Laravel 13 +
PHP 8.3 + MySQL/SQLite + Tabler**. النواة **لا تحتوي أبدًا** على منطق تطبيقي
خاص — كل ميزات الأعمال موجودة كامتدادات بمجلد + ملف تعريف في
`modules/` و `plugins/` و `themes/`.

هذه التوثيق مكتوبة من الشيفرة المصدرية، وتحتوي على أقسام **"Known gaps"**
توضح ما **غير موجود**. اقرأها قبل أن تَعِد عميلًا بميزة.

## الموجود فعليًا

| المجال | المحتوى | التوثيق |
|---|---|---|
| النواة | 28 خدمة أحادية، 3 مزودات، وسائط، سجل تدقيق، فحوص صحة، نسخ احتياطي، استيراد/تصدير | [ARCHITECTURE.md](ARCHITECTURE.md) |
| المصادقة | دخول/تسجيل/خروج/استعادة، تذكرني، مصادقة ثنائية TOTP مع رموز احتياطية، إدارة الجلسات، OAuth، رموز Sanctum | [SECURITY.md](SECURITY.md) |
| الصلاحيات | مستخدمون، أدوار، أذونات، مجموعات، **127** إذنًا مسبقًا، 4 أدوار، وسيط `permission:` | [ARCHITECTURE.md](ARCHITECTURE.md) |
| القوائم | `menu_items`، تداخل غير محدود، تصفية بالصلاحية والدور على الخادم | [MENU_ENGINE.md](MENU_ENGINE.md) |
| محرّك الامتدادات | وحدة/إضافة/قالب: اكتشاف ← سجل ← تفعيل/تعطيل/إزالة | [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) · [THEMES.md](THEMES.md) |
| المحتوى | صفحات (مسودة/منشور/مجدول/مراجعات)، مدونة، وسائط بطابور (مصغّرات + WebP/AVIF)، سيو | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| منشئ الصفحات | سحب وإفلات HTML5 حقيقي، **21 مكوّنًا**، تراجع 60 خطوة، نسخ/لصق/تكرار، حفظ ككتلة، قوالب | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| منشئ النماذج | **18 نوع حقل**، تحقق لكل نوع، 5 طبقات ضد السبام، `POST /api/v1/forms/{slug}` | [FORM_BUILDER.md](FORM_BUILDER.md) |
| منشئ البيانات | أنواع محتوى وقت التشغيل، سجل JSON + جدول `cb_*`، استيراد CSV/JSON مع تقرير خطأ لكل صف | [DATA_BUILDER.md](DATA_BUILDER.md) |
| سير العمل | 17 محفزًا، 13 معامل شرط، 9 إجراءات، قائمة نماذج مسموحة، سجل تشغيل | [WORKFLOW.md](WORKFLOW.md) |
| ملف الشركة | 12 جدول `cp_*`، 8 موارد CRUD، 18 مسارًا عامًا | [COMPANY_PROFILE.md](COMPANY_PROFILE.md) |
| API | REST `v1` و `v2` (Bearer، ترقيم، فلترة، ترتيب، بحث)، 28 موردًا عامًا، OpenAPI | [API.md](API.md) |
| Webhooks | صادر (HMAC + طابور + إعادة محاولة) وداخل | [API.md](API.md) |
| متعدد المستأجرين | مستأجرون، نطاقات، خطط، أعلام ميزات، حصص، اشتراكات للقراءة فقط | [SAAS.md](SAAS.md) |
| العلامة البيضاء | 6 أقسام للعلامة، نطاقات مخصصة، مخصص القالب ← خصائص CSS | [WHITE_LABEL.md](WHITE_LABEL.md) |
| الترخيص | مدير ترخيص مدمج **و** بوابة الإقران License v3 (توقيع RSA + AES-256-GCM) | [LICENSE.md](LICENSE.md) |
| التحديثات | نسخ احتياطي ← ترحيل ← مسح الكاش ← تسجيل الإصدار. **لا ينفّذ شيفرة عن بُعد أبدًا** | [UPDATES.md](UPDATES.md) |
| التثبيت | `/install` (ثلاث خطوات، يُقفل بعد الاستخدام) + `php artisan lindu:install` | [INSTALL.md](INSTALL.md) |
| التشغيل | `lindu:doctor`، `lindu:backup`، `lindu:search-index`، `webhooks:retry` | [DEVELOPMENT.md](DEVELOPMENT.md) |

## غير الموجود — اقرأ قبل البيع

- **وحدات الأعمال مجرد هياكل.** `pos` و `hotel` و `lms` و `crm` و `erp` و
  `ecommerce` و `marketplace` و `jodohku` و `api` تحتوي على `module.json` فقط
  مع مسار بديل واحد. الموجود هو **مخطط قاعدة بيانات + CRUD عام** — لا توجد
  عملية بيع، ولا فحص توفر غرف، ولا تشغيل اختبارات، ولا إتمام شراء في السوق.
  راجع [MODULES.md](MODULES.md).
- **الإضافات مجرد هياكل.** لا يوجد محمّل شيفرة للإضافات، و
  `PluginManager::filters()` يُرجع القيمة دون تغيير.
- **قوالب القوالب غير مستخدمة.** `themes/*/layout.blade.php` لا يُعرض أبدًا.
- **الدفع واجهة فقط، لا فوترة.** خمسة محوّلات (Xendit, iPaymu, Tripay, Stripe,
  Generic) بلا صفحة دفع ولا مطابقة فواتير ولا اشتراك.
- **إظهار/إخفاء حسب المقاس لا يؤثر** على الصفحة النهائية.
- **تعدد المستأجرين غير معزول.** فقط شجرة القوائم وجداول `cb_*` ترشّح بـ
  `tenant_id`.
- **محدِّدات المعدل `api` و `webhook-in` و `login` مسجَّلة وغير مستخدمة.** فقط
  `throttle:form-submit` مُطبَّق.
- **فحص التحديث بدون نقطة نهاية يُرجع "محلي".** هذا يعني *أن شيئًا لم يُفحص*.
- **الاختبارات ليست خضراء.**

## التثبيت

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`.env.example` يستخدم SQLite، فلا حاجة لخادم قاعدة بيانات.
الدخول: `admin@lindu.local / password123` — **غيّره قبل أن يصل الموقع لأحد.**

```sh
composer check          # blade-lint + route-lint + مجموعة الاختبارات
php artisan lindu:doctor
```

## التوثيق

ابدأ بـ [ARCHITECTURE.md](ARCHITECTURE.md)، ثم:
[INSTALL](INSTALL.md) · [DEVELOPMENT](DEVELOPMENT.md) · [DEPLOYMENT](DEPLOYMENT.md) ·
[PAGE_BUILDER](PAGE_BUILDER.md) · [FORM_BUILDER](FORM_BUILDER.md) ·
[DATA_BUILDER](DATA_BUILDER.md) · [MENU_ENGINE](MENU_ENGINE.md) ·
[WORKFLOW](WORKFLOW.md) · [COMPANY_PROFILE](COMPANY_PROFILE.md) ·
[WHITE_LABEL](WHITE_LABEL.md) · [UPDATES](UPDATES.md) · [LICENSE](LICENSE.md) ·
[API](API.md) · [DATABASE](DATABASE.md) · [MODULES](MODULES.md) ·
[PLUGINS](PLUGINS.md) · [THEMES](THEMES.md) · [SAAS](SAAS.md) ·
[SECURITY](SECURITY.md) · [TROUBLESHOOTING](TROUBLESHOOTING.md)

المصدر: https://github.com/linducip2208/ciptacms

</div>
