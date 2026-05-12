<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'lastname',
        'firstname',
        'email',
        'user_id',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function attendanceStatuses(): HasMany
    {
        return $this->hasMany(StudentAttendanceStatus::class);
    }
}
