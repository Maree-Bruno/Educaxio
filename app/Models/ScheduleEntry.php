<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleEntry extends Model
{
    use SoftDeletes;
    protected $fillable = ['schedule_id', 'schedule_slot_id', 'lesson_id', 'day_of_week', 'classroom'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
