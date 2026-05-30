<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\LessonNote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();

        $search = $request->input('search', '');
        $group = $request->input('group', '');
        $school = $request->input('school', '');

        // Load every lesson the user teaches, with all needed relationships, once.
        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,slug,school_id',
                'group.school:id,name',
                'subject:id,name',
                'scheduleEntries.scheduleSlot:id,label',
            ])
            ->get()
            ->keyBy('id');

        // Filter lesson IDs by group / school (PHP-side — no extra queries).
        $filteredLessons = $lessons
            ->when($group, fn ($col) => $col->filter(fn ($l) => $l->group->grade.$l->group->name === $group))
            ->when($school, fn ($col) => $col->filter(fn ($l) => $l->group->school->name === $school));

        $lessonIds = $filteredLessons->keys()->toArray();

        // Lesson IDs whose metadata (subject / group name) match the search term.
        $searchLessonIds = $search
            ? $filteredLessons->filter(fn ($l) => str_contains(mb_strtolower($l->subject->name), mb_strtolower($search)) ||
                str_contains(mb_strtolower($l->group->grade.$l->group->name), mb_strtolower($search))
            )->keys()->toArray()
            : null;

        $journalEntries = LessonNote::whereIn('lesson_id', $lessonIds)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('notes', 'like', "%{$search}%")
                ->orWhereIn('lesson_id', $searchLessonIds ?? [])
            ))
            ->orderByDesc('date')
            ->paginate(25)
            ->through(function ($n) use ($lessons) {
                $lesson = $lessons[$n->lesson_id];

                return [
                    'id'         => $n->id,
                    'date'       => $n->date->toDateString(),
                    'notes'      => $n->notes,
                    'group'      => $lesson->group->grade.$lesson->group->name,
                    'group_slug' => $lesson->group->slug,
                    'subject'    => $lesson->subject->name,
                    'school'     => $lesson->group->school->name,
                ];
            });

        $assignments = Assignment::whereIn('lesson_id', $lessonIds)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhereIn('lesson_id', $searchLessonIds ?? [])
            ))
            ->orderBy('scheduled_date')
            ->get()
            ->map(function ($a) use ($lessons) {
                $lesson = $lessons[$a->lesson_id];
                $dow = Carbon::parse($a->scheduled_date)->dayOfWeekIso;
                $entry = $lesson->scheduleEntries->first(fn ($e) => $e->day_of_week === $dow);

                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'title' => $a->title,
                    'scheduled_date' => $a->scheduled_date->toDateString(),
                    'description' => $a->description,
                    'group' => $lesson->group->grade.$lesson->group->name,
                    'group_slug' => $lesson->group->slug,
                    'subject' => $lesson->subject->name,
                    'school' => $lesson->group->school->name,
                    'slot_label' => $entry?->scheduleSlot?->label,
                ];
            });

        $groupOptions = $lessons->map(fn ($l) => $l->group->grade.$l->group->name)->unique()->sort()->values();
        $schoolOptions = $lessons->map(fn ($l) => $l->group->school->name)->unique()->sort()->values();

        return Inertia::render('Agenda', [
            'journalEntries' => $journalEntries,
            'assignments' => $assignments,
            'groupOptions' => $groupOptions,
            'schoolOptions' => $schoolOptions,
            'filters' => [
                'search' => $search,
                'group' => $group,
                'school' => $school,
            ],
        ]);
    }
}
