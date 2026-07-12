# 🎓 شرح الكود الكامل — نظام إدارة المستودعات

هذا الملف يشرح **كل دالة** في المشروع، **اللوجيك** المستخدم في الباك، **سير العمل** الكامل، و**ترتيب استدعاء الـ API**.

---

# 1️⃣ البنية المعمارية — رحلة الطلب (Request Lifecycle)

كل طلب يدخل للنظام يمر بهذه الطبقات بالترتيب:

```
   الفرونت (Frontend)
        │  HTTP Request + Bearer Token
        ▼
┌─────────────────────┐
│  routes/api.php      │  ① أين يذهب الطلب؟ وهل الدور مسموح له؟
│  (Route + Middleware)│     auth:sanctum → هل التوكن صحيح؟
│                      │     role:Admin  → هل الدور مناسب؟
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  Form Request        │  ② هل البيانات المرسلة صحيحة؟
│  (Validation)        │     إذا لا → يرجع 422 تلقائياً ولا يكمل
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  Controller          │  ③ يستقبل الطلب وينسّق العمل
│                      │     لا يحتوي لوجيك معقد — فقط "استقبل، نفّذ، أرجع JSON"
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  Service             │  ④ اللوجيك الحقيقي (Business Logic)
│                      │     Transactions، حسابات، قواعد العمل
└─────────┬───────────┘
          ▼
┌─────────────────────┐
│  Model (Eloquent)    │  ⑤ التخاطب مع قاعدة البيانات
└─────────┬───────────┘
          ▼
     قاعدة البيانات
```

### لماذا هذا التقسيم؟

| الطبقة | مسؤوليتها الوحيدة | ماذا لو حذفناها؟ |
|---|---|---|
| **Route + Middleware** | الحماية والتوجيه | أي شخص يصل لأي شيء |
| **Form Request** | التحقق من صحة المدخلات | بيانات فاسدة تدخل للقاعدة |
| **Controller** | التنسيق وإرجاع JSON | — (لا يمكن حذفه) |
| **Service** | اللوجيك المعقد | الكونترولر يتضخم ولا يمكن إعادة استخدام اللوجيك |
| **Model** | العلاقات والاستعلامات | — (لا يمكن حذفه) |

> **القاعدة الذهبية بالمشروع:** العمليات البسيطة (CRUD عادي) لوجيكها بالكونترولر مباشرة. العمليات الحساسة (تحريك مخزون، أموال، فواتير) لوجيكها بـ **Service** لأنها تحتاج Transactions وتُستدعى من أكثر من مكان.

---

# 2️⃣ هيكل المشروع

```
app/
├── Http/
│   ├── Controllers/     ← 10 كونترولرات (نقطة استقبال الطلبات)
│   └── Requests/        ← 12 Form Request (قواعد الفاليديشن)
├── Models/              ← 13 موديل (الجداول والعلاقات)
├── Services/            ← 5 سيرفيسات (اللوجيك الحساس)
└── Mail/                ← إيميل الترحيب بالموظف الجديد

database/
├── migrations/          ← بنية الجداول
└── seeders/             ← الأدوار الأربعة + المستخدم الأول

routes/
└── api.php              ← كل المسارات مقسمة حسب الدور

bootstrap/app.php        ← تسجيل middleware الأدوار (role)
```

---

# 3️⃣ المصادقة والأدوار — كيف تعمل الحماية؟

## Laravel Sanctum (التوكنات)

- عند تسجيل الدخول بنجاح: `$user->createToken('auth_Token')->plainTextToken` ينشئ توكن ويخزنه **مشفراً** بجدول `personal_access_tokens`.
- الفرونت يرسل التوكن مع كل طلب: `Authorization: Bearer {token}`.
- الـ middleware `auth:sanctum` يفك التوكن، يجد صاحبه، ويحقنه بالطلب — لهذا نستطيع كتابة `$request->user()->id` بأي كونترولر.
- عند الخروج: `$user->currentAccessToken()->delete()` يحذف **هذا التوكن فقط** (لو مسجل دخول من جهازين، الجهاز الثاني يبقى شغالاً).

## Spatie Permission (الأدوار)

