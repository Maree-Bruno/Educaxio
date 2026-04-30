<?php

namespace Database\Factories;

use App\Enums\Attendance_type;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StudentAttendanceStatusFactory extends Factory
{
    protected $model = StudentAttendanceStatus::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(Attendance_type::values()),
            'motive' => $this->faker->text(),
            'student_id' => Student::factory(),
            'attendance_id' => Attendance::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
