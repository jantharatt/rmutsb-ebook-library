<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::table('ebooks', function (Blueprint $table) {

            if (!Schema::hasColumn('ebooks', 'source')) {

                $table->string('source')
                      ->nullable()
                      ->after('cataloguer');

            }

        });

    }


    public function down(): void
    {

        if (Schema::hasColumn('ebooks', 'source')) {

            Schema::table('ebooks', function (Blueprint $table) {

                $table->dropColumn('source');

            });

        }

    }

};