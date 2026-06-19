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
        Schema::table('companies', function (Blueprint $col) {
            // Thêm trường reject_reason cho công ty
            $col->text('reject_reason')->nullable()->after('is_verified'); 
            // Lưu ý: nếu bảng của bạn dùng `is_verified` thì sửa thành ->after('is_verified') nhé
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $col) {
            $col->dropColumn('reject_reason');
        });
    }
};