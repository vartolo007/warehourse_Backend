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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('invoice_number')->unique();      // رقم فاتورة الشراء (يولد تلقائياً)
            $table->date('purchase_date');
            $table->decimal('total', 10, 2)->default(0);     // مجموع الأسطر قبل الحسم والضريبة
            $table->decimal('discount', 10, 2)->default(0);  // الحسم
            $table->decimal('tax', 10, 2)->default(0);       // الضريبة
            $table->decimal('grand_total', 10, 2)->default(0); // الإجمالي النهائي
            // pending: بانتظار الاستلام، confirmed: تم إدخال البضاعة للمخزون، cancelled: ملغاة
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            // لتوثيق من الموظف الذي أنشأ أو عدل الفاتورة
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
        Schema::dropIfExists('purchase_orders');
    }
};
