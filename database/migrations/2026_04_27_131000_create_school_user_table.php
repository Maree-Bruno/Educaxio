<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_user', function (Blueprint $table) {
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['admin', 'teacher'])->default('teacher');
            $table->primary(['school_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user');
    }
};