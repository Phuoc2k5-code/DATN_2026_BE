<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng ai_cv_analyses (Lưu thông tin tổng quan của CV do AI đọc)
        Schema::create('ai_cv_analyses', function (Blueprint $table) {
            $table->id();
            // Khóa ngoại nối duy nhất với bảng cv_files, nếu file bị xóa thì data AI tự mất theo
            $table->foreignId('cv_file_id')->constrained('cv_files')->onDelete('cascade');
            
            $table->string('cv_title')->nullable();         // Ví dụ: Nodejs Web Developer
            $table->string('job_category')->nullable();     // Ví dụ: IT Phần mềm
            $table->string('experience_year')->nullable();
            $table->timestamps();
        });

        // 2. Tạo bảng ai_cv_skills (Tách riêng từng skill ra để sau này viết câu lệnh Query lọc Job siêu nhanh)
        Schema::create('ai_cv_skills', function (Blueprint $table) {
            $table->id();
            // Khóa ngoại nối với bảng phân tích tổng quan ở trên
            $table->foreignId('ai_analysis_id')->constrained('ai_cv_analyses')->onDelete('cascade');
            
            $table->string('skill_name'); // Ví dụ dòng 1: Laravel, dòng 2: ReactJS...
            $table->timestamps();

            // 🚀 QUAN TRỌNG: Tạo Index cho cột này để tăng tốc độ tìm kiếm/lọc việc làm gấp 10 lần
            $table->index('skill_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_cv_skills');
        Schema::dropIfExists('ai_cv_analyses');
    }
};