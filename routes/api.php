<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات المصادقة (Auth)
|-------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // تقييد محاولات تسجيل الدخول لمنع الهجمات
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| مسارات الأدمن فقط
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {

    // إضافة موظف جديد وإعطاؤه دور
    Route::post('/add/staff/admin', [StaffController::class, 'storeStaff']);

    // الحذف النهائي للسجلات صلاحية حصرية للأدمن
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']); // حذف صنف
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);    // حذف منتج
    //bashar
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

    Route::delete('/sales-orders/{id}', [SalesOrderController::class, 'destroy']);       // حذف فاتورة بيع
    Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy']); // حذف فاتورة شراء
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);            // حذف مستند
});

/*
|--------------------------------------------------------------------------
| مسارات أمين المستودع (Storekeeper) - يشاركه الأدمن بكل الصلاحيات
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:Storekeeper|Admin'])->group(function () {

    // ------ إدارة الأصناف (Categories) ------
    Route::get('/categories', [CategoryController::class, 'index']);        // عرض كل الأصناف مع عدد منتجاتها
    Route::post('/categories', [CategoryController::class, 'store']);       // إضافة صنف جديد
    Route::get('/categories/{id}', [CategoryController::class, 'show']);    // تفاصيل صنف واحد مع منتجاته
    Route::put('/categories/{id}', [CategoryController::class, 'update']);  // تعديل بيانات صنف

    // ------ إدارة المنتجات (Products) ------
    Route::get('/products', [ProductController::class, 'index']);           // عرض كل المنتجات (مع بحث وفلاتر)
    Route::post('/products', [ProductController::class, 'store']);          // إضافة منتج جديد (بكمية افتتاحية)
    Route::get('/products/{id}', [ProductController::class, 'show']);       // تفاصيل منتج + آخر 10 حركات + تنبيه الحد الأدنى
    Route::put('/products/{id}', [ProductController::class, 'update']);     // تعديل بيانات المنتج (بدون الكمية)

    // ------ حركات المخزون (Stock Movements) ------
    Route::get('/stock/history', [StockMovementController::class, 'index']);    // سجل الحركات (وارد، صادر، جرد) مع فلاتر
    Route::post('/stock/move-in', [StockMovementController::class, 'storeIn']); // إدخال بضاعة للمستودع (وارد)
    Route::post('/stock/move-out', [StockMovementController::class, 'storeOut']); // إخراج بضاعة (صادر أو تالف/جرد)
    //bashar
    // ------ إدارة الموردين (Suppliers) ------
    Route::get('/suppliers', [SupplierController::class, 'index']);          // عرض مع بحث وفلترة
    Route::post('/suppliers', [SupplierController::class, 'store']);         // إضافة مورد جديد
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);       // تفاصيل المورد
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);      // تعديل بيانات المورد

    // ------ إدارة العملاء (Customers) ------
    Route::get('/customers', [CustomerController::class, 'index']);          // عرض مع بحث وفلترة
    Route::post('/customers', [CustomerController::class, 'store']);         // إضافة عميل جديد
    Route::get('/customers/{id}', [CustomerController::class, 'show']);       // تفاصيل العميل
    Route::put('/customers/{id}', [CustomerController::class, 'update']);     // تعديل بيانات العميل

    // ------ فواتير البيع (Sales Orders) ------
    Route::get('/sales-orders', [SalesOrderController::class, 'index']);            // عرض مع فلاتر (العميل، الحالة، التاريخ)
    Route::post('/sales-orders', [SalesOrderController::class, 'store']);           // إنشاء فاتورة بيع (pending)
    Route::get('/sales-orders/{id}', [SalesOrderController::class, 'show']);        // تفاصيل الفاتورة مع أسطرها ومستنداتها
    Route::post('/sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']); // تأكيد: صرف من المخزون + دين على العميل
    Route::post('/sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);   // إلغاء فاتورة معلقة

    // ------ فواتير الشراء (Purchase Orders) ------
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);            // عرض مع فلاتر (المورد، الحالة، التاريخ)
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);           // إنشاء فاتورة شراء (pending)
    Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show']);        // تفاصيل الفاتورة مع أسطرها ومستنداتها
    Route::post('/purchase-orders/{id}/confirm', [PurchaseOrderController::class, 'confirm']); // تأكيد الاستلام: إدخال للمخزون + دين للمورد
    Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);   // إلغاء فاتورة معلقة

    // ------ المستندات (Documents) ------
    Route::post('/documents', [DocumentController::class, 'store']);             // رفع مستند مربوط بفاتورة بيع أو شراء
    Route::get('/documents/{id}/download', [DocumentController::class, 'download']); // تحميل المستند
});

/*
|--------------------------------------------------------------------------
| مسارات المحاسب (Accountant) - يشاركه الأدمن بكل الصلاحيات
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:Accountant|Admin'])->group(function () {

    // ------ الحركات المالية (Financial Transactions) ------
    Route::get('/finance/transactions', [FinancialTransactionController::class, 'index']);  // سجل الحركات المالية مع فلاتر
    Route::post('/finance/transactions', [FinancialTransactionController::class, 'store']); // تسجيل سند قبض (عميل) أو سند صرف (مورد)

    // ------ كشوف الحسابات (Statements) ------
    Route::get('/finance/statement/customer/{id}', [FinancialTransactionController::class, 'customerStatement']); // كم لنا عند العميل؟
    Route::get('/finance/statement/supplier/{id}', [FinancialTransactionController::class, 'supplierStatement']); // كم علينا للمورد؟
});

/*
|--------------------------------------------------------------------------
| مسارات مدير المستودع (Warehouse Manager) - يشاركه الأدمن بكل الصلاحيات
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:Warehouse Manager|Admin'])->group(function () {

    // ------ لوحة القيادة (Dashboard) ------
    Route::get('/dashboard', [ReportController::class, 'dashboard']); // إحصائيات اليوم + بيانات الرسوم البيانية

    // ------ التقارير المتقدمة (Reports) ------
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);                        // جرد المخزون
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock']);                         // المنتجات تحت الحد الأدنى
    Route::get('/reports/stagnant-products', [ReportController::class, 'stagnantProducts']);         // الأصناف الراكدة (?days=30)
    Route::get('/reports/stock-movements-summary', [ReportController::class, 'stockMovementsSummary']); // ملخص الوارد والصادر لكل منتج
    Route::get('/reports/suppliers-performance', [ReportController::class, 'suppliersPerformance']); // أداء الموردين
    Route::get('/reports/top-selling-products', [ReportController::class, 'topSellingProducts']);    // الأكثر مبيعاً
});
