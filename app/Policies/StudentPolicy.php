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
        return $this->isAdminOf($user, $student->school_id);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->isAdminOf($user, $student->school_id);
    }

    private function isAdminOf(User $user, int $schoolId): bool
    {
        return $user->schools()
            ->where('schools.id', $schoolId)
            ->wherePivot('role', 'admin')
            ->exists();
    }
}
