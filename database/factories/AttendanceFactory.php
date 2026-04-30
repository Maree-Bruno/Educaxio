<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'validated_at' => Carbon::now(),
            'classsession_id' => ClassSession::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
