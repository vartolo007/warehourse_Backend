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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            // علاقة Polymorphic: الحركة المالية إما على عميل (Customer) أو مورد (Supplier)
            $table->morphs('financialable'); // financialable_id + financialable_type
            // sale: فاتورة بيع (دين على العميل)، receipt: سند قبض من العميل
            // purchase: فاتورة شراء (دين علينا للمورد)، payment: سند صرف للمورد
            $table->enum('type', ['sale', 'receipt', 'purchase', 'payment']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);       // رصيد الطرف بعد الحركة (لكشف الحساب)
            $table->string('reference_number')->nullable(); // رقم الفاتورة أو السند المرتبط
            $table->text('notes')->nullable();
            // المحاسب أو الموظف الذي سجل الحركة
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
