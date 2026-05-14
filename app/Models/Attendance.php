<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'validated_at',
        'classsession_id',
        'user_id',
    ];

    public function classsession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'classsession_id');
    }

    public function studentAttendanceStatuses(): HasMany
    {
        return $this->hasMany(StudentAttendanceStatus::class);
    }

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }
}