- بجدول `roles` توجد 4 أدوار (من `RoleSeeder`): `Admin`, `Warehouse Manager`, `Storekeeper`, `Accountant`.
- ربط المستخدم بدوره: `$user->assignRole($role)` (يحدث في `StaffService`).
- الحماية بالراوت: `role:Storekeeper|Admin` تعني "اسمح لأي من الدورين" — الشرطة العمودية `|` تعني **أو**.
- تم تسجيل الاختصار `role` في `bootstrap/app.php`:
```php
$middleware->alias(['role' => \Spatie\Permission\Middleware\RoleMiddleware::class]);
```

## خريطة الصلاحيات في `routes/api.php`

```
عام (بدون توكن)      → POST /login  (مع throttle:5,1 = 5 محاولات بالدقيقة ضد التخمين)
auth:sanctum فقط      → POST /logout
role:Admin            → إضافة موظفين + كل عمليات الحذف النهائي
role:Storekeeper|Admin → الأصناف، المنتجات، المخزون، الموردين، العملاء، الفواتير، المستندات
role:Accountant|Admin  → الحركات المالية وكشوف الحسابات
role:Warehouse Manager|Admin → الداشبورد والتقارير
```

> **لاحظ الفلسفة:** الأدمن موجود بكل المجموعات (الكل في الكل)، وكل دور يرى عالمه فقط — أمين المستودع لا يرى الأرباح، والمحاسب لا يعدل المخزون.

---

# 4️⃣ الموديلات (Models) — الجداول والعلاقات

كل الموديلات تستخدم `protected $guarded = []` ومعناها: "اسمح بتعبئة كل الأعمدة جماعياً (Mass Assignment)". هذا آمن هنا لأن **كل** المدخلات تمر بفاليديشن قبل الوصول للموديل.

## User
- يستخدم 4 Traits: `HasApiTokens` (سانكتوم)، `HasRoles` (سباتي)، `Notifiable`، `HasFactory`.
- `$hidden = ['password', 'remember_token']` → لا يظهران أبداً في أي JSON.
- `casts(): ['password' => 'hashed']` → أي قيمة تسند للباسورد تشفَّر تلقائياً.
- علاقاته: `createdSuppliers`, `createdCustomers`, `createdCategories`, `createdProducts`, `stockMovements` — كلها `hasMany` عبر عمود `created_by` (من أنشأ ماذا).

## Category
- `products()` → hasMany: الصنف له عدة منتجات.
- `creator()` / `updater()` → belongsTo على `created_by` / `updated_by`.

## Product
- `category()` → belongsTo: المنتج يتبع صنفاً.
- `movements()` → hasMany: كل حركات المخزون على هذا المنتج.
- `isLowStock(): bool` → دالة مساعدة ترجع `true` إذا `quantity <= minimum_quantity`. تستخدم في تفاصيل المنتج وتقارير المدير.

## StockMovement
- `product()` و `creator()`.
- أهم أعمدته: `type` (in/out/adjustment)، `balance_after` (الكمية بعد الحركة — snapshot للتقارير)، `reference_type` + `reference_id` (مصدر الحركة: فاتورة شراء؟ بيع؟ مورد مباشر؟).

## Supplier / Customer
- `financialTransactions()` → **morphMany**: حركاته المالية.
- `purchaseOrders()` (للمورد) / `salesOrders()` (للعميل) → hasMany.

## SalesOrder / PurchaseOrder
- `customer()` أو `supplier()` → belongsTo (طرف الفاتورة).
- `items()` → hasMany: أسطر الفاتورة.
- `documents()` → **morphMany**: المستندات المرفقة.
- `creator()` / `updater()`.

## SalesOrderItem / PurchaseOrderItem
- `salesOrder()` / `purchaseOrder()` → belongsTo (الفاتورة الأم).
- `product()` → belongsTo.

## FinancialTransaction — العلاقة الأهم بالمشروع
```php
public function financialable() { return $this->morphTo(); }
```
هذه **علاقة Polymorphic**: بدل أن نعمل عمودين (`customer_id` و `supplier_id`) ونترك أحدهما فارغاً دائماً، نستخدم عمودين ذكيين:
- `financialable_type` = `"App\Models\Customer"` أو `"App\Models\Supplier"`
- `financialable_id` = رقم السجل

فيصبح جدول مالي **واحد** يخدم الطرفين، وإذا أضفنا لاحقاً "موظفين لهم سلف" مثلاً — نفس الجدول يخدمهم بدون تعديل.

