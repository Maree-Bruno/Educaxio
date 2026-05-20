<?php

namespace App\Models;

use App\Enums\ScheduleSlotType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'position', 'label', 'classroom', 'type'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ScheduleSlotType::class,
        ];
    }
}