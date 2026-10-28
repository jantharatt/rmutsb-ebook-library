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
        Schema::create('borrow_policies', function (Blueprint $table) {

            $table->id();

            $table->enum('role',[
                'student',
                'teacher',
                'staff',
                'alumni'
            ])->unique();

            $table->integer('max_books');

            $table->integer('borrow_days');

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_policies');
    }
};
