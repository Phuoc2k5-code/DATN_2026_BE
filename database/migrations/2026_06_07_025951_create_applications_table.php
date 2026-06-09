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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('cv_type'); // 'online' (dùng mẫu cv hệ thống) hoặc 'uploaded' (file pdf tải lên)
            $table->foreignId('cv_file_id')->nullable()->constrained('cv_files')->onDelete('set null');
            $table->text('description')->nullable();
            $table->decimal('matching_score', 5, 2)->default(0.00); // Ví dụ: 85.50 %
            $table->string('status')->default('pending'); // pending, reviewed, accepted, rejected
            $table->json('status_details')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
