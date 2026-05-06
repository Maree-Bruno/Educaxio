<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $grade = $this->faker->numberBetween(1, 6).'eme';
        $name = strtoupper($this->faker->randomLetter());

        return [
            'name' => $name,
            'grade' => $grade,
            'slug' => Str::slug("{$grade}-{$name}-".uniqid()),
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
