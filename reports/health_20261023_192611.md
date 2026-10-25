# تقرير فحص RS_Own — 2026-10-23 19:26:11

- **النتيجة العامة:** FAIL (فشل)
- **الجذر:** `D:\RS_Own`
- **PHP:** 8.2.34
- **القاعدة:** `rs_own`
- **الفحوصات:** OK=29 | INFO=4 | WARN=15 | FAIL=1 | CRIT=0

## البيئة

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | إصدار PHP | PHP 8.2.34 |
| WARN | الإضافات المطلوبة | اختيارية ناقصة: zip, fileinfo |
| OK | المسارات الأساسية | كل المسارات موجودة |
|  | ↳ OK app → D:/RS_Own/app |
|  | ↳ OK config.php → D:/RS_Own/app/config.php |
|  | ↳ OK lib → D:/RS_Own/app/lib |
|  | ↳ OK pages → D:/RS_Own/app/pages |
|  | ↳ OK reports → D:/RS_Own/reports |
|  | ↳ OK backups → D:/RS_Own/backups |
| WARN | إعدادات PHP | display_errors مفعّل (يفضل إيقافه على الإنتاج) |
|  | ↳ display_errors=1 |
|  | ↳ memory_limit=128M |
|  | ↳ timezone=Africa/Cairo |
| OK | سيرفر التطبيق :8082 | السيرفر شغال على 127.0.0.1:8082 |

## الإعدادات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | تحميل config.php | config.php محمّل |
|  | ↳ admin_user=admin |
| OK | إعدادات القاعدة | إعدادات القاعدة مكتملة |
|  | ↳ name=rs_own |
|  | ↳ host=127.0.0.1 |
|  | ↳ charset=utf8mb4 |
| WARN | الأسرار و EasyOrders | secrets.php غير موجود — مزامنة EasyOrders قد لا تعمل |
|  | ↳ secrets.php: غير موجود |
|  | ↳ easyorders.key: مضبوط |
| OK | setup.php | setup.php محذوف |
| WARN | حماية .htaccess | حماية ناقصة: حجب config.php |
|  | ↳ MISSING حجب config.php |
|  | ↳ OK حجب lib |
|  | ↳ OK حجب sql |
| INFO | موقع secrets.php | secrets.php غير موجود |

## قاعدة البيانات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | الاتصال بقاعدة البيانات | متصل بـ rs_own |
|  | ↳ server=13.0.2-MariaDB |
| WARN | جداول القاعدة مقابل schema_manifest | جداول إضافية غير موجودة في المانيفست: _orphan_sub_backup, bridge_log, bridge_map, bridge_state, countries_bak_20261022, curr_barcodes_bak_20261022, curr_barcodes_old_20261022, customers_bak_20261022, days_bak_20261022, departments_bak_20261022 |
|  | ↳ live=80 |
|  | ↳ manifest=63 |
| OK | أعمدة الجداول مقابل schema_manifest | كل الأعمدة موجودة |
| WARN | الفهارس المهمة | فهارس ناقصة: inv.proc_id, inv.branch, inv_sub.proc_id |
| INFO | إحصاءات القاعدة | إحصاءات الجداول |
|  | ↳ inv=18685 |
|  | ↳ inv_sub=48646 |
|  | ↳ currencies=3245 |
|  | ↳ item_bal=3245 |
|  | ↳ users=3 |
|  | ↳ audit_log=48 |

## سلامة البيانات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | تكرار proc_id في الفواتير | لا يوجد تكرار |
| WARN | فواتير سارية بدون بنود | فواتير بدون بنود: 1 |
|  | ↳ all=1 |
|  | ↳ branch99=0 |
| OK | بنود يتيمة (بدون رأس) | لا توجد بنود يتيمة |
| WARN | الأرصدة السالبة | أرصدة سالبة: 118 (مجموع -227.322) — موروثة من بيانات المهندس غالباً |
| OK | تغطية item_bal للأصناف | تغطية item_bal كاملة |
|  | ↳ missing=0 |
|  | ↳ orphan=0 |
| OK | ترقيم فرع 99 | ترقيم فرع 99 متسق |
|  | ↳ max_proc=10000003 |
|  | ↳ max_id=10000003 |
|  | ↳ active=1 |
|  | ↳ seq_proc=10000003 |
|  | ↳ seq_inv=10000003 |
| OK | سجل التدقيق | سجل التدقيق شغال |
|  | ↳ rows=48 |
|  | ↳ last=2026-10-21 22:25:39 |
| INFO | أعلام الفواتير للمستخدمين | مستخدمون بلا صلاحية تعديل فاتورة (Fail-Closed): 1 |
| OK | طرق الدفع | طرق دفع مفعّلة: 4 |
| OK | قيم أنواع الفواتير | أنواع الفواتير سليمة |

