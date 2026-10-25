# حالة مشروع RS_Own — 2026-10-24

## ملخص سريع
- التطبيق: PHP 8.2.34 + MySQL (`rs_own`)، الويب روت `D:\RS_Own\app` (مش جذر الريبو).
- **شغل اليوم: تنظيف وأمن فقط — صفر تعديلات على أي ملف PHP** (و`app/lib/bridge.php` لم يُمس إطلاقًا).
- **git صار موجودًا**: المستودع اتعمل النهاردة بعد التنظيف (baseline commit `f142401`).
- السيرفر الحي على :8082 هو PHP مباشر (`php -S 0.0.0.0:8082 -t app`) بيطلعه `desktop/keep_server.ps1` — **nginx متظبط لكن غير شغال** (التفاصيل تحت).

## ما تم تنفيذه اليوم (التنظيف الأمني)

### 1) نسخة احتياطية كاملة قبل أي شغل
- `robocopy D:\RS_Own → D:\RS_Own_backup_20261025` — نجحت، **1.3 GB** (exit code 1).

### 2) حذف ملفين خطرين (كانا مكشوفين على الويب)
- `app/install_inst.php` — المثبت القديم بمفتاح ثابت (الثغرة الحرجة).
- `app/app.rar` — أرشيف كامل للتطبيق داخل الويب روت.
- تم التحقق: الاتنان بيرجعوا **404** بعد الحذف.

