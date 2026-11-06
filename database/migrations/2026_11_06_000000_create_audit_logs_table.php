<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();        // สำเนาชื่อ เผื่อบัญชีถูกลบภายหลัง
            $table->string('action', 30);                   // created, updated, deleted, login, logout, archived
            $table->string('subject_type', 50)->nullable(); // Ebook, Category, BorrowPolicy, User, Borrow
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();    // ชื่อรายการ ณ เวลาที่บันทึก
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
