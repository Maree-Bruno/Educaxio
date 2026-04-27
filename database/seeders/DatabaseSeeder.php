<?php

namespace Database\Seeders;

use App\Models\Academic_year;
use App\Models\Subject;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Subject::factory(10)->create();
        Academic_year::factory(10)->create();
        User::factory()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }
}
