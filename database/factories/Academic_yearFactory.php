<?php

namespace Database\Factories;

use App\Models\Academic_year;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class Academic_yearFactory extends Factory
{
    protected $model = Academic_year::class;

    public function definition(): array
    {
        return [
            'year' => $this->faker->year(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
