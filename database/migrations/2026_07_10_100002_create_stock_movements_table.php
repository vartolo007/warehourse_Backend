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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment']); // 'in' وارد، 'out' صادر، 'adjustment' جرد/تالف
            $table->integer('quantity');                       // الكمية المتحركة
            $table->integer('balance_after');                  // الكمية في المستودع بعد الحركة (مهمة للتقارير)
            $table->string('reference_type')->nullable();      // مثلاً: PurchaseOrder أو SalesOrder
            $table->unsignedBigInteger('reference_id')->nullable(); // رقم الفاتورة أو الطلب المرتبط
            $table->text('notes')->nullable();
            // أمين المستودع الذي قام بالحركة
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
