# 📦 Warehouse Management System — API Documentation

توثيق كامل للـ API الخاص بنظام إدارة المستودعات، موجّه لفريق الـ Frontend.

---

## 🌐 معلومات عامة

| | |
|---|---|
| **Base URL** | `http://localhost:8000/api` |
| **Content-Type** | `application/json` (ما عدا رفع الملفات: `multipart/form-data`) |
| **Authentication** | Laravel Sanctum — Bearer Token |

### Headers المطلوبة بكل طلب محمي

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

> ⚠️ لا تنسَ `Accept: application/json` — بدونها لارافيل قد يعيد HTML بدل JSON عند الأخطاء.

### الأدوار والصلاحيات

| الدور | الصلاحيات |
|---|---|
| **Admin** | كل شيء + الحذف النهائي (حصري) + إضافة الموظفين |
| **Storekeeper** | الأصناف، المنتجات، حركات المخزون، الموردين، العملاء، فواتير البيع والشراء، المستندات |
| **Accountant** | الحركات المالية، سندات القبض والصرف، كشوف الحسابات |
| **Warehouse Manager** | لوحة القيادة (Dashboard) والتقارير |

### شكل الأخطاء الموحّد

| Status | متى | شكل الرد |
|---|---|---|
| `401` | توكن مفقود أو منتهي | `{ "message": "Unauthenticated." }` |
| `403` | الدور لا يملك الصلاحية | `{ "message": "User does not have the right roles." }` |
| `404` | السجل غير موجود | `{ "message": "No query results for model ..." }` |
| `422` | خطأ فاليديشن | انظر المثال أدناه |

**مثال رد `422 Unprocessable Entity`:**
```json
{
  "message": "The quantity field is required. (and 1 more error)",
  "errors": {
    "quantity": ["The quantity field is required."],
    "items.0.product_id": ["The selected items.0.product_id is invalid."]
  }
}
```

### شكل الـ Pagination

كل الراوتات التي تستخدم pagination ترجع `data` بهذا الشكل (Laravel القياسي):

```json
{
  "message": "...",
  "data": {
    "current_page": 1,
    "data": [ /* ... السجلات هنا ... */ ],
    "first_page_url": "http://localhost:8000/api/products?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/products?page=3",
    "links": [],
    "next_page_url": "http://localhost:8000/api/products?page=2",
    "path": "http://localhost:8000/api/products",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```
> للتنقل بين الصفحات أرسل `?page=2`.

---

## 🔑 1. المصادقة (Auth)

### `POST /login` — تسجيل الدخول
> عام (بدون توكن) — محمي بـ throttle: 5 محاولات بالدقيقة.

**Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response `201`:**
```json
{
  "massage": "User log in Succssfully ",
  "Token": "1|dGhpc2lzYXRva2Vu..."
}
```
> ⚠️ **انتبه:** المفتاح اسمه `Token` بحرف كبير و `massage` (بهذا الإملاء) — خذها كما هي بالفرونت. خزّن التوكن وأرسله بكل الطلبات اللاحقة.

**Response `401`:** `{ "message": "Invalid email or password" }`

---

### `POST /logout` — تسجيل الخروج
> يتطلب توكن.

**Response `200`:**
```json
{
  "message": "The log out successfully",
  "is_verified": false
}
```

---

## 👥 2. إدارة الموظفين (Admin فقط)

### `POST /add/staff/admin` — إضافة موظف جديد

**Body:**
```json
{
  "first_name": "أحمد",
  "last_name": "محمد",
  "email": "ahmad@warehouse.com",
  "password": "12345678",
  "role": "Storekeeper",
  "shift_type": "morning"
}
```

| الحقل | النوع | ملاحظات |
|---|---|---|
| `role` | string | واحدة من: `Admin`, `Warehouse Manager`, `Storekeeper`, `Accountant` |
| `shift_type` | string | **إجباري فقط إذا كان الدور Storekeeper**: `morning` أو `night` |
| `password` | string | 8 أحرف على الأقل |

**Response `201`:**
```json
{
  "message": "Staff member created successfully",
  "data": {
    "id": 5,
    "first_name": "أحمد",
    "last_name": "محمد",
    "email": "ahmad@warehouse.com",
    "shift_type": "morning",
    "created_at": "2026-07-12T10:00:00.000000Z",
    "updated_at": "2026-07-12T10:00:00.000000Z"
  }
}
```

