<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'lastname' => $this->faker->lastName(),
            'firstname' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'school_id' => School::factory(),
        ];
    }
}
