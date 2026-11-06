<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('renew_count'); // ส่งอีเมลเตือนก่อนครบกำหนดแล้ว
            $table->boolean('auto_returned')->default(false)->after('reminder_sent_at'); // ระบบตัดคืนอัตโนมัติเมื่อครบกำหนด
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'auto_returned']);
        });
    }
};
