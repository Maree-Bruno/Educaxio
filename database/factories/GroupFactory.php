<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomLetter(),
            'grade' => $this->faker->numberBetween(1, 6),
            'academic_year_id' => AcademicYear::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
