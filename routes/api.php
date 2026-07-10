<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
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
});
