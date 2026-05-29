<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function create(User $user): bool
    {
        return $user->schools()->wherePivot('role', 'admin')->exists();
    }

    public function update(User $user, Group $group): bool
    {
        return $this->isAdminOf($user, $group->school_id);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->isAdminOf($user, $group->school_id);
    }

    public function attachStudent(User $user, Group $group): bool
    {
        return $this->isAdminOf($user, $group->school_id);
    }

    private function isAdminOf(User $user, int $schoolId): bool
    {
        return $user->schools()
            ->where('schools.id', $schoolId)
            ->wherePivot('role', 'admin')
            ->exists();
    }
}
