<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebooks', function (Blueprint $table) {
            if (!Schema::hasColumn('ebooks', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }

            if (!Schema::hasColumn('ebooks', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }

            // รองรับหลายรายการ (คั่นด้วยขึ้นบรรทัดใหม่)
            $table->text('added_personal_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ebooks', function (Blueprint $table) {
            $table->dropColumn(['subtitle', 'price']);
        });
    }
};
