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
        Schema::create('ebooks', function (Blueprint $table) {

            $table->id();
            
            // ข้อมูลบรรณานุกรม
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->year('publish_year')->nullable();

            // หมวดหมู่
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // รายละเอียด
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();

            // ไฟล์
            $table->string('cover')->nullable();
            $table->string('file_path');

            // จำนวนหน้า
            $table->integer('total_pages')->default(0);

            // สถานะ
            $table->boolean('status')->default(true);
            $table->timestamps();

            // สถิติ
            $table->integer('view_count')->default(0);
            $table->integer('borrow_count')->default(0);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebooks');
    }
};