---

## 🏷️ 3. الأصناف (Categories) — Storekeeper | Admin

### `GET /categories` — كل الأصناف (paginated + عدد منتجات كل صنف)

**Response `200`:** (داخل `data.data`)
```json
{
  "message": "Categories retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "إلكترونيات",
        "description": "أجهزة وقطع إلكترونية",
        "created_by": 1,
        "updated_by": null,
        "created_at": "2026-07-10T09:00:00.000000Z",
        "updated_at": "2026-07-10T09:00:00.000000Z",
        "products_count": 12
      }
    ],
    "per_page": 15,
    "total": 4
  }
}
```

### `POST /categories` — إضافة صنف

**Body:**
```json
{
  "name": "إلكترونيات",
  "description": "أجهزة وقطع إلكترونية"
}
```
**Response `201`:** `{ "message": "Category created successfully", "data": { ...الصنف... } }`

### `GET /categories/{id}` — تفاصيل صنف مع منتجاته
**Response `200`:** الصنف + مصفوفة `products` كاملة.

### `PUT /categories/{id}` — تعديل صنف
نفس body الإضافة (الحقول اختيارية). **Response `200`.**

### `DELETE /categories/{id}` — حذف صنف (**Admin فقط**)
**Response `200`:** `{ "message": "Category deleted successfully" }`

---

## 📦 4. المنتجات (Products) — Storekeeper | Admin

### `GET /products` — قائمة المنتجات (paginated)

**Query Parameters (كلها اختيارية):**

| Param | مثال | الوظيفة |
|---|---|---|
| `search` | `?search=لابتوب` | بحث بالاسم أو SKU أو الباركود |
| `category_id` | `?category_id=1` | فلترة حسب الصنف |
| `status` | `?status=active` | `active` أو `inactive` |
| `low_stock` | `?low_stock=1` | فقط المنتجات تحت الحد الأدنى |
| `page` | `?page=2` | رقم الصفحة |

**Response `200`:** (كل منتج يأتي مع صنفه `category`)
```json
{
  "message": "Products retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "category_id": 1,
        "name": "لابتوب ديل",
        "sku": "DELL-5520",
        "barcode": "123456789",
        "description": null,
        "quantity": 25,
        "minimum_quantity": 5,
        "image": null,
        "status": "active",
        "created_by": 1,
        "updated_by": null,
        "created_at": "2026-07-10T09:30:00.000000Z",
        "updated_at": "2026-07-11T14:00:00.000000Z",
        "category": { "id": 1, "name": "إلكترونيات", "description": null }
      }
    ],
    "per_page": 15,
    "total": 30
  }
}
```

### `POST /products` — إضافة منتج

**Body:**
```json
{
  "category_id": 1,
  "name": "لابتوب ديل",
  "sku": "DELL-5520",
  "barcode": "123456789",
  "description": "وصف اختياري",
  "quantity": 10,
  "minimum_quantity": 5,
  "image": "products/dell.jpg",
  "status": "active"
}
```
> 💡 `quantity` هنا هي **الكمية الافتتاحية** — تسجَّل تلقائياً كحركة مخزون "وارد" بملاحظة `Opening stock`.

**Response `201`:** `{ "message": "Product created successfully", "data": { ...المنتج مع category... } }`

### `GET /products/{id}` — تفاصيل منتج + آخر 10 حركات

**Response `200`:**
```json
{
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "لابتوب ديل",
    "quantity": 25,
    "minimum_quantity": 5,
    "category": { "id": 1, "name": "إلكترونيات" },
    "movements": [
      {
        "id": 10,
        "type": "in",
        "quantity": 20,
        "balance_after": 25,
        "reference_type": "PurchaseOrder",
        "reference_id": 3,
        "notes": "Purchase invoice PO-20260711-0003",
        "creator": { "id": 2, "first_name": "أحمد", "last_name": "محمد" },
        "created_at": "2026-07-11T14:00:00.000000Z"
      }
    ]
  },
  "is_low_stock": false
}
```

### `PUT /products/{id}` — تعديل منتج
نفس حقول الإضافة **ما عدا `quantity`** — الكمية لا تعدَّل من هنا أبداً، فقط عبر حركات المخزون أو الفواتير. **Response `200`.**

### `DELETE /products/{id}` — حذف منتج (**Admin فقط**)
**Response `200`:** `{ "message": "Product deleted successfully" }`

