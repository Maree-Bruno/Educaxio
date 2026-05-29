<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\User;

class AttendancePolicy
{
    public function create(User $user, Lesson $lesson): bool
    {
        return $user->lessons()->where('lessons.id', $lesson->id)->exists();
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $attendance->classsession->lesson->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
