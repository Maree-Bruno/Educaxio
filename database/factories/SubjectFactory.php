<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Anglais', 'Néerlandais', 'Mathématiques', 'Sciences',
                'Biologie', 'Physique', 'Chimie', 'Sciences humaines',
                'Éducation physique', 'Arts', 'Français', 'Histoire',
                'Géographie', 'Morale',
            ]),
        ];
    }
}