## Document
- نفس الفكرة: `documentable()` morphTo → المستند يتبع `SalesOrder` أو `PurchaseOrder` بنفس الجدول.

---

# 5️⃣ الكونترولرات — دالة دالة

## AuthController

| الدالة | ماذا تفعل بالضبط |
|---|---|
| `login()` | ① يمرر البيانات على `LoginRequest` (إيميل + باسورد إجباريان) ② `Auth::attempt()` يقارن الباسورد المشفر — فشل؟ يرجع 401 ③ نجح؟ ينشئ توكن سانكتوم ويرجعه للفرونت |
| `logout()` | يحذف التوكن الحالي فقط من قاعدة البيانات → التوكن يصبح ميتاً فوراً |

## StaffController (Admin فقط)

| الدالة | ماذا تفعل |
|---|---|
| `storeStaff()` | يستقبل بيانات الموظف + دوره عبر `RequestStaff` (الإيميل unique، الباسورد 8+ أحرف، الدور واحد من الأربعة، و`shift_type` إجباري فقط للـ Storekeeper) ثم يمرر كل شيء لـ `StaffService` |

**StaffService::createStaff()** — اللوجيك:
```
داخل DB::transaction:                ← لو فشلت أي خطوة، كل شيء يتراجع
  1. User::create(...)               ← مع Hash::make للباسورد
  2. $user->assignRole($role)        ← ربط الدور (سباتي)
  3. Mail::to(...)->send(...)        ← إيميل ترحيبي فيه بيانات الدخول
```

## CategoryController — CRUD بسيط (اللوجيك بالكونترولر مباشرة)

| الدالة | اللوجيك |
|---|---|
| `index()` | `withCount('products')` → يجلب الأصناف + عمود `products_count` محسوب بالـ SQL (بدون جلب المنتجات نفسها = أداء أفضل) + pagination |
| `store()` | فاليديشن (`name` إجباري وunique) ثم `create` مع حقن `created_by = $request->user()->id` |
| `show()` | `with('products')` → الصنف **مع** كل منتجاته. `findOrFail` يرمي 404 تلقائياً إذا غير موجود |
| `update()` | فاليديشن ثم `update` مع حقن `updated_by` |
| `destroy()` | حذف مباشر — **الراوت نفسه** يحميه بأنه Admin فقط (الحماية بالراوت وليس بالكونترولر) |

## ProductController

| الدالة | اللوجيك |
|---|---|
| `index()` | سلسلة `when()` — كل فلتر يطبق **فقط إذا أرسله الفرونت**: بحث (name/sku/barcode بـ LIKE)، `category_id`، `status`، `low_stock` (مقارنة عمود بعمود `whereColumn`) |
| `store()` | ⭐ **أهم لوجيك بالمنتجات:** الكمية الافتتاحية **لا تُخزن مباشرة**. المنتج ينشأ بكمية 0، ثم تسجل الكمية عبر `StockMovementService->moveIn()` كحركة "وارد" بملاحظة `Opening stock`. **لماذا؟** حتى يكون سجل الحركات موثقاً 100% — مجموع الحركات = الكمية الحالية دائماً. وكل هذا داخل Transaction |
| `show()` | المنتج + صنفه + **آخر 10 حركات فقط** (`latest()->limit(10)`) + علم `is_low_stock` |
| `update()` | تعديل البيانات **ما عدا quantity** — الـ `UpdateProductRequest` أصلاً لا يقبل حقل كمية. أي تغيير كمية = حركة مخزون. هذه قاعدة صارمة |
| `destroy()` | حذف (Admin فقط من الراوت) |

## StockMovementController

| الدالة | اللوجيك |
|---|---|
| `index()` | سجل الحركات مع فلاتر (منتج/نوع/تاريخ من-إلى) + تحميل علاقتين بشكل انتقائي: `product:id,name,sku` (أعمدة محددة فقط = استعلام أخف) |
| `storeIn()` | فاليديشن `StockInRequest` ثم تفويض كامل لـ `StockMovementService->moveIn()` |
| `storeOut()` | فاليديشن `StockOutRequest` (النوع: out أو adjustment) ثم `moveOut()` |

