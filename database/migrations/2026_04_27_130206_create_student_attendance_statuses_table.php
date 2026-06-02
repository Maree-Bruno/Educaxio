<?php

use App\Enums\Attendance_type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('type', Attendance_type::values());
            $table->text('motive')->nullable();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_statuses');
    }
};
