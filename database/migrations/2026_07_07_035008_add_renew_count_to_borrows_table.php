<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('borrows', function (Blueprint $table) {

            $table->integer('renew_count')
                ->default(0)
                ->after('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            //
        });
    }
};
