<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng queue_jobs thay vì bảng jobs để tránh đụng hàng
        Schema::create('queue_jobs', function (Blueprint $blueprint) {
            $blueprint->bigIncrements('id');
            $blueprint->string('queue')->index();
            $blueprint->longText('payload');
            $blueprint->unsignedTinyInteger('attempts');
            $blueprint->unsignedInteger('reserved_at')->nullable();
            $blueprint->unsignedInteger('available_at');
            $blueprint->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_jobs');
    }
};