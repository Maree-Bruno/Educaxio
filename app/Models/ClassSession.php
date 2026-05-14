<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassSession extends Model
{
    use HasFactory, BelongsToUser;

    protected $table = 'classsessions';

    protected $fillable = [
        'date',
        'lesson_hour',
        'lesson_id',
        'classroom',
        'user_id',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'classsession_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
