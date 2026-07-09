<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // اسم الشركة أو المورد الأساسي
            $table->string('company_name')->nullable(); // اسم الشركة المسؤول عنها
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique();   // رقم الهاتف للتواصل
            $table->text('address')->nullable(); // العنوان
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();   // ملاحظات إضافية
            $table->string('status')->default('active'); // active أو inactive كما في الـ UI
            // لتوثيق من الموظف الذي أضاف أو عدل المورد
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
