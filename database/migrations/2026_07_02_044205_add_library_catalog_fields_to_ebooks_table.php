<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::table('ebooks', function (Blueprint $table) {


            if (!Schema::hasColumn('ebooks','co_author')) {
                $table->string('co_author')->nullable();
            }


            if (!Schema::hasColumn('ebooks','call_number')) {
                $table->string('call_number')->nullable();
            }


            if (!Schema::hasColumn('ebooks','call_number_copy')) {
                $table->string('call_number_copy')->nullable();
            }


            if (!Schema::hasColumn('ebooks','edition')) {
                $table->string('edition')->nullable();
            }


            if (!Schema::hasColumn('ebooks','imprint')) {
                $table->text('imprint')->nullable();
            }


            if (!Schema::hasColumn('ebooks','physical_description')) {
                $table->text('physical_description')->nullable();
            }


            if (!Schema::hasColumn('ebooks','series_name')) {
                $table->string('series_name')->nullable();
            }


            if (!Schema::hasColumn('ebooks','subject_heading')) {
                $table->text('subject_heading')->nullable();
            }


            if (!Schema::hasColumn('ebooks','added_personal_name')) {
                $table->string('added_personal_name')->nullable();
            }


            if (!Schema::hasColumn('ebooks','added_corporate_name')) {
                $table->string('added_corporate_name')->nullable();
            }


            if (!Schema::hasColumn('ebooks','budget_year')) {
                $table->string('budget_year')->nullable();
            }


            if (!Schema::hasColumn('ebooks','registration_number')) {
                $table->string('registration_number')->nullable();
            }


            if (!Schema::hasColumn('ebooks','campus')) {
                $table->string('campus')->nullable();
            }


            if (!Schema::hasColumn('ebooks','faculty')) {
                $table->string('faculty')->nullable();
            }


            if (!Schema::hasColumn('ebooks','cataloguer')) {
                $table->string('cataloguer')->nullable();
            }


            if (!Schema::hasColumn('ebooks','language')) {
                $table->string('language')->nullable();
            }


            if (!Schema::hasColumn('ebooks','barcode')) {
                $table->string('barcode')->nullable();
            }


            if (!Schema::hasColumn('ebooks','item_class')) {
                $table->string('item_class')->nullable();
            }


            if (!Schema::hasColumn('ebooks','collection_type')) {
                $table->string('collection_type')->nullable();
            }


            if (!Schema::hasColumn('ebooks','source')) {
                $table->string('source')->nullable();
            }


            if (!Schema::hasColumn('ebooks','main_category_code')) {
                $table->string('main_category_code')->nullable();
            }


        });

    }


    public function down(): void
    {
        //
    }

};