## SupplierController / CustomerController — CRUD كلاسيكي
فاليديشن inline داخل الكونترولر (بدون Form Request منفصل — أسلوب زميلك بشار)، مع حقن `created_by`/`updated_by` عبر `Auth::id()`. الردود بصيغة `"status": "success"` ورسائل عربية.

## SalesOrderController

| الدالة | اللوجيك |
|---|---|
| `index()` | فواتير البيع + فلاتر (عميل/حالة/تاريخ) + `customer:id,name,phone` |
| `store()` | فاليديشن `StoreSalesOrderRequest` (الفاتورة لازم يكون فيها سطر واحد على الأقل `items min:1`) → `SalesOrderService->create()` |
| `show()` | الفاتورة + العميل + الأسطر مع منتجاتها + المستندات المرفقة + من أنشأها |
| `confirm()` | يجلب الفاتورة **مع أسطرها** ثم `SalesOrderService->confirm()` — هنا يحدث كل السحر (انظر السيرفيس) |
| `cancel()` | `SalesOrderService->cancel()` — إلغاء آمن |
| `destroy()` | حذف نهائي (Admin) — بفضل `onDelete('cascade')` بالميغريشن، حذف الفاتورة يحذف أسطرها تلقائياً |

## PurchaseOrderController
نسخة مطابقة لكونترولر البيع مع استبدال العميل بالمورد والصرف بالإدخال.

## FinancialTransactionController

| الدالة | اللوجيك |
|---|---|
| `index()` | كل الحركات المالية + فلاتر + `financialable:id,name` (يجلب اسم الطرف أياً كان نوعه — قوة الـ polymorphic) |
| `store()` | ① فاليديشن ② **قاعدة عمل يدوية:** عميل → receipt فقط، مورد → payment فقط (وإلا 422) ③ يجلب الطرف بـ `findOrFail` ④ `FinancialTransactionService->record()` |
| `customerStatement()` / `supplierStatement()` | يجلبان الطرف ثم يستدعيان `statement()` المشتركة |
| `statement()` (private) | كل حركات الطرف **من الأقدم للأحدث** (شكل كشف الحساب الطبيعي) + `current_balance` = رصيد آخر حركة |

## DocumentController

| الدالة | اللوجيك |
|---|---|
| `store()` | ① فاليديشن (pdf/صور، 5MB) ② يتأكد أن الفاتورة الهدف **موجودة فعلاً** (`findOrFail`) ③ `$file->store('documents', 'public')` يخزن الملف باسم عشوائي آمن ④ ينشئ السجل عبر علاقة الـ morph: `$documentable->documents()->create()` — لارافيل يملأ عمودي النوع والـ id تلقائياً |
| `download()` | يتحقق أن الملف موجود فيزيائياً على القرص ثم يرجعه **باسمه الأصلي** (المستخدم رفع "فاتورة.pdf" → يحمله "فاتورة.pdf" وليس الاسم العشوائي) |
| `destroy()` | يحذف الملف من القرص **ثم** السجل من القاعدة (Admin فقط) |

## ReportController (قراءة فقط — لا يعدل شيئاً، لذلك لا يحتاج Service)

| الدالة | اللوجيك |
|---|---|
| `dashboard()` | يجمع كل شيء بمكان واحد: **اليوم** (وارد/صادر بـ `sum('quantity')`، مبيعات/مشتريات مؤكدة بـ `sum('grand_total')`) + **المعلق** (عدد الفواتير pending) + **تنبيه النواقص** (`whereColumn quantity <= minimum_quantity`) + **عدادات عامة** + **بيانات رسمين**: حركة 7 أيام (تجميع بالكولكشن حسب اليوم) ومبيعات ضد مشتريات 6 أشهر (مصفوفة ثابتة 6 عناصر حتى لو أشهر فارغة — حتى لا ينكسر الرسم بالفرونت) |
| `inventory()` | كل المنتجات بكمياتها + فلاتر + إجماليات (عدد، كمية كلية، عدد النواقص) + علم `is_low_stock` لكل منتج |
| `lowStock()` | فقط ما وصل للحد الأدنى، مرتبة من الأقل كمية (الأخطر أولاً) |
| `stagnantProducts()` | ⭐ الأذكى: منتجات **كميتها > 0** (موجودة بالمستودع) **وليس لها** أي حركة `out` منذ X يوم — `whereDoesntHave('movements', ...)` يترجم لـ SQL `NOT EXISTS`. الافتراضي 30 يوماً، قابل للتغيير بـ `?days=60` |
| `stockMovementsSummary()` | استعلام تجميعي واحد: `SUM(CASE WHEN type='in'...)` لكل نوع + `GROUP BY product_id` — الداتابيس تحسب، ليس PHP (أسرع بكثير) |
| `suppliersPerformance()` | لكل مورد: عدد فواتيره المؤكدة (`withCount` مشروط) + إجمالي التعامل (`withSum`) + آخر توريد (`withMax`) — ثلاثتها Subqueries بدون جلب الفواتير نفسها |
| `topSellingProducts()` | تجميع على أسطر فواتير البيع **المؤكدة فقط** (`whereHas('salesOrder', status=confirmed)`): مجموع الكمية والإيراد لكل منتج، ترتيب تنازلي، أعلى 10 |

