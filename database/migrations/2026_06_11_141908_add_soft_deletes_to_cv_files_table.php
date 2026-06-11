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
        Schema::table('cv_files', function (Blueprint $table) {
            // 🚀 Thêm dòng này để tự động sinh cột 'deleted_at' chuẩn SoftDeletes
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_files', function (Blueprint $table) {
            // 🚀 Hàm drop cột phòng trường hợp bro muốn rollback migration
            $table->dropSoftDeletes();
        });
    }
};