## الأمان والصلاحيات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | مستخدمون بكلمة مرور فارغة | لا توجد كلمات مرور فارغة |
| OK | كلمات مرور غير مشفرة | كل كلمات المرور مشفرة |
| OK | مستخدمون بلا صلاحيات | كل المستخدمين لديهم صلاحيات |
| OK | حساب admin | حساب admin موجود |
| FAIL | خريطة الصلاحيات | صفحات في الخريطة غير موجودة: refresh_bal.php |
| WARN | صفحات بلا صلاحية محددة | صفحات بدون m_*: about.php, api_gard.php, data_basic.php, main.php |
| OK | إعدادات الجلسة | إعدادات الجلسة آمنة |
| WARN | حماية CSRF | حماية CSRF ناقصة |
|  | ↳ OK csrf_require() |
|  | ↳ MISSING X-CSRF-Token |
|  | ↳ OK csrf_field() |
| WARN | دوال خطرة في app | دوال تحتاج مراجعة (48) |
|  | ↳ exec @ D:/RS_Own/app/lib/archive_year.php:27 |
|  | ↳ exec @ D:/RS_Own/app/lib/archive_year.php:30 |
|  | ↳ exec @ D:/RS_Own/app/lib/archive_year.php:36 |
|  | ↳ exec @ D:/RS_Own/app/lib/archive_year.php:63 |
|  | ↳ exec @ D:/RS_Own/app/lib/audit.php:11 |
|  | ↳ exec @ D:/RS_Own/app/lib/bridge.php:10 |
|  | ↳ exec @ D:/RS_Own/app/lib/bridge.php:23 |
|  | ↳ exec @ D:/RS_Own/app/lib/bridge.php:27 |
|  | ↳ exec @ D:/RS_Own/app/lib/bridge.php:65 |
|  | ↳ proc_open @ D:/RS_Own/app/lib/bridge.php:528 |
|  | ↳ exec @ D:/RS_Own/app/lib/cfg.php:20 |
|  | ↳ exec @ D:/RS_Own/app/lib/cfg.php:110 |
|  | ↳ exec @ D:/RS_Own/app/lib/cfg.php:151 |
|  | ↳ exec @ D:/RS_Own/app/lib/cfg.php:152 |
|  | ↳ exec @ D:/RS_Own/app/lib/eo_item.php:14 |
|  | ↳ exec @ D:/RS_Own/app/lib/guard.php:132 |
|  | ↳ exec @ D:/RS_Own/app/lib/items.php:129 |
|  | ↳ exec @ D:/RS_Own/app/lib/loyalty.php:59 |
|  | ↳ exec @ D:/RS_Own/app/lib/push.php:127 |
|  | ↳ exec @ D:/RS_Own/app/lib/push.php:169 |
|  | ↳ exec @ D:/RS_Own/app/lib/push.php:170 |
|  | ↳ exec @ D:/RS_Own/app/lib/seq.php:11 |
|  | ↳ exec @ D:/RS_Own/app/pages/api_control.php:81 |
|  | ↳ exec @ D:/RS_Own/app/pages/api_control.php:90 |
|  | ↳ exec @ D:/RS_Own/app/pages/api_control.php:91 |
|  | ↳ exec @ D:/RS_Own/app/pages/api_control.php:107 |
|  | ↳ exec @ D:/RS_Own/app/pages/api_gard.php:11 |
|  | ↳ exec @ D:/RS_Own/app/pages/board.php:214 |
|  | ↳ exec @ D:/RS_Own/app/pages/board.php:215 |
|  | ↳ exec @ D:/RS_Own/app/pages/branches.php:17 |

