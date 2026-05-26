<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\SchoolJoinRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password'    => $this->passwordRules(),
            'subject_ids'   => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'school_ids'    => ['nullable', 'array'],
            'school_ids.*'  => ['integer', 'exists:schools,id'],
        ])->validate();

        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $input['password'],
        ]);

        if (! empty($input['subject_ids'])) {
            $user->subjects()->attach($input['subject_ids']);
        }

        foreach ($input['school_ids'] ?? [] as $schoolId) {
            SchoolJoinRequest::create([
                'user_id'   => $user->id,
                'school_id' => $schoolId,
                'status'    => 'pending',
            ]);
        }

        return $user;
    }
}
