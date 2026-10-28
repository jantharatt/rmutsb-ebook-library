<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrows', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('ebook_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('borrow_date');

            $table->date('due_date');

            $table->date('return_date')
                ->nullable();

            $table->enum('status',[
                'borrowed',
                'returned',
                'expired'
            ])->default('borrowed');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
