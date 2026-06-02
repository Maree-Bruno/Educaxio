<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\LessonNote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();

        $search = $request->input('search', '');
        $group = $request->input('group', '');
        $school = $request->input('school', '');
        $sortField = in_array($request->input('sort_field'), ['date', 'group', 'subject'])
            ? $request->input('sort_field')
            : 'date';
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc'])
            ? $request->input('sort_dir')
            : 'desc';
        $assignmentType = in_array($request->input('assignment_type'), ['homework', 'test'])
            ? $request->input('assignment_type') : null;
        $lessons = $user->lessons()
            ->with([
                'group:id,grade,name,slug,school_id',
                'group.school:id,name',
                'subject:id,name',
                'scheduleEntries.scheduleSlot:id,label',
            ])
            ->get()
            ->keyBy('id');
        $filteredLessons = $lessons
            ->when($group, fn ($col) => $col->filter(fn ($l) => $l->group->grade.$l->group->name === $group))
            ->when($school, fn ($col) => $col->filter(fn ($l) => $l->group->school->name === $school));

        $lessonIds = $filteredLessons->keys()->toArray();
        $searchLessonIds = $search
            ? $filteredLessons->filter(fn ($l) => str_contains(mb_strtolower($l->subject->name),
                mb_strtolower($search)) ||
                str_contains(mb_strtolower($l->group->grade.$l->group->name), mb_strtolower($search))
            )->keys()->toArray()
            : null;

        $journalQuery = LessonNote::whereIn('lesson_notes.lesson_id', $lessonIds)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('lesson_notes.notes', 'like', "%{$search}%")
                ->orWhereIn('lesson_notes.lesson_id', $searchLessonIds ?? [])
            ));

        if ($sortField === 'group') {
            $journalQuery = $journalQuery
                ->join('lessons', 'lesson_notes.lesson_id', '=', 'lessons.id')
                ->join('groups', 'lessons.group_id', '=', 'groups.id')
                ->orderByRaw("CONCAT(groups.grade, groups.name) {$sortDir}")
                ->select('lesson_notes.*');
        } elseif ($sortField === 'subject') {
            $journalQuery = $journalQuery
                ->join('lessons', 'lesson_notes.lesson_id', '=', 'lessons.id')
                ->join('subjects', 'lessons.subject_id', '=', 'subjects.id')
                ->orderBy('subjects.name', $sortDir)
                ->select('lesson_notes.*');
        } else {
            $journalQuery = $journalQuery->orderBy('lesson_notes.date', $sortDir);
        }

        $journalEntries = $journalQuery
            ->paginate(10)
            ->through(function ($n) use ($lessons) {
                $lesson = $lessons[$n->lesson_id];
                $dow = Carbon::parse($n->date)->dayOfWeekIso;
                $entry = $lesson->scheduleEntries->first(fn ($e) => $e->day_of_week === $dow);

                return [
                    'id' => $n->id,
                    'date' => $n->date->toDateString(),
                    'notes' => $n->notes,
                    'group' => $lesson->group->grade.$lesson->group->name,
                    'group_slug' => $lesson->group->slug,
                    'subject' => $lesson->subjectLabel(),
                    'school' => $lesson->group->school->name,
                    'slot_label' => $entry?->scheduleSlot?->label,
                ];
            });

        $today = now()->toDateString();

        $upcomingAssignments = $this->buildAssignmentQuery($lessonIds, $search, $searchLessonIds, $assignmentType, $sortField, $sortDir, false, $today)
            ->paginate(10, ['*'], 'upcoming_page')
            ->through(fn ($a) => $this->formatAssignment($a, $lessons));

        $pastAssignments = $this->buildAssignmentQuery($lessonIds, $search, $searchLessonIds, $assignmentType, $sortField, $sortDir, true, $today)
            ->paginate(10, ['*'], 'past_page')
            ->through(fn ($a) => $this->formatAssignment($a, $lessons));

        $groupOptions = $lessons->map(fn ($l) => $l->group->grade.$l->group->name)->unique()->sort()->values();
        $schoolOptions = $lessons->map(fn ($l) => $l->group->school->name)->unique()->sort()->values();

        return Inertia::render('Agenda', [
            'journalEntries' => $journalEntries,
            'upcomingAssignments' => $upcomingAssignments,
            'pastAssignments' => $pastAssignments,
            'groupOptions' => $groupOptions,
            'schoolOptions' => $schoolOptions,
            'filters' => [
                'search' => $search,
                'group' => $group,
                'school' => $school,
                'sort_field' => $sortField,
                'sort_dir' => $sortDir,
                'assignment_type' => $assignmentType ?? '',
            ],
        ]);
    }

    private function buildAssignmentQuery(
        array $lessonIds,
        string $search,
        ?array $searchLessonIds,
        ?string $assignmentType,
        string $sortField,
        string $sortDir,
        bool $past,
        string $today,
    ): Builder {
        $query = Assignment::whereIn('assignments.lesson_id', $lessonIds)
            ->when($past,
                fn ($q) => $q->where('assignments.scheduled_date', '<', $today),
                fn ($q) => $q->where('assignments.scheduled_date', '>=', $today),
            )
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('assignments.title', 'like', "%{$search}%")
                ->orWhereIn('assignments.lesson_id', $searchLessonIds ?? [])
            ))
            ->when($assignmentType, fn ($q) => $q->where('assignments.type', $assignmentType));

        if ($sortField === 'group') {
            $query->join('lessons as al', 'assignments.lesson_id', '=', 'al.id')
                ->join('groups as ag', 'al.group_id', '=', 'ag.id')
                ->orderByRaw("CONCAT(ag.grade, ag.name) {$sortDir}")
                ->select('assignments.*');
        } elseif ($sortField === 'subject') {
            $query->join('lessons as sl', 'assignments.lesson_id', '=', 'sl.id')
                ->join('subjects as ss', 'sl.subject_id', '=', 'ss.id')
                ->orderBy('ss.name', $sortDir)
                ->select('assignments.*');
        } else {
            $query->orderBy('assignments.scheduled_date', $past ? 'desc' : 'asc');
        }

        return $query;
    }

    private function formatAssignment(Assignment $a, Collection $lessons): array
    {
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
            'subject' => $lesson->subjectLabel(),
            'school' => $lesson->group->school->name,
            'slot_label' => $entry?->scheduleSlot?->label,
        ];
    }
}
