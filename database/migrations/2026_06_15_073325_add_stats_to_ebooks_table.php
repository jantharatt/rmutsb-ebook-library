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
        Schema::table('ebooks', function (Blueprint $table) {

            if (!Schema::hasColumn('ebooks', 'view_count')) {
                $table->integer('view_count')->default(0);
            }

            if (!Schema::hasColumn('ebooks', 'borrow_count')) {
                $table->integer('borrow_count')->default(0);
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebooks', function (Blueprint $table) {
            //
        });
    }
};
