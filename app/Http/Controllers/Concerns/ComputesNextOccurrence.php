<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Lesson;
use Carbon\Carbon;

trait ComputesNextOccurrence
{
    private function nextOccurrence(Lesson $lesson): string
    {
        $dows = $lesson->scheduleEntries->pluck('day_of_week');
        $date = Carbon::tomorrow();

        for ($i = 0; $i < 7; $i++) {
            if ($dows->contains($date->dayOfWeekIso)) {
                return $date->toDateString();
            }
            $date->addDay();
        }

        return Carbon::tomorrow()->toDateString();
    }

    private function upcomingOccurrences(Lesson $lesson, int $count = 8): array
    {
        $lesson->loadMissing('scheduleEntries.scheduleSlot');

        $results = [];
        $date    = Carbon::tomorrow();

        for ($day = 0; $day < 60 && count($results) < $count; $day++) {
            foreach ($lesson->scheduleEntries->sortBy('scheduleSlot.position') as $entry) {
                if ($entry->day_of_week === $date->dayOfWeekIso) {
                    $results[] = [
                        'date'  => $date->toDateString(),
                        'label' => ucfirst($date->locale('fr_BE')->isoFormat('ddd D MMM'))
                                   .' · '.$entry->scheduleSlot->label,
                    ];
                }
            }
            $date->addDay();
        }

        return $results;
    }
}