---

# 6️⃣ السيرفيسات — قلب النظام النابض 💗

هنا اللوجيك الحقيقي. ثلاث تقنيات تتكرر، افهمها مرة واحدة:

### 🔒 التقنية 1: `DB::transaction()`
```php
return DB::transaction(function () { /* عدة عمليات */ });
```
**"إما كل شيء أو لا شيء"** — لو تأكيد الفاتورة صرف المخزون ثم فشل تسجيل الحركة المالية، الـ Transaction يرجع المخزون كما كان. بدونها: مخزون ناقص بدون أثر مالي = كارثة محاسبية.

### 🔒 التقنية 2: `lockForUpdate()`
```php
$product = Product::lockForUpdate()->findOrFail($id);
```
يقفل سطر المنتج بقاعدة البيانات حتى نهاية الـ Transaction. **السيناريو الذي يمنعه:** موظفان يصرفان من منتج كميته 5 بنفس اللحظة — بدون القفل كلاهما يقرأ "5 متوفر" ويصرفان 5+5 من رصيد 5! مع القفل: الثاني **ينتظر** الأول، ثم يقرأ الكمية الجديدة (0) فيرفض طلبه.

### 🔒 التقنية 3: `ValidationException::withMessages()`
```php
throw ValidationException::withMessages(['quantity' => 'Insufficient stock...']);
```
يرمي خطأ 422 بنفس شكل أخطاء الفاليديشن العادية — الفرونت يعالج كل الأخطاء بطريقة موحدة، والـ Transaction يتراجع تلقائياً لأن الاستثناء يقطع التنفيذ.

---

## StockMovementService

### `moveIn(array $data, int $userId)` — الإدخال
```
Transaction {
  1. اقفل سطر المنتج (lockForUpdate)
  2. newQuantity = الكمية الحالية + الداخلة
  3. حدّث المنتج (quantity + updated_by)
  4. سجل StockMovement: type=in + balance_after=newQuantity + المرجع + من نفذها
}
```

### `moveOut(array $data, int $userId)` — الإخراج
نفس الخطوات + خطوة حاسمة قبل التنفيذ:
```
if ($product->quantity < $data['quantity'])  → ارفض 422 "Insufficient stock"
```
والنوع يأتي من الطلب: `out` (صادر) أو `adjustment` (جرد/تالف).

> 💡 **لماذا `balance_after`؟** نصور الرصيد لحظة الحركة. بعد سنة، تقدر تعرف "كم كان مخزون المنتج يوم 5 الشهر الماضي؟" بقراءة سطر واحد، بدل إعادة حساب كل الحركات من البداية.

---

## FinancialTransactionService

### `record($party, $type, $amount, $referenceNumber, $notes, $userId)`
الدالة المالية المركزية — **كل** قرش يتحرك بالنظام يمر من هنا:
```
Transaction {
  1. آخر رصيد للطرف:
     آخر حركة له (lockForUpdate + latest('id')) → خذ balance_after منها
     لا يوجد حركات؟ → الرصيد 0
  2. الحساب:
     sale أو purchase  → رصيد جديد = القديم + المبلغ   (فاتورة = دين يزيد)
     receipt أو payment → رصيد جديد = القديم − المبلغ   (سند = دين ينقص)
  3. سجل الحركة مع balance_after الجديد
}
```
لاحظ التماثل الجميل مع المخزون: **الرصيد المالي يعامل تماماً مثل كمية المنتج** — سلسلة حركات موثقة، كل حركة تحمل الرصيد بعدها، ولا أحد يعدل الرصيد مباشرة.

