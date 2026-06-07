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
        Schema::create('job_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->date('click_date');
            $table->integer('click_count')->default(1);
            $table->timestamps();
            
            $table->unique(['job_id', 'click_date']); // Đảm bảo mỗi ngày một tin tuyển dụng chỉ có 1 dòng ghi nhận, sau đó increment click_count lên
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_clicks');
    }
};
