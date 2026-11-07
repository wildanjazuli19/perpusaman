<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_code', 24)->unique();
            $table->foreignId('loan_id')->constrained('loans')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('book_condition', 20);
            $table->text('notes')->nullable();
            $table->dateTime('returned_at');
            $table->timestamps();

            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_returns');
    }
};
