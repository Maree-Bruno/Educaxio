<?php

namespace App\Policies;

use App\Models\ScheduleEntry;
use App\Models\User;

class ScheduleEntryPolicy
{
    public function delete(User $user, ScheduleEntry $scheduleEntry): bool
    {
        return $scheduleEntry->schedule->user_id === $user->id;
    }
}