### 3) إخراج الملفات الحساسة من المشروع → `C:\ProgramData\RS_Own\private\`
المجلد بصلاحيات ACL مضبوطة (المستخدم + Administrators + SYSTEM بس). **26 ملف**:
- من الجذر: `RS_Own.rar` (172م)، `app_upload.zip`، `audit.sql`، `load.sql`، `schema.sql`، `rs_own_infinity.sql` + `_clean` + `_utf8` + `_utf8_fixed` + `.zip`، `rs_unpack_test.exe`.
- `app/sql/rsown.sql` + `rsown.sql.gz` → `private\app\sql\` (كانا قابلين للتحميل من `/sql/` على السيرفر الحي).
- `backups\`: `RS_RS_20261021_182533.bak`، `pre_decimal_migration_20261022.sql`، `rs_own_uploads_*.zip` (7) → `private\backups\`.
- `deploy/infinity_20261021_1603/htdocs.zip` → `private\deploy\...`.

**بقي مكانه عن قصد:**
- `desktop/chrome_profile` — يستخدمه فعليًا `desktop/start_app.ps1` (`--user-data-dir`). مستثنى من git.
- `backups/rs_own_*.sql` (14 ملف) — `tests/run_all.ps1` (سطر 7) بيستخدم أحدثهم لاستعادة قاعدة الاختبار المؤقتة؛ نقلهم يكسر بوابة الاختبارات. هم خارج الويب روت أصلًا + محجوبون بقاعدة `*.sql` في nginx + مستثنون من git.

### 4) تقوية nginx (`C:\phplocal\nginx\conf\nginx.conf`)
اتضاف داخل الـ server block (نسخة احتياطية: `nginx.conf.bak-20261025`):
```nginx
location ~* ^/(app/lib|tools)(/|$) { return 404; }
location ~* \.(sql|rar|zip|bak|log|exe|ini|md)$ { return 404; }
```
- كانت موجودة بالفالي: حجب الـ dotfiles (مع استثناء `.well-known`) وحجب `/sessions|logs|lib|sql` و`/uploads/` بالكامل.
- اتحذف `deny all;` من القواعد لأن `return 404` بيتنفذ قبلها في nginx (كود ميت) — النتيجة 404 زي المطلوب.
- **`nginx -t`: ناجح** (syntax ok / test is successful).
- **الـ reload فشل لأن nginx غير شغال أصلًا** — مفيش process ولا حاجة بتشغله تلقائيًا؛ الإعدادات هتشتغل أول ما يتشغل.

### 5) إدارة إصدارات (git)
- `git init` في `D:\RS_Own` + أول commit: **`f142401` "baseline after security cleanup"** — 646 ملف، `.git` بحجم 11 MB، الشجرة نضيفة.
- `.gitignore`: `uploads/`, `*.log`, `*.rar`, `*.zip`, `*.sql`, `*.bak` + سطرين ضروريين للأمان: `desktop/chrome_profile/` (بيانات جلسات متصفح) و `data/sessions/` (جلسات PHP وقتية).
- اتأكد: صفر ملفات sql أو chrome_profile داخل الـ repo.

## الاختبارات والجودة
- **بوابة الاختبارات الكاملة آخر تشغيل لها 2026-10-23: ALL PASS** (lint + unit + smoke 99 صفحة + E2E بيع/شراء + CSRF + PHPUnit 16/97). النهاردة مفيش تعديل كود، فمااتشغلتش من جديد.
- **Smoke بعد التنظيف على السيرفر الحي :8082 — كله سليم:**
  - `/` و `/pages/main.php` و `/pages/print_inv.php` → 302 (الـ guard، طبيعي بلا جلسة).
  - `/pages/login.php` → 200.
  - `/sql/rsown.sql` و `.gz` و `/app.rar` و `/install_inst.php` → 404 (اتحذفوا/اتنقلوا).
  - `/lib/db.php` → 302 (الـ guard بيرد).

## مخاطر وقرارات مؤجلة
1. **nginx غير شغال**: القواعد الجديدة (والقديمة) غير فعّالة على الترافيك الحي. السيرفر الحي PHP مباشر، يعني الحماية الفعلية النهاردة جاية من حذف/نقل الملفات نفسها. لتفعيل حماية nginx لازم تحويل التوبولوجي لـ workers (8083-8086) + nginx على 8082 وتحديث `keep_server.ps1`/`start_app.ps1`/`start_rsown.bat` — **قرار تشغيلي مؤجل**.
2. `AGENTS.md` قديم في نقطتين: بيقول "مش git repo" (اتعمل النهاردة)، وبيشير للـ `.bak` في `backups\` (اتنقل لـ `private\backups\`).
3. `tools/*.log` و`logs/*.log` فضلت مكانها (مش ضمن قائمة النقل) — محجوبة في nginx ومستثناة من git.
4. باقي العناصر المؤجلة من STATE_2026-10-22 (المخزون السالب 118 صنف، ربط البائعين، تدوير مفتاح EasyOrders، إلخ) لسه مفتوحة.

## أوامر سريعة
```powershell
# تشغيل التطبيق
D:\RS_Own\start_rsown.bat

# lint
cmd /c D:\RS_Own\tests\lint_all.bat

# كل الاختبارات (lint + unit + smoke + E2E)
powershell -ExecutionPolicy Bypass -File D:\RS_Own\tests\run_all.ps1

# PHPUnit
C:\phplocal\php\php.exe D:\RS_Own\tools\phpunit.phar -c D:\RS_Own\phpunit.xml

# فحص إعدادات nginx (لازم من مجلد nginx)
C:\phplocal\nginx\nginx.exe -p "C:\phplocal\nginx\" -t
```

## مراجع
- `D:\RS_Own_backup_20261025` — نسخة كاملة ما قبل التنظيف الأمني.
- `C:\ProgramData\RS_Own\private\` — الملفات الحساسة المنقولة.
- `C:\phplocal\nginx\conf\nginx.conf` (+ `.bak-20261025`) — إعدادات nginx المحصّنة.
- `AGENTS.md` — قيود واصطلاحات العمل.
- `STATE_2026-10-23.md` / `STATE_2026-10-22.md` — الحالات السابقة (مؤرشفة).
- `STATE.md` (هذا الملف) — الحالة بتاريخ 2026-10-24.
