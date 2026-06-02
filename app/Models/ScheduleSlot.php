<?php

namespace App\Models;

use App\Enums\ScheduleSlotType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = ['position', 'label', 'type', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return [
            'type' => ScheduleSlotType::class,
        ];
    }

    public function schoolSlotTimes(): HasMany
    {
        return $this->hasMany(SchoolSlotTime::class);
    }
}