---

## SalesOrderService

### `create(array $data, int $userId)` — إنشاء الفاتورة
```
Transaction {
  1. total = مجموع (كمية × سعر) لكل الأسطر        ← السيرفر يحسب، لا نثق بأرقام الفرونت
  2. grand_total = total − discount + tax
  3. أنشئ الفاتورة: status=pending + رقم مولد تلقائياً
  4. أنشئ أسطرها واحداً واحداً (subtotal محسوب لكل سطر)
}
```
> ⚠️ **لا يتحرك أي مخزون هنا** — الفاتورة مجرد "مسودة" بانتظار التأكيد.

### `confirm(SalesOrder $order, int $userId)` — التأكيد ⭐ أهم دالة بالنظام
```
حارس أولاً (خارج الـ Transaction):
  status ≠ pending؟ → ارفض 422    ← يمنع التأكيد المزدوج (double confirm)

Transaction {
  المرحلة 1 — تحقق شامل قبل أي تنفيذ:
    لكل سطر: هل كمية المنتج تكفي؟
    أي نقص → ارفض الفاتورة كلها برسالة تذكر اسم المنتج والمتوفر والمطلوب
    ← "الكل أو لا شيء": لا نصرف 3 منتجات ثم نكتشف أن الرابع ناقص!

  المرحلة 2 — الصرف:
    لكل سطر → stockMovementService.moveOut(reference: SalesOrder #id)
    ← الحركة تعرف "أنا نتيجة الفاتورة الفلانية" = تتبع كامل

  المرحلة 3 — المالية:
    financialTransactionService.record(العميل, 'sale', grand_total, رقم الفاتورة)
    ← قيمة الفاتورة صارت ديناً موثقاً على العميل

  المرحلة 4 — status = confirmed + updated_by
}
```

### `cancel(SalesOrder $order, int $userId)`
حارس واحد: `pending` فقط تلغى. **لماذا لا تلغى المؤكدة؟** لأن مخزونها خرج ودينها سجل — التراجع عنها يحتاج "مرتجع بيع" (فاتورة عكسية)، وهذه ميزة مستقلة.

### `generateInvoiceNumber()`
`SO-20260712-0001` = بادئة + تاريخ اليوم + (أكبر id + 1) مبطن بأصفار. مقروء للإنسان وفريد.

## PurchaseOrderService
مرآة لسيرفيس البيع مع 3 فروقات:
1. `moveIn` بدل `moveOut` (بضاعة تدخل).
2. **لا يوجد فحص كميات** عند التأكيد — الشراء دائماً يزيد المخزون، لا شيء ينقص.
3. الحركة المالية `purchase` على **المورد** (دين علينا) بدل `sale` على العميل.

---

# 7️⃣ سير العمل الكامل (Workflows)

## 🛒 دورة الشراء (من المورد إلى الرف)

```
أمين المستودع                    النظام
     │
     │ POST /purchase-orders
     │──────────────────────────► فاتورة PO-xxx بحالة pending
     │                            (الإجماليات محسوبة، لا مخزون تحرك)
     │
     │ البضاعة وصلت فعلياً وفحصها
     │
     │ POST /purchase-orders/7/confirm
     │──────────────────────────► Transaction:
     │                            ├─ لكل سطر: moveIn → الكمية ↑ + حركة in مسجلة
     │                            ├─ حركة مالية purchase → دين المورد ↑
     │                            └─ status = confirmed
     │
     │ POST /documents (صورة فاتورة المورد الورقية)
     │──────────────────────────► مستند مربوط بالفاتورة
```

## 💵 دورة البيع (من الرف إلى العميل)

```
     │ POST /sales-orders          → SO-xxx بحالة pending
     │ POST /sales-orders/8/confirm
     │──────────────────────────► Transaction:
     │                            ├─ فحص توفر كل الأسطر (أي نقص = رفض كامل)
     │                            ├─ لكل سطر: moveOut → الكمية ↓ + حركة out
     │                            ├─ حركة مالية sale → دين العميل ↑
     │                            └─ status = confirmed
```

## 💰 الدورة المالية (المحاسب)

