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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');           // مسار الملف داخل storage
            $table->string('file_name');           // الاسم الأصلي للملف
            // علاقة Polymorphic: المستند مربوط بفاتورة بيع أو فاتورة شراء
            $table->morphs('documentable'); // documentable_id + documentable_type
            // الموظف الذي رفع المستند
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
