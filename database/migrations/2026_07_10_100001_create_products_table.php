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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->unique();               // الرمز التعريفي للمنتج
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);       // الكمية الحالية في المستودع
            $table->integer('minimum_quantity')->default(5); // الحد الأدنى للتنبيه
            $table->string('image')->nullable();
            $table->string('status')->default('active');   // active أو inactive
            // لتوثيق من الموظف الذي أضاف أو عدل المنتج
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
        Schema::dropIfExists('products');
    }
};
