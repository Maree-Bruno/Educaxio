<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $user->schools()->where('schools.id', $student->school_id)->exists();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->schools()->where('schools.id', $student->school_id)->exists();
    }
}
