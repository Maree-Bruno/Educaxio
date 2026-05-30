<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->text('notes');
            $table->timestamps();

            $table->unique(['lesson_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};