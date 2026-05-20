<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('label');
            $table->string('classroom')->nullable();
            $table->string('type')->default('slot');
            $table->timestamps();

            $table->unique(['schedule_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
    }
};