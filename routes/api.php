<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1'); // تقييد محاولات تسجيل الدخول لمنع الهجمات
    Route::post('logout',[AuthController::class,'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum','role:Admin'])->group(function () {

Route::post('/add/staff/admin', [StaffController::class, 'storeStaff']);

});


Route::middleware(['auth:sanctum','role:Storekeeper'])->group(function () {


});
