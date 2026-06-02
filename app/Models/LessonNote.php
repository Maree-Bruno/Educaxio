<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonNote extends Model
{
    protected $fillable = ['lesson_id', 'date', 'notes'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
