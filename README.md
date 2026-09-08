# منصة لقاحي — الباك إند (Laravel API)

نظام إلكتروني لإدارة وتتبع برامج التلقيح الوطنية للأطفال، يخدم ثلاثة أدوار: **مدير وطني (admin)**، **طبيب (doctor)**، **ولي أمر (parent)**.

## المتطلبات

- PHP >= 8.2 (تم الاختبار على 8.2.12 مع XAMPP)
- تمكين إضافة `gd` في php.ini (سطر `extension=gd`)
- MySQL / MariaDB (تم الاختبار على MariaDB 10.4)
- Composer

## خطوات التشغيل

```bash
# 1. تثبيت الحزم
composer install

# 2. إنشاء قاعدة البيانات (utf8mb4)
#    CREATE DATABASE laqahi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3. إعداد ملف البيئة (انسخ من .env.example وعدل بيانات البريد)
cp .env.example .env
php artisan key:generate

# 4. إنشاء الجداول والبيانات الأولية (19 لقاحاً وطنياً + حساب مدير)
php artisan migrate --seed

# 5. تشغيل السيرفر
php artisan serve
# API Base URL: http://localhost:8000/api
```

## حسابات الدخول الافتراضية (بعد التهيئة)

| الدور | البريد | كلمة المرور |
|---|---|---|
| مدير وطني | admin@laqahi.com | Admin@1234 |

الأطباء وأولياء الأمور يُنشؤون عبر الـ API (المدير ينشئ الطبيب، والطبيب ينشئ ولي الأمر).

## المصادقة (Laravel Sanctum — Personal Access Tokens)

1. أرسل `POST /api/auth/login` مع `role` و`email` و`password`.
2. استلم `token` من الاستجابة.
3. أرسله في كل طلب محمي عبر الترويسة: `Authorization: Bearer <token>`.

كل دور له Guard منفصل (`auth:admin` / `auth:doctor` / `auth:parent`)، ولا يمكن لتوكن دور الوصول لمسارات دور آخر.

## صيغة الاستجابة الموحدة

```json
{
  "success": true,
  "message": "رسالة عربية واضحة",
  "data": { }
}
```

الأخطاء: `success=false` مع `message`، وعند أخطاء التحقق تُضاف `errors` (حقل 422).

## Endpoints

### المصادقة (عام)

| Method | Route | المدخلات | المخرجات | ملاحظات |
|---|---|---|---|---|
| POST | /api/auth/login | role (admin/doctor/parent), email, password | token, user, role | يرفض الطبيب المعطل (403) |
| POST | /api/auth/register | name, email, password, password_confirmation, national_id, phone?, mother_name?, father_name? | token, user | تسجيل ولي أمر فقط |
| POST | /api/auth/logout | - (يتطلب توكن) | - | يبطل التوكن الحالي |
| GET | /api/auth/me | - (يتطلب توكن) | user, role | بيانات المستخدم الحالي |

### المدير الوطني (auth:admin)

| Method | Route | الوظيفة |
|---|---|---|
| GET | /api/admin/centers | قائمة المراكز مع عدد الأطباء والأطفال |
| POST | /api/admin/centers | إنشاء مركز (name, address, phone?, admin_id?) |
| GET | /api/admin/centers/{id} | تفاصيل مركز |
| PUT | /api/admin/centers/{id} | تحديث مركز |
| DELETE | /api/admin/centers/{id} | حذف مركز (409 إن كان مرتبطاً بسجلات) |
| GET | /api/admin/doctors?center_id= | قائمة الأطباء |
| POST | /api/admin/doctors | إنشاء حساب طبيب (name, email, password, national_id, center_id, specialization?, is_active?) |
| GET | /api/admin/doctors/{id} | تفاصيل طبيب |
| PUT | /api/admin/doctors/{id} | تحديث طبيب (تفعيل/تعطيل، إعادة تعيين كلمة مرور) |
| DELETE | /api/admin/doctors/{id} | حذف طبيب |
| GET | /api/admin/inventory?center_id= | المخزون (مع is_low_stock) |
| POST | /api/admin/inventory | إضافة/تحديث مخزون (center_id, vaccine_id, quantity, min_threshold?) — ينبه المدير بالبريد تحت الحد الأدنى |
| GET | /api/admin/inventory/{id} | سجل مخزون |
| PUT | /api/admin/inventory/{id} | تحديث كمية/حد أدنى — ينبه المدير تحت الحد الأدنى |
| GET | /api/admin/reports | الإجماليات + نسبة التغطية لكل مركز |
| GET | /api/admin/audit?actor_type=&target_table= | سجل التدقيق |
### الطبيب (auth:doctor)

| Method | Route | الوظيفة |
|---|---|---|
| GET | /api/doctor/children | سجلات أطفال مركزه فقط (مع بيانات ولي الأمر) |
| POST | /api/doctor/children | إضافة طفل لمركزه (name, birth_date, gender, parent_id) + توليد جدول اللقاحات تلقائياً |
| GET | /api/doctor/children/{id} | ملف طفل كامل (الوالد + المواعيد) |
| GET | /api/doctor/children/scan/{qr_code} | فتح ملف طفل عن طريق مسح رمز QR |
| GET | /api/doctor/children/{id}/qr | توليد صورة QR للطفل (SVG base64) |
| POST | /api/doctor/parents | إنشاء حساب ولي أمر (يرجع بيانات الدخول لتسليمها لولي الأمر) |
| GET | /api/doctor/appointments?status=booked/completed/cancelled/overdue&date=YYYY-MM-DD | مواعيد المركز مع الفلاتر |
| PUT | /api/doctor/appointments/{id}/complete | تسجيل جرعة كمكتملة (notes?) — يخصم من المخزون ويشعر ولي الأمر |
| GET | /api/doctor/notifications | الإشعارات الواردة للطبيب (unread_count) |
| POST | /api/doctor/notifications | تنبيه يدوي لولي أمر (child_id, message) — يسجل في DB ويحاول إرسال بريد |