## الكود والملفات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | فحص الصياغة (lint) | كل الملفات سليمة (149 ملف) |
| WARN | حماية إعادة التحميل في lib | ملفات تُحمّل بـ require بلا function_exists: wa.php |
| OK | روابط القوائم والشاشات | كل روابط القوائم سليمة |
| OK | ملاحظات TODO/FIXME | لا توجد ملاحظات TODO/FIXME |
| WARN | مسارات تطوير مثبتة | مسارات مطلقة في app: 52 |
|  | ↳ D:/RS_Own/app/config.php:5 |
|  | ↳ D:/RS_Own/app/config.php:7 |
|  | ↳ D:/RS_Own/app/config.php:9 |
|  | ↳ D:/RS_Own/app/lib/bridge.php:39 |
|  | ↳ D:/RS_Own/app/lib/bridge.php:44 |
|  | ↳ D:/RS_Own/app/lib/bridge.php:501 |
|  | ↳ D:/RS_Own/app/lib/easyorders.php:3 |
|  | ↳ D:/RS_Own/app/lib/eo_item.php:5 |
|  | ↳ D:/RS_Own/app/lib/eo_item.php:59 |
|  | ↳ D:/RS_Own/app/lib/eo_item.php:62 |
|  | ↳ D:/RS_Own/app/lib/layout.php:17 |
|  | ↳ D:/RS_Own/app/lib/layout.php:21 |
|  | ↳ D:/RS_Own/app/lib/paths.php:7 |
|  | ↳ D:/RS_Own/app/lib/push.php:17 |
|  | ↳ D:/RS_Own/app/lib/push_secret.php:2 |
|  | ↳ D:/RS_Own/app/lib/wa.php:11 |
|  | ↳ D:/RS_Own/app/lib/wa.php:14 |
|  | ↳ D:/RS_Own/app/pages/api_buy_save.php:10 |
|  | ↳ D:/RS_Own/app/pages/api_control.php:28 |
|  | ↳ D:/RS_Own/app/pages/api_easy_sync.php:184 |
| INFO | أكبر الملفات | أكبر 5 ملفات |
|  | ↳ D:/RS_Own/app/lib/schema_manifest.php = 175.3 KB |
|  | ↳ D:/RS_Own/app/pages/sale_rs.php = 50.6 KB |
|  | ↳ D:/RS_Own/app/pages/buy_rs.php = 35.2 KB |
|  | ↳ D:/RS_Own/app/pages/control.php = 31.9 KB |
|  | ↳ D:/RS_Own/app/pages/board.php = 27.3 KB |

## التشغيل والنسخ

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | النسخ الاحتياطي | نسخة حديثة |
|  | ↳ rs_own_20261023_163410.sql (2.9 ساعة) |
| OK | آخر مزامنة | المزامنة حديثة |
|  | ↳ 2026-10-22 22:53 (20.5 ساعة) |
| WARN | مراقب الصحة | المراقب يقول bad |
|  | ↳ overall=bad |
|  | ↳ ts=2026-10-23T19:17:08+03:00 |
| WARN | مطابقة المهندس | آخر مطابقة عمرها 2.9 يوم |
|  | ↳ ok=1 |
|  | ↳ ts=2026-10-20T21:40:45+03:00 |
| OK | مساحة القرص | المساحة كافية |
|  | ↳ 28.1% فاضي (48.1 GB) |
| OK | أخطاء حديثة في اللوج | لا أخطاء حديثة في sync.log |

## الاختبارات

| الحالة | الفحص | النتيجة |
|---|---|---|
| OK | ملفات الاختبارات | اختبارات موجودة |
|  | ↳ focused=6 |
|  | ↳ phpunit=4 |
| OK | أدوات الاختبار | أدوات الاختبار موجودة |
|  | ↳ OK phpunit.xml |
|  | ↳ OK phpunit.phar |
|  | ↳ OK run_all.ps1 |

