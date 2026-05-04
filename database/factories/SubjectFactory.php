<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Anglais',
                'Néerlandais',
                'Mathématiques',
                'Sciences',
                'Biologie',
                'Physique',
                'Chimie',
                'Sciences humaines',
                'éducation physique',
                'Arts',
                'Français',
                'Histoire',
                'Géographie',
                'Morale',
            ]),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
