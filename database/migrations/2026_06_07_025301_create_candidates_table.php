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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cv_template_id')->nullable()->constrained('cv_templates')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('summary')->nullable();
            $table->text('objective')->nullable();
            $table->json('links')->nullable(); // Lưu trữ mảng links (GitHub, LinkedIn) dạng JSON
            $table->integer('experience_years')->default(0);
            $table->text('project')->nullable();
            $table->text('education')->nullable();
            $table->text('contact_reference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
