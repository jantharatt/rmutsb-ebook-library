<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', [
                'student',
                'teacher',
                'staff',
                'alumni',
                'admin'
            ])->default('student')->after('email');
            }

            if (!Schema::hasColumn('users', 'faculty')) {
                $table->string('faculty')
                    ->nullable()
                    ->after('role');
            }

            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')
                    ->nullable()
                    ->after('faculty');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->boolean('status')
                    ->default(true)
                    ->after('department');
            }
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