```
العميل جاء ودفع 200 كاش:
  POST /finance/transactions { party_type: customer, type: receipt, amount: 200 }
  → رصيد العميل: 440 − 200 = 240

الشركة دفعت للمورد 500:
  POST /finance/transactions { party_type: supplier, type: payment, amount: 500 }
  → رصيد المورد: 1005 − 500 = 505

نهاية الشهر:
  GET /finance/statement/customer/1
  → كشف حساب كامل: كل فاتورة وكل دفعة والرصيد بعد كل حركة
```

## 📉 الجرد والتوالف (بدون فواتير)

```
اكتشفنا 2 قطعة مكسورة:
  POST /stock/move-out { type: adjustment, reference_type: Internal, notes: "تالف" }
  → الكمية ↓ وحركة adjustment مسجلة باسم من نفذها (مساءلة كاملة)
```

## 📊 دورة المدير (مراقبة فقط)

```
كل صباح: GET /dashboard          → أرقام اليوم + الرسوم + كم فاتورة معلقة
أسبوعياً: GET /reports/stagnant-products?days=30 → ما البضاعة الميتة؟
          GET /reports/low-stock              → ماذا نطلب من الموردين؟
شهرياً:   GET /reports/suppliers-performance   → من أفضل مورد نفاوضه؟
          GET /reports/top-selling-products    → ما الذي يبيع فعلاً؟
```

---

# 8️⃣ ترتيب استدعاء الـ API — خارطة الفرونت

## أ. التسلسل المنطقي عند بناء الشاشات (أول تشغيل للنظام)

```
1. POST /login (Admin)                        ← لا شيء يعمل قبل التوكن
2. POST /add/staff/admin                      ← أنشئ فريق العمل بأدوارهم
3. POST /categories                           ← الأصناف أولاً (المنتج يحتاج category_id)
4. POST /products                             ← المنتجات (بكمية افتتاحية أو 0)
5. POST /suppliers + POST /customers          ← أطراف التعامل
6. POST /purchase-orders → confirm            ← عبئ المستودع
7. POST /sales-orders → confirm               ← ابدأ البيع
8. POST /finance/transactions                 ← سجل الدفعات
9. GET /dashboard + /reports/*                ← راقب
```
> **القاعدة:** لا تنشئ كياناً قبل الكيان الذي يعتمد عليه (منتج قبل صنفه = خطأ 422).

## ب. ترتيب الاستدعاءات داخل كل شاشة

**شاشة إنشاء فاتورة بيع:**
```
عند الفتح (متوازيان):   GET /customers  +  GET /products
أثناء التعبئة:          فلترة المنتجات محلياً أو GET /products?search=...
عند الحفظ:              POST /sales-orders
بعد الحفظ:              عرض الفاتورة pending وزرا [تأكيد] [إلغاء]
عند التأكيد:            POST /sales-orders/{id}/confirm
   نجح 200؟            حدّث الشاشة status=confirmed
   فشل 422؟            اعرض رسالة النقص كما جاءت من السيرفر
```

**شاشة كشف الحساب:**
```
GET /customers                              ← قائمة للاختيار
GET /finance/statement/customer/{id}        ← الكشف نفسه (الحركات مرتبة زمنياً جاهزة للجدول)
```

**الداشبورد:**
```
GET /dashboard    ← طلب واحد فقط يرجع كل الأرقام والرسوم (مصمم هكذا عمداً
                     حتى لا يرسل الفرونت 8 طلبات لكل تحديث)
```

## ج. قواعد عامة للفرونت

1. **خزن التوكن** (localStorage) وأرفقه بكل طلب عبر interceptor.
2. **401** بأي رد → التوكن مات → حول لصفحة الدخول.
3. **403** → المستخدم فتح شاشة ليست لدوره → أخف الشاشات حسب الدور أصلاً من القائمة الجانبية.
4. **422** → اعرض `errors` حقلاً حقلاً تحت المدخلات.
5. **لا تحسب الإجماليات نهائياً** — أرسل الأسطر الخام واعرض ما يرجعه السيرفر (مصدر الحقيقة واحد).
6. أزرار **تأكيد/إلغاء** تظهر فقط إذا `status === "pending"`، و**حذف** فقط لدور Admin.

---

# 9️⃣ القرارات التصميمية — "ليش هيك؟"

