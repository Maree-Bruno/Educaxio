<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_subject', function (Blueprint $table) {
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->primary(['school_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_subject');
    }
};