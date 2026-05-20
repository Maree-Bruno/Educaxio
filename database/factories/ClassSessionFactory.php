<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\ClassSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'classroom' => $this->faker->bothify('Salle ##?'),
            'lesson_id' => Lesson::factory(),
            'schedule_slot_id' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