| القرار | السبب |
|---|---|
| **الكمية لا تعدَّل مباشرة أبداً** (لا من إنشاء المنتج ولا من تعديله) | كل تغيير كمية = حركة مسجلة باسم منفذها وسببها → `SUM(الحركات) = الكمية الحالية` دائماً. لا "تسريب" مخزون بدون أثر |
| **الفاتورة على مرحلتين (pending → confirmed)** | الإنشاء "مسودة" رخيصة التراجع، والتأكيد هو لحظة الحقيقة. يعطي فرصة مراجعة، ويمهد لميزة "موافقة المدير" مستقبلاً |
| **الفاتورة المؤكدة لا تلغى** | مخزونها تحرك ودينها سجل — التراجع الصحيح محاسبياً هو فاتورة مرتجع، وليس مسح التاريخ |
| **Polymorphic للمالية والمستندات** | جدول واحد بدل جدولين شبه متطابقين، وقابل للتوسع لأطراف جديدة بدون migration |
| **`balance_after` مخزنة snapshot** | كشف الحساب والتقارير التاريخية بقراءة مباشرة بدون إعادة حساب آلاف الحركات |
| **الأسعار على سطر الفاتورة** (`unit_price` وقت البيع) | السعر يتغير مع الزمن — الفاتورة القديمة يجب أن تحفظ سعر يومها للأبد |
| **السيرفر يحسب الإجماليات ويولد أرقام الفواتير** | لا ثقة بأرقام تأتي من المتصفح — أي عبث بالفرونت لا يؤثر |
| **الحذف بالراوت للأدمن وليس شرطاً بالكونترولر** | طبقة الحماية بمكان واحد واضح (`api.php`) بدل شروط متناثرة |
| **`onDelete('cascade')` للأسطر و`set null` للموظفين** | حذف الفاتورة يسحب أسطرها معها (لا أسطر يتيمة)، لكن حذف موظف لا يمسح تاريخ ما أنشأه |

---

# 🔟 الصورة الكبيرة — كيف يمسك النظام بعضه

```
                        ┌──────────────┐
                        │   users      │ (4 أدوار)
                        └──────┬───────┘
                               │ created_by / updated_by في كل الجداول
     ┌─────────────────────────┼──────────────────────────┐
     ▼                         ▼                          ▼
┌──────────┐  category_id ┌──────────┐              ┌───────────┐
│categories│◄─────────────│ products │              │ suppliers │
└──────────┘              └────┬─────┘              └─────┬─────┘
                               │ product_id               │ supplier_id
                 ┌─────────────┼─────────────┐            ▼
                 ▼             ▼             ▼      ┌────────────────┐
        ┌───────────────┐ ┌─────────┐ ┌──────────┐ │purchase_orders │──┐
        │stock_movements│ │so_items │ │ po_items │◄┤   + items      │  │
        └───────▲───────┘ └────▲────┘ └──────────┘ └────────────────┘  │
                │              │ sales_order_id                        │
                │         ┌────┴────────┐         ┌───────────┐        │
   reference    │         │sales_orders │◄────────│ customers │        │
   (تتبع المصدر)└─────────┤             │         └─────┬─────┘        │
                          └──────┬──────┘               │              │
                                 │                      ▼              │
                                 │            ┌────────────────────┐   │
                                 ├───────────►│financial_transactions│◄─┤ (morph)
                                 │            └────────────────────┘   │
                                 │            ┌───────────┐            │
                                 └───────────►│ documents │◄───────────┘ (morph)
                                              └───────────┘
```

**ثلاث سلاسل موثقة تحكم كل شيء:**
1. **سلسلة المخزون:** كل تغير كمية = سطر في `stock_movements` بمرجعه ومنفذه ورصيده بعده.
2. **سلسلة المال:** كل تغير دين = سطر في `financial_transactions` بمرجعه ومنفذه ورصيده بعده.
3. **سلسلة التوثيق:** كل عملية تحمل `created_by`/`updated_by`، وكل فاتورة يمكن إرفاق مستنداتها.

والرابط بينها: **تأكيد الفاتورة** — اللحظة الوحيدة التي تتحرك فيها السلسلتان الأولى والثانية معاً داخل Transaction واحد.

---
*آخر تحديث: 2026-07-12*