---

## 🔄 5. حركات المخزون (Stock Movements) — Storekeeper | Admin

### `GET /stock/history` — سجل الحركات (paginated)

**Query Parameters (اختيارية):** `product_id`, `type` (`in`/`out`/`adjustment`), `from_date`, `to_date` (بصيغة `YYYY-MM-DD`)

**Response `200`:**
```json
{
  "message": "Stock movements retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 15,
        "product_id": 1,
        "type": "out",
        "quantity": 3,
        "balance_after": 22,
        "reference_type": "SalesOrder",
        "reference_id": 7,
        "notes": "Sales invoice SO-20260712-0007",
        "created_by": 2,
        "created_at": "2026-07-12T11:00:00.000000Z",
        "product": { "id": 1, "name": "لابتوب ديل", "sku": "DELL-5520" },
        "creator": { "id": 2, "first_name": "أحمد", "last_name": "محمد" }
      }
    ],
    "per_page": 15,
    "total": 50
  }
}
```

### `POST /stock/move-in` — إدخال بضاعة يدوي (وارد)

**Body:**
```json
{
  "product_id": 1,
  "quantity": 10,
  "reference_type": "Supplier",
  "reference_id": 2,
  "notes": "توريد مباشر"
}
```
> `reference_type` هنا: `Supplier` أو `Internal` فقط.

**Response `201`:**
```json
{
  "message": "Stock added successfully",
  "data": {
    "id": 16,
    "product_id": 1,
    "type": "in",
    "quantity": 10,
    "balance_after": 32,
    "product": { "id": 1, "name": "لابتوب ديل", "sku": "DELL-5520", "quantity": 32 }
  }
}
```

### `POST /stock/move-out` — إخراج بضاعة يدوي (صادر أو جرد/تالف)

**Body:**
```json
{
  "product_id": 1,
  "quantity": 2,
  "type": "adjustment",
  "reference_type": "Internal",
  "reference_id": 0,
  "notes": "قطع تالفة"
}
```
> `type`: `out` (إخراج عادي) أو `adjustment` (جرد/تالف). `reference_type`: `Customer` أو `Internal`.

**Response `201`:** نفس شكل move-in مع `"message": "Stock removed successfully"`.

**Response `422`** إذا الكمية غير كافية:
```json
{
  "message": "Insufficient stock. Available quantity: 5",
  "errors": { "quantity": ["Insufficient stock. Available quantity: 5"] }
}
```

---

## 🚚 6. الموردون (Suppliers) — Storekeeper | Admin

> ⚠️ **ملاحظة للفرونت:** ردود الموردين والعملاء تستخدم `"status": "success"` بدل `"message"` — انتبه للفرق عن باقي الراوتات.

### `GET /suppliers` — كل الموردين (**بدون pagination** — مصفوفة كاملة)
**Response `200`:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "شركة التقنية",
      "company_name": "التقنية المحدودة",
      "email": "info@tech.com",
      "phone": "0991234567",
      "address": "دمشق - المزة",
      "city": "دمشق",
      "country": "سوريا",
      "notes": null,
      "status": "active",
      "created_by": 1,
      "updated_by": null
    }
  ]
}
```

### `POST /suppliers` — إضافة مورد

**Body:**
```json
{
  "name": "شركة التقنية",
  "company_name": "التقنية المحدودة",
  "phone": "0991234567",
  "email": "info@tech.com",
  "address": "دمشق - المزة",
  "city": "دمشق",
  "country": "سوريا",
  "notes": "",
  "status": "active"
}
```
> `name`, `phone`, `status` إجبارية. `phone` و `email` يجب أن يكونا unique.

**Response `201`:** `{ "status": "success", "message": "تم إضافة المورد بنجاح", "data": { ...المورد... } }`

### `GET /suppliers/{id}` — تفاصيل مورد
**Response `200`:** `{ "status": "success", "data": { ...المورد... } }`

### `PUT /suppliers/{id}` — تعديل مورد
نفس حقول الإضافة (كلها اختيارية). **Response `200`:** `{ "status": "success", "message": "تم تحديث بيانات المورد بنجاح", "data": {...} }`

### `DELETE /suppliers/{id}` — حذف مورد (**Admin فقط**)
**Response `200`:** `{ "status": "success", "message": "تم حذف المورد بنجاح" }`

---

## 🧑‍💼 7. العملاء (Customers) — Storekeeper | Admin

نفس شكل الموردين تماماً (نفس الحقول والردود) مع استبدال المسار بـ `/customers`:

| Method | المسار | الوظيفة |
|---|---|---|
| `GET` | `/customers` | كل العملاء (بدون pagination) |
| `POST` | `/customers` | إضافة عميل — `name`, `phone`, `status` إجبارية |
| `GET` | `/customers/{id}` | تفاصيل عميل |
| `PUT` | `/customers/{id}` | تعديل عميل |
| `DELETE` | `/customers/{id}` | حذف (**Admin فقط**) |

رسائل النجاح: `"تم إضافة العميل بنجاح"` / `"تم تحديث بيانات العميل بنجاح"` / `"تم حذف العميل بنجاح"`.

---

## 🧾 8. فواتير البيع (Sales Orders) — Storekeeper | Admin

### دورة حياة الفاتورة (مهم للفرونت!)

```
إنشاء ──► pending ──► POST /confirm ──► confirmed  (المخزون نقص + دين على العميل)
                └────► POST /cancel  ──► cancelled (لا شيء تحرك)
