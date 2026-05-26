<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleSlotType;
use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleSlotController extends Controller
{
    public function updateType(Request $request)
    {
        $validated = $request->validate([
            'id'   => ['required', 'integer', 'exists:schedule_slots,id'],
            'type' => ['required', Rule::enum(ScheduleSlotType::class)],
        ]);

        ScheduleSlot::where('id', $validated['id'])
            ->update(['type' => $validated['type']]);

        return back();
    }
}
