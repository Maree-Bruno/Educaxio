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
            'lesson_hour' => $this->faker->numberBetween(1, 10),
            'classroom' => $this->faker->word(),
            'lesson_id' => Lesson::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
