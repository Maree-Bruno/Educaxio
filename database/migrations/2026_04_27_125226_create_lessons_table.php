<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
