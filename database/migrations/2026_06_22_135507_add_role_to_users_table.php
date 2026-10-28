<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->enum('role', [
                'student',
                'teacher',
                'staff',
                'alumni',
                'admin'
            ])->default('student')->after('email');

            $table->string('faculty')
                ->nullable()
                ->after('role');

            $table->string('department')
                ->nullable()
                ->after('faculty');

            $table->boolean('status')
                ->default(true)
                ->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'role',
                'faculty',
                'department',
                'status'
            ]);

        });
    }
};
