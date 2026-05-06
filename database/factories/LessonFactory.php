<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'group_id' => Group::factory(),
            'subject_id' => Subject::factory(),
            'user_id' => User::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
