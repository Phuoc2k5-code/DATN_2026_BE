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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');            $table->string('reason_type'); // Lừa đảo, từ ngữ xúc phạm, sai ngành nghề...
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, resolved, rejected
            $table->text('admin_note')->nullable(); // Ghi chú của Admin khi xử lý bài đăng tuyển dụng
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