```
> الفاتورة `confirmed` **لا يمكن** إلغاؤها. فقط `pending` يمكن تأكيدها أو إلغاؤها.

### `GET /sales-orders` — قائمة الفواتير (paginated)

**Query Parameters (اختيارية):** `customer_id`, `status` (`pending`/`confirmed`/`cancelled`), `from_date`, `to_date`

**Response `200`:**
```json
{
  "message": "Sales orders retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 7,
        "customer_id": 1,
        "invoice_number": "SO-20260712-0007",
        "sale_date": "2026-07-12",
        "total": "450.00",
        "discount": "10.00",
        "tax": "0.00",
        "grand_total": "440.00",
        "status": "confirmed",
        "notes": null,
        "created_by": 2,
        "updated_by": 2,
        "created_at": "2026-07-12T11:00:00.000000Z",
        "customer": { "id": 1, "name": "عميل تجريبي", "phone": "0981111111" }
      }
    ],
    "per_page": 15,
    "total": 20
  }
}
```

### `POST /sales-orders` — إنشاء فاتورة بيع (تنشأ بحالة `pending`)

**Body:**
```json
{
  "customer_id": 1,
  "sale_date": "2026-07-12",
  "discount": 10,
  "tax": 0,
  "notes": "ملاحظات اختيارية",
  "items": [
    { "product_id": 1, "quantity": 3, "unit_price": 150 },
    { "product_id": 2, "quantity": 1, "unit_price": 80 }
  ]
}
```

| الحقل | ملاحظات |
|---|---|
| `items` | إجباري — سطر واحد على الأقل |
| `discount`, `tax` | اختيارية، افتراضياً 0 |
| الحسابات | `total` = مجموع الأسطر، `grand_total` = total − discount + tax — **يحسبها السيرفر، لا ترسلها** |
| `invoice_number` | **يولَّد تلقائياً** بصيغة `SO-YYYYMMDD-0001` |

**Response `201`:**
```json
{
  "message": "Sales order created successfully",
  "data": {
    "id": 8,
    "invoice_number": "SO-20260712-0008",
    "status": "pending",
    "total": "530.00",
    "discount": "10.00",
    "tax": "0.00",
    "grand_total": "520.00",
    "items": [
      {
        "id": 12,
        "sales_order_id": 8,
        "product_id": 1,
        "quantity": 3,
        "unit_price": "150.00",
        "subtotal": "450.00",
        "product": { "id": 1, "name": "لابتوب ديل", "sku": "DELL-5520" }
      }
    ]
  }
}
```

### `GET /sales-orders/{id}` — تفاصيل الفاتورة
**Response `200`:** الفاتورة كاملة مع `customer` (id, name, phone, email) و `items.product` و `documents` (المستندات المرفقة) و `creator`.

### `POST /sales-orders/{id}/confirm` — تأكيد الفاتورة
يصرف الكميات من المخزون + يسجل الدين على العميل.

**Response `200`:** `{ "message": "Sales order confirmed successfully", "data": { ...status: "confirmed"... } }`

**Response `422`** إذا الكمية غير كافية (لا يتحرك شيء):
```json
{
  "message": "Insufficient stock for product (لابتوب ديل). Available: 2, required: 3",
  "errors": { "items": ["Insufficient stock for product (لابتوب ديل). Available: 2, required: 3"] }
}
```

**Response `422`** إذا الفاتورة ليست `pending`:
```json
{
  "message": "Only pending orders can be confirmed. Current status: confirmed",
  "errors": { "status": ["Only pending orders can be confirmed. Current status: confirmed"] }
}
```

### `POST /sales-orders/{id}/cancel` — إلغاء فاتورة معلقة
**Response `200`:** `{ "message": "Sales order cancelled successfully", "data": {...} }`
**Response `422`** إذا لم تكن `pending` (نفس شكل خطأ confirm).

### `DELETE /sales-orders/{id}` — حذف نهائي (**Admin فقط**)
**Response `200`:** `{ "message": "Sales order deleted successfully" }`

---

## 📥 9. فواتير الشراء (Purchase Orders) — Storekeeper | Admin

**نفس شكل فواتير البيع تماماً** مع الفروقات التالية:

| | فواتير البيع | فواتير الشراء |
|---|---|---|
| المسار | `/sales-orders` | `/purchase-orders` |
| الطرف | `customer_id` | `supplier_id` |
| التاريخ | `sale_date` | `purchase_date` |
| فلتر القائمة | `customer_id` | `supplier_id` |
| رقم الفاتورة | `SO-...` | `PO-...` |
| التأكيد | يصرف من المخزون + دين **على العميل** | **يُدخل** للمخزون + دين **للمورد** |

**المسارات:** `GET /purchase-orders` • `POST /purchase-orders` • `GET /purchase-orders/{id}` • `POST /purchase-orders/{id}/confirm` • `POST /purchase-orders/{id}/cancel` • `DELETE /purchase-orders/{id}` (Admin)

**مثال Body للإنشاء:**
```json
{
  "supplier_id": 1,
  "purchase_date": "2026-07-12",
  "discount": 0,
  "tax": 5,
  "items": [
    { "product_id": 1, "quantity": 20, "unit_price": 100 }
  ]
}
```

---

## 📎 10. المستندات (Documents) — Storekeeper | Admin

### `POST /documents` — رفع مستند مربوط بفاتورة

> ⚠️ هذا الراوت الوحيد الذي يستخدم **`multipart/form-data`** (وليس JSON).

**Form Data:**

| الحقل | القيمة |
|---|---|
| `file` | الملف نفسه — `pdf`, `jpg`, `jpeg`, `png` — حد أقصى 5MB |
| `documentable_type` | `sales_order` أو `purchase_order` |
| `documentable_id` | رقم الفاتورة (id) |

**Response `201`:**
```json
{
  "message": "Document uploaded successfully",
  "data": {
    "id": 3,
    "file_path": "documents/AbC123xyz.pdf",
    "file_name": "فاتورة-الشحن.pdf",
    "documentable_type": "App\\Models\\SalesOrder",
    "documentable_id": 8,
    "created_by": 2,
    "created_at": "2026-07-12T12:00:00.000000Z"
  }
}
```

### `GET /documents/{id}/download` — تحميل المستند
**Response `200`:** الملف نفسه (binary download) — استخدمه كرابط مباشر أو `window.open`.
**Response `404`:** `{ "message": "File not found on disk" }` إذا الملف محذوف من التخزين.

### `DELETE /documents/{id}` — حذف مستند (**Admin فقط**)
يحذف السجل + الملف من التخزين. **Response `200`:** `{ "message": "Document deleted successfully" }`

---

## 💰 11. المالية (Finance) — Accountant | Admin

### مفهوم الرصيد (مهم!)

- **رصيد العميل** = كم بقي **لنا عنده**. فاتورة البيع المؤكدة تزيده، سند القبض ينقصه.
- **رصيد المورد** = كم بقي **علينا له**. فاتورة الشراء المؤكدة تزيده، سند الصرف ينقصه.
- كل حركة تحمل `balance_after` = الرصيد بعدها مباشرة.

### أنواع الحركات (`type`)

| النوع | تسجَّل | المعنى |
|---|---|---|
| `sale` | **تلقائياً** عند تأكيد فاتورة بيع | دين على العميل |
| `purchase` | **تلقائياً** عند تأكيد فاتورة شراء | دين علينا للمورد |
| `receipt` | يدوياً من المحاسب | سند قبض من عميل |
| `payment` | يدوياً من المحاسب | سند صرف لمورد |

### `GET /finance/transactions` — سجل الحركات المالية (paginated)

**Query Parameters (اختيارية):** `type` (`sale`/`receipt`/`purchase`/`payment`), `from_date`, `to_date`

**Response `200`:**
```json
{
  "message": "Financial transactions retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 4,
        "financialable_type": "App\\Models\\Customer",
        "financialable_id": 1,
        "type": "receipt",
        "amount": "200.00",
        "balance_after": "240.00",
        "reference_number": "REC-1",
        "notes": null,
        "created_by": 3,
        "created_at": "2026-07-12T13:00:00.000000Z",
        "financialable": { "id": 1, "name": "عميل تجريبي" },
        "creator": { "id": 3, "first_name": "محاسب", "last_name": "النظام" }
      }
    ],
    "per_page": 15,
    "total": 8
  }
}
```
> 💡 لمعرفة إذا الحركة على عميل أو مورد افحص `financialable_type`: تحتوي `Customer` أو `Supplier`.

### `POST /finance/transactions` — تسجيل سند قبض أو صرف

**Body (سند قبض من عميل):**
```json
{
  "party_type": "customer",
  "party_id": 1,
  "type": "receipt",
  "amount": 200,
  "reference_number": "REC-2026-001",
  "notes": "دفعة نقدية"
}
```

**Body (سند صرف لمورد):**
```json
{
  "party_type": "supplier",
  "party_id": 1,
  "type": "payment",
  "amount": 500,
  "reference_number": "PAY-2026-001",
  "notes": ""
}
```

> ⚠️ **قاعدة:** `customer` يقبل فقط `receipt`، و `supplier` يقبل فقط `payment` — غير ذلك يرجع `422`:
> `{ "message": "Only receipt vouchers are allowed for customers" }`

**Response `201`:** `{ "message": "Financial transaction recorded successfully", "data": { ...الحركة مع balance_after... } }`

### `GET /finance/statement/customer/{id}` — كشف حساب عميل
### `GET /finance/statement/supplier/{id}` — كشف حساب مورد

**Response `200`:**
```json
{
  "message": "Statement retrieved successfully",
  "data": {
    "party": {
      "id": 1,
      "name": "عميل تجريبي",
      "phone": "0981111111",
      "email": null
    },
    "current_balance": "240.00",
    "transactions": [
      {
        "id": 2,
        "type": "sale",
        "amount": "440.00",
        "balance_after": "440.00",
        "reference_number": "SO-20260712-0007",
        "created_at": "2026-07-12T11:00:00.000000Z",
        "creator": { "id": 2, "first_name": "أحمد", "last_name": "محمد" }
      },
      {
        "id": 4,
        "type": "receipt",
        "amount": "200.00",
        "balance_after": "240.00",
        "reference_number": "REC-1",
        "created_at": "2026-07-12T13:00:00.000000Z",
        "creator": { "id": 3, "first_name": "محاسب", "last_name": "النظام" }
      }
    ]
  }
}
```
> الحركات مرتبة **من الأقدم للأحدث** (مناسبة للعرض كجدول كشف حساب). `current_balance` هو رصيد آخر حركة.

---

## 📊 12. لوحة القيادة والتقارير — Warehouse Manager | Admin

### `GET /dashboard` — لوحة القيادة

**Response `200`:**
```json
{
  "message": "Dashboard retrieved successfully",
  "data": {
    "today": {
      "stock_in": 20,
      "stock_out": 8,
      "sales": 640,
      "purchases": 1000
    },
    "pending": {
      "sales_orders": 2,
      "purchase_orders": 1
    },
    "low_stock_products": 3,
    "totals": {
      "products": 30,
      "categories": 4,
      "customers": 12,
      "suppliers": 5
    },
    "charts": {
      "stock_movements_last_7_days": [
        { "date": "2026-07-12", "in": 20, "out": 8 }
      ],
      "sales_vs_purchases_last_6_months": [
        { "month": "2026-02", "sales": 0, "purchases": 0 },
        { "month": "2026-03", "sales": 1200, "purchases": 900 },
        { "month": "2026-04", "sales": 3400, "purchases": 2100 },
        { "month": "2026-05", "sales": 2800, "purchases": 1500 },
        { "month": "2026-06", "sales": 4100, "purchases": 3000 },
        { "month": "2026-07", "sales": 640, "purchases": 1000 }
      ]
    }
  }
}
```
> 💡 `charts` جاهزة للرسم مباشرة (Chart.js / Recharts / ApexCharts). مصفوفة الأشهر دائماً 6 عناصر حتى لو القيم صفر. مصفوفة الأيام تحتوي فقط الأيام التي فيها حركة.

### `GET /reports/inventory` — تقرير جرد المخزون

**Query Parameters (اختيارية):** `category_id`, `status`

**Response `200`:**
```json
{
  "message": "Inventory report retrieved successfully",
  "data": {
    "total_products": 30,
    "total_quantity": 540,
    "low_stock_count": 3,
    "products": [
      {
        "id": 1,
        "category_id": 1,
        "name": "لابتوب ديل",
        "sku": "DELL-5520",
        "quantity": 25,
        "minimum_quantity": 5,
        "status": "active",
        "is_low_stock": false,
        "category": { "id": 1, "name": "إلكترونيات" }
      }
    ]
  }
}
```

### `GET /reports/low-stock` — المنتجات تحت الحد الأدنى

**Response `200`:** مصفوفة منتجات (مرتبة من الأقل كمية) بنفس شكل منتج الجرد بدون `is_low_stock`.

### `GET /reports/stagnant-products` — الأصناف الراكدة

**Query Parameters:** `days` (افتراضياً 30) — منتجات موجودة بالمخزون ولم يخرج منها شيء منذ X يوم.

**Response `200`:**
```json
{
  "message": "Stagnant products report retrieved successfully",
  "data": {
    "days": 30,
    "count": 2,
    "products": [
      {
        "id": 5,
        "name": "منتج راكد",
        "sku": "S-12345",
        "quantity": 50,
        "category": { "id": 2, "name": "قرطاسية" }
      }
    ]
  }
}
```

### `GET /reports/stock-movements-summary` — ملخص الحركة لكل منتج

**Query Parameters (اختيارية):** `from_date`, `to_date`

**Response `200`:**
```json
{
  "message": "Stock movements summary retrieved successfully",
  "data": [
    {
      "product_id": 1,
      "total_in": "20",
      "total_out": "8",
      "total_adjustment": "0",
      "movements_count": 2,
      "product": { "id": 1, "name": "لابتوب ديل", "sku": "DELL-5520", "quantity": 12 }
    }
  ]
}
```

### `GET /reports/suppliers-performance` — أداء الموردين

**Response `200`:** (مرتبة حسب إجمالي التعامل تنازلياً)
```json
{
  "message": "Suppliers performance report retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "شركة التقنية",
      "company_name": "التقنية المحدودة",
      "phone": "0991234567",
      "status": "active",
      "confirmed_orders_count": 5,
      "total_purchases": "12500.00",
      "last_purchase_date": "2026-07-11"
    }
  ]
}
```

### `GET /reports/top-selling-products` — الأكثر مبيعاً

**Query Parameters (اختيارية):** `from_date`, `to_date`, `limit` (افتراضياً 10)

**Response `200`:**
```json
{
  "message": "Top selling products report retrieved successfully",
  "data": [
    {
      "product_id": 1,
      "total_sold": "45",
      "total_revenue": "6750.00",
      "product": { "id": 1, "name": "لابتوب ديل", "sku": "DELL-5520", "quantity": 12 }
    }
  ]
}
```

---

## 🗺️ ملخص سريع — كل الراوتات

| # | Method | المسار | الدور | الوظيفة |
|---|---|---|---|---|
| 1 | POST | `/login` | عام | تسجيل دخول |
| 2 | POST | `/logout` | أي مستخدم | تسجيل خروج |
| 3 | POST | `/add/staff/admin` | Admin | إضافة موظف |
| 4 | GET | `/categories` | Storekeeper+ | قائمة الأصناف |
| 5 | POST | `/categories` | Storekeeper+ | إضافة صنف |
| 6 | GET | `/categories/{id}` | Storekeeper+ | تفاصيل صنف |
| 7 | PUT | `/categories/{id}` | Storekeeper+ | تعديل صنف |
| 8 | DELETE | `/categories/{id}` | Admin | حذف صنف |
| 9 | GET | `/products` | Storekeeper+ | قائمة المنتجات |
| 10 | POST | `/products` | Storekeeper+ | إضافة منتج |
| 11 | GET | `/products/{id}` | Storekeeper+ | تفاصيل منتج |
| 12 | PUT | `/products/{id}` | Storekeeper+ | تعديل منتج |
| 13 | DELETE | `/products/{id}` | Admin | حذف منتج |
| 14 | GET | `/stock/history` | Storekeeper+ | سجل الحركات |
| 15 | POST | `/stock/move-in` | Storekeeper+ | إدخال يدوي |
| 16 | POST | `/stock/move-out` | Storekeeper+ | إخراج يدوي |
| 17 | GET | `/suppliers` | Storekeeper+ | قائمة الموردين |
| 18 | POST | `/suppliers` | Storekeeper+ | إضافة مورد |
| 19 | GET | `/suppliers/{id}` | Storekeeper+ | تفاصيل مورد |
| 20 | PUT | `/suppliers/{id}` | Storekeeper+ | تعديل مورد |
| 21 | DELETE | `/suppliers/{id}` | Admin | حذف مورد |
| 22 | GET | `/customers` | Storekeeper+ | قائمة العملاء |
| 23 | POST | `/customers` | Storekeeper+ | إضافة عميل |
| 24 | GET | `/customers/{id}` | Storekeeper+ | تفاصيل عميل |
| 25 | PUT | `/customers/{id}` | Storekeeper+ | تعديل عميل |
| 26 | DELETE | `/customers/{id}` | Admin | حذف عميل |
| 27 | GET | `/sales-orders` | Storekeeper+ | قائمة فواتير البيع |
| 28 | POST | `/sales-orders` | Storekeeper+ | إنشاء فاتورة بيع |
| 29 | GET | `/sales-orders/{id}` | Storekeeper+ | تفاصيل فاتورة بيع |
| 30 | POST | `/sales-orders/{id}/confirm` | Storekeeper+ | تأكيد فاتورة بيع |
| 31 | POST | `/sales-orders/{id}/cancel` | Storekeeper+ | إلغاء فاتورة بيع |
| 32 | DELETE | `/sales-orders/{id}` | Admin | حذف فاتورة بيع |
| 33 | GET | `/purchase-orders` | Storekeeper+ | قائمة فواتير الشراء |
| 34 | POST | `/purchase-orders` | Storekeeper+ | إنشاء فاتورة شراء |
| 35 | GET | `/purchase-orders/{id}` | Storekeeper+ | تفاصيل فاتورة شراء |
| 36 | POST | `/purchase-orders/{id}/confirm` | Storekeeper+ | تأكيد استلام |
| 37 | POST | `/purchase-orders/{id}/cancel` | Storekeeper+ | إلغاء فاتورة شراء |
| 38 | DELETE | `/purchase-orders/{id}` | Admin | حذف فاتورة شراء |
| 39 | POST | `/documents` | Storekeeper+ | رفع مستند (multipart) |
| 40 | GET | `/documents/{id}/download` | Storekeeper+ | تحميل مستند |
| 41 | DELETE | `/documents/{id}` | Admin | حذف مستند |
| 42 | GET | `/finance/transactions` | Accountant+ | سجل الحركات المالية |
| 43 | POST | `/finance/transactions` | Accountant+ | سند قبض/صرف |
| 44 | GET | `/finance/statement/customer/{id}` | Accountant+ | كشف حساب عميل |
| 45 | GET | `/finance/statement/supplier/{id}` | Accountant+ | كشف حساب مورد |
| 46 | GET | `/dashboard` | Manager+ | لوحة القيادة |
| 47 | GET | `/reports/inventory` | Manager+ | جرد المخزون |
| 48 | GET | `/reports/low-stock` | Manager+ | تحت الحد الأدنى |
| 49 | GET | `/reports/stagnant-products` | Manager+ | الأصناف الراكدة |
| 50 | GET | `/reports/stock-movements-summary` | Manager+ | ملخص الحركة |
| 51 | GET | `/reports/suppliers-performance` | Manager+ | أداء الموردين |
| 52 | GET | `/reports/top-selling-products` | Manager+ | الأكثر مبيعاً |

> **Storekeeper+** = Storekeeper أو Admin • **Accountant+** = Accountant أو Admin • **Manager+** = Warehouse Manager أو Admin

---

*آخر تحديث: 2026-07-12*
