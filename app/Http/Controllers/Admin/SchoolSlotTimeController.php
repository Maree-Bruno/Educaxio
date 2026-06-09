<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSlotTime;
use Illuminate\Http\Request;

class SchoolSlotTimeController extends Controller
{
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'slots' => ['required', 'array'],
            'slots.*.slot_id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'slots.*.start_time' => ['required', 'date_format:H:i'],
            'slots.*.end_time' => ['required', 'date_format:H:i', 'after:slots.*.start_time'],
        ]);

        $slots = $validated['slots'];

        for ($i = 1; $i < count($slots); $i++) {
            if ($slots[$i]['start_time'] < $slots[$i - 1]['end_time']) {
                return back()->withErrors([
                    "slots.{$i}.start_time" => "L'heure de début doit être après {$slots[$i - 1]['end_time']}.",
                ])->withInput();
            }
        }

        foreach ($slots as $row) {
            SchoolSlotTime::updateOrCreate(
                ['school_id' => $school->id, 'schedule_slot_id' => $row['slot_id']],
                ['start_time' => $row['start_time'], 'end_time' => $row['end_time']],
            );
        }

        return to_route('profile.edit');
    }
}
