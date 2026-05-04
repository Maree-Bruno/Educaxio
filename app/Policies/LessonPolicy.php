<?php

namespace App\Policies;

use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;
    public function viewLesson(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->user_id;
    }

    public function viewClasssession(User $user, ClassSession $classsession): bool
    {
        return $user->id === $classsession->lesson->user_id;
    }
}
