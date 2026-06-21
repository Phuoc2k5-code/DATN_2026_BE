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
        Schema::table('jobs', function (Blueprint $col) {
            // Thêm trường reject_reason kiểu text, cho phép null, đặt sau trường status
            $col->text('reject_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $col) {
            // Xóa cột nếu rollback migration
            $col->dropColumn('reject_reason');
        });
    }
};