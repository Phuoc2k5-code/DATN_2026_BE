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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('logo_url');
            $table->string('tax_code')->nullable();
            $table->string('business_license')->nullable();
            $table->string('website_url')->nullable();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();     // Lĩnh vực hoạt động (Ví dụ: Công nghệ thông tin)
            $table->string('size')->nullable();         // Quy mô công ty (Ví dụ: 100-500 nhân viên)
            $table->integer('founded_year')->nullable(); // Năm thành lập (Ví dụ: 2018)
            $table->string('address')->nullable();      // Địa chỉ trụ sở chính
            $table->text('benefits')->nullable();
            $table->boolean('is_verified')->default(false); // Dành cho Admin duyệt bài
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
