<?php

namespace App\Models;

use App\Enums\ScheduleSlotType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = ['position', 'label', 'type'];

    protected function casts(): array
    {
        return [
            'type' => ScheduleSlotType::class,
        ];
    }
}