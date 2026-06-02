<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSlotTime extends Model
{
    protected $fillable = ['school_id', 'schedule_slot_id', 'start_time', 'end_time'];

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