### ولي الأمر (auth:parent)

| Method | Route | الوظيفة |
|---|---|---|
| GET | /api/parent/children | أطفاله فقط (مع عدد المواعيد) |
| POST | /api/parent/children | إضافة طفل (name, birth_date, gender, center_id) — يولد qr_code فريد + جدول اللقاحات تلقائياً |
| GET | /api/parent/children/{id}/qr | صورة QR للطفل |
| GET | /api/parent/children/{id}/appointments?status=upcoming/overdue/completed/cancelled | مواعيد طفل (الحالة المحسوبة display_status) |
| PUT | /api/parent/appointments/{id}/reschedule | تعديل موعد مؤكد (appointment_date بعد الآن) + إشعار أطباء المركز |
| PUT | /api/parent/appointments/{id}/cancel | إلغاء موعد مؤكد + إشعار أطباء المركز |
| GET | /api/parent/notifications | إشعارات ولي الأمر (unread_count) |
| PUT | /api/parent/notifications/read-all | تعليم الكل كمقروء |
| PUT | /api/parent/notifications/{id}/read | تعليم إشعار كمقروء |

## الميزة الأهم: توليد جدول اللقاحات التلقائي

عند إضافة طفل جديد (من ولي الأمر أو الطبيب) ينشئ `VaccineScheduleService` موعداً لكل لقاح في جدول `vaccines` (19 لقاحاً وطنياً) بحساب:

```
appointment_date = birth_date + recommended_age_days (الساعة 09:00)
```

وتُحسب حالة كل موعد عند العرض في `display_status`:

- `completed` / `cancelled` حسب قاعدة البيانات
- `overdue` إذا كان الموعد مؤكداً وتاريخه قد فات
- `upcoming` إذا كان مؤكداً وقادماً

## التنبيهات

1. **تلقائي (`type=auto`)**: الأمر المجدول `app:send-reminders` يرسل تذكيراً لولي الأمر قبل الموعد بيوم واحد (بريد + سجل في جدول notifications). مجدول يومياً 08:00 صباحاً في `routes/console.php`.
2. **يدوي (`type=manual`)**: الطبيب يرسل تنبيهاً لولي أمر طفل في مركزه.
3. سجل الإشعار يُكتب في قاعدة البيانات **دائماً** حتى لو فشل إرسال البريد (EMAIL_ENABLED أو بيانات SMTP خاطئة).

لتشغيل المجدول على ويندوز بشكل دائم: أضف مهمة مجدولة تنفذ:

```
C:\xampp\php\php.exe C:\path\to\laqahi-backend\artisan schedule:run
```

كل دقيقة، وسيقوم Laravel بتنفيذ المهام في أوقاتها.

## QR Code

- رمز الطفل الفريد: `LQH-XXXXXXXXXXXX` (يولد تلقائياً).
- `GET .../qr` يرجع صورة SVG بصيغة `data:image/svg+xml;base64,...` (لا تحتاج مكتبات إضافية في الواجهة).
- الطبيب يمسح الرمز ويستدعي `GET /api/doctor/children/scan/{qr_code}` لفتح الملف.

## البريد الإلكتروني (Gmail SMTP)

1. فعّل التحقق بخطوتين في حساب Gmail وولّد **App Password**.
2. ضع البيانات في `.env` (MAIL_USERNAME / MAIL_PASSWORD).
3. للتعطيل المؤقت بدون تعطيل التسجيل في قاعدة البيانات: `EMAIL_ENABLED=false`.

## الربط مع الفرونت إند (laqahi-main)

- **Base URL**: `http://localhost:8000/api`
- **CORS**: مفعّل لـ `http://localhost:5173` فقط (عدّل `FRONTEND_URL` في .env عند الحاجة) — لا حاجة لـ credentials/cookies لأن المصادقة توكن في الترويسة.
- عند تسجيل الدخول خزّن `token` و`role` و`user` (مثلاً في localStorage) وأرسل التوكن في كل طلب.
- أمثلة سريعة (fetch):

```js
const res = await fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ role: 'parent', email: 'x@y.com', password: '...' }),
});
const { success, data } = await res.json();
// بعدها في أي طلب محمي:
headers: { Authorization: `Bearer ${data.token}` }
```

## بنية الكود

```
app/
├── Console/Commands/SendReminders.php      # أمر التذكيرات اليومية
├── Http/Controllers/Api/
│   ├── ApiController.php                   # أساس: استجابات موحدة + Audit
│   ├── AuthController.php                  # المصادقة لكل الأدوار
│   ├── Admin/                              # مراكز، أطباء، مخزون، تقارير، تدقيق
│   ├── Doctor/                             # أطفال، أولياء أمور، مواعيد، إشعارات
│   └── Parent/                             # أطفال، مواعيد، إشعارات، QR
├── Mail/VaccineReminderMail.php            # البريد (قالب عربي RTL)
├── Models/                                 # 10 نماذج مطابقة لجداول قاعدة البيانات
└── Services/
    ├── VaccineScheduleService.php          # توليد الجدول التلقائي
    └── NotificationService.php             # الإشعارات + تنبيه المخزون
```

## سجل التدقيق (Audit Log)

كل عملية حساسة (إنشاء/تحديث/حذف مراكز، أطباء، أطفال، مواعيد، مخزون، إشعارات) تُسجل تلقائياً في `audit_logs` مع نوع الفاعل وهويتك والقيم القديمة والجديدة (JSON).