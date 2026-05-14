<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'group_id' => Group::factory(),
            'subject_id' => Subject::factory(),
        ];
    }
}