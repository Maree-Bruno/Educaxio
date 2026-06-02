<?php

/** @noinspection D */

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\LessonNote;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const ROOMS = ['101', '102', '103', '201', '202', '203', '301', '302', '303'];

    private const JOURNAL_NOTES = [
        'Introduction du chapitre. Exercices corrigés collectivement.',
        'Retour sur les notions vues la semaine passée. Quelques difficultés signalées.',
        'Exposé préparé par les élèves. Bonne participation générale.',
        'Révisions pour l\'interrogation. Ambiance studieuse.',
        'Correction du devoir maison. Questions pertinentes.',
        'Travail en groupes. Résultats satisfaisants dans l\'ensemble.',
        'Début d\'une nouvelle séquence. Bon démarrage.',
        'Exercices individuels. Plusieurs élèves en difficulté sur la notion.',
        'Visionnage d\'un document + questions. Très bon engagement de la classe.',
        'Dictée / exercice d\'écoute. Résultats mitigés.',
        'Mise en commun des travaux de recherche.',
        'Cours magistral. Prise de notes vérifiée en fin de séance.',
    ];

    private const HOMEWORK_TITLES = [
        'Langue néerlandaise' => ['Woordenschat p. %d', 'Grammatica oefeningen %d–%d', 'Tekst lezen h. %d', 'Schrijfoefening'],
        'Langue anglaise' => ['Vocabulary p. %d–%d', 'Grammar exercises %d à %d', 'Read and summarize ch. %d', 'Listening worksheet'],
        'Langue allemande' => ['Wortschatz S. %d', 'Grammatik Übungen %d–%d', 'Text lesen Kap. %d', 'Schreibübung'],
        'Langue française' => ['Rédaction : %s', 'Analyse de texte p. %d', 'Conjugaison – fiche %d', 'Lecture ch. %d'],
        'Mathématiques' => ['Ex. p. %d n° %d à %d', 'Série d\'exercices – algèbre', 'Problèmes p. %d', 'Fiche de révision'],
        'Sciences' => ['Résumé ch. %d', 'Fiche d\'observation', 'Questions p. %d–%d', 'Schéma à compléter'],
        'Physique' => ['Ex. p. %d n° %d à %d', 'Problèmes – ch. %d', 'Fiche de lois ch. %d', 'Exercices de conversion'],
        'Chimie' => ['Équations à équilibrer p. %d', 'Exercices – ch. %d', 'Fiche de nomenclature', 'Questions p. %d–%d'],
        'Biologie' => ['Résumé ch. %d', 'Schéma à légender', 'Questions p. %d–%d', 'Fiche d\'observation'],
        'Histoire' => ['Résumé du cours – ch. %d', 'Analyse de document p. %d', 'Frise chronologique', 'Questions p. %d–%d'],
        'Géographie' => ['Croquis ch. %d', 'Questions p. %d–%d', 'Analyse de carte', 'Résumé ch. %d'],
    ];

    private const TEST_TITLES = [
        'Interrogation – Chapitre %d',
        'Test récapitulatif',
        'Contrôle de connaissances',
        'Mini-test vocabulaire',
        'Interrogation surprise',
        'Évaluation formative',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────
        $adminSj = $this->createUser('Admin Saint-Joseph', 'admin.sj@example.com');
        $adminAr = $this->createUser('Admin Athénée', 'admin.ar@example.com');
        $profAnglais = $this->createUser('John Doe', 'test@example.com');
        $profMaths = $this->createUser('Marie Dupont', 'prof.maths@example.com');
        $profFrancais = $this->createUser('Pierre Martin', 'prof.francais@example.com');
        $profNl = $this->createUser('Sophie Leclercq', 'prof.nl@example.com');

        $teachers = collect([$profAnglais, $profMaths, $profFrancais, $profNl]);

        // ── Schools & academic year ────────────────────────────────────────────
        $academicYear = AcademicYear::create(['year' => '2025-2026']);

        $saintJoseph = School::create(['name' => 'Institut Saint-Joseph', 'slug' => 'saint-joseph']);
        $athenee = School::create(['name' => 'Athénée Royal de Bruxelles', 'slug' => 'athenee-royal-bruxelles']);

        $saintJoseph->users()->attach($adminSj->id, ['role' => 'admin']);
        $athenee->users()->attach($adminAr->id, ['role' => 'admin']);

        foreach ([$saintJoseph, $athenee] as $school) {
            $school->academicYears()->attach($academicYear->id);
            $teachers->each(fn (User $t) => $school->users()->attach($t->id, ['role' => 'teacher']));
        }

        // ── Subjects (cours généraux FWB – enseignement secondaire) ───────────
        $subjects = collect([
            'Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande',
            'Mathématiques', 'Sciences', 'Biologie', 'Physique', 'Chimie',
            'Histoire', 'Géographie', 'Éducation physique',
            'Philosophie et citoyenneté', 'Économie', 'Sciences informatiques',
            'Arts plastiques', 'Latin',
        ])->map(fn (string $name) => Subject::create(['name' => $name]));

        foreach ([$saintJoseph, $athenee] as $school) {
            $school->subjects()->attach($subjects->pluck('id'));
        }

        // ── Schedule slots ────────────────────────────────────────────────────
        $slots = $this->createScheduleSlots();

        // ── Groups ────────────────────────────────────────────────────────────
        $sjGroups = collect([
            $this->makeGroup('3', 'A', $saintJoseph, $academicYear),
            $this->makeGroup('3', 'B', $saintJoseph, $academicYear),
            $this->makeGroup('4', 'A', $saintJoseph, $academicYear),
            $this->makeGroup('2', 'A', $saintJoseph, $academicYear),
        ]);

        $arGroups = collect([
            $this->makeGroup('3', 'A', $athenee, $academicYear),
            $this->makeGroup('1', 'B', $athenee, $academicYear),
            $this->makeGroup('2', 'C', $athenee, $academicYear),
            $this->makeGroup('1', 'D', $athenee, $academicYear),
        ]);

        // ── Teacher subject preferences (profil déclaré) ──────────────────────
        $teacherSubjects = [
            $profAnglais->id => [$subjects->firstWhere('name', 'Langue anglaise'), $subjects->firstWhere('name', 'Langue allemande')],
            $profNl->id => [$subjects->firstWhere('name', 'Langue néerlandaise'), $subjects->firstWhere('name', 'Langue anglaise')],
            $profMaths->id => [$subjects->firstWhere('name', 'Mathématiques'), $subjects->firstWhere('name', 'Sciences'), $subjects->firstWhere('name', 'Physique'), $subjects->firstWhere('name', 'Chimie')],
            $profFrancais->id => [$subjects->firstWhere('name', 'Langue française'), $subjects->firstWhere('name', 'Latin')],
        ];

        foreach ($teacherSubjects as $teacherId => $subjectList) {
            $teachers->firstWhere('id', $teacherId)
                ->subjects()
                ->attach(collect($subjectList)->pluck('id'));
        }

        // ── Lessons (cours attribués en classe) ───────────────────────────────
        // Each entry: [subject, lm_level|null] — null for non-language subjects
        $lessonSubjects = [
            $profAnglais->id => [[$subjects->firstWhere('name', 'Langue anglaise'), 1]],
            $profNl->id => [[$subjects->firstWhere('name', 'Langue néerlandaise'), null]], // resolved per-group
            $profMaths->id => [[$subjects->firstWhere('name', 'Mathématiques'), null], [$subjects->firstWhere('name', 'Sciences'), null]],
            $profFrancais->id => [[$subjects->firstWhere('name', 'Langue française'), null]],
        ];

        $lessonData = collect();

        foreach ([$saintJoseph->id => $sjGroups, $athenee->id => $arGroups] as $schoolId => $groups) {
            foreach ($groups as $group) {
                foreach ($lessonSubjects as $teacherId => $subjectList) {
                    foreach ($subjectList as [$subject, $lmLevel]) {
                        // Néerlandais: LM1 in grade 1–2, LM2 in grade 3+
                        if ($subject->name === 'Langue néerlandaise') {
                            $lmLevel = (int) $group->grade <= 2 ? 1 : 2;
                        }

                        $lesson = Lesson::create([
                            'group_id' => $group->id,
                            'subject_id' => $subject->id,
                            'lm_level' => $lmLevel,
                        ]);
                        $lesson->users()->attach($teacherId);

                        $lessonData->push([
                            'lesson' => $lesson,
                            'teacher' => $teachers->firstWhere('id', $teacherId),
                            'school_id' => $schoolId,
                            'subject_name' => $subject->name,
                        ]);
                    }
                }
            }
        }

        // ── Schedules & entries ───────────────────────────────────────────────
        $schedules = collect();
        foreach ($teachers as $teacher) {
            foreach ([$saintJoseph, $athenee] as $school) {
                $schedules->put("{$teacher->id}_{$school->id}", Schedule::create([
                    'user_id' => $teacher->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                ]));
            }
        }

        $this->seedScheduleEntries($lessonData, $schedules, $slots);

        // ── Historical sessions & attendance ──────────────────────────────────
        $this->seedSessions($lessonData);

        // ── Upcoming assignments ──────────────────────────────────────────────
        $this->seedAssignments($lessonData);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function createUser(string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function createScheduleSlots(): Collection
    {
        $slots = collect();
        $startMinutes = 8 * 60 + 30;

        foreach (range(1, 10) as $position) {
            $endMinutes = $startMinutes + 50;

            $slots->push(ScheduleSlot::create([
                'position' => $position,
                'label' => $position === 1 ? '1ère heure' : "{$position}e heure",
                'type' => 'slot',
                'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
                'end_time' => sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60),
            ]));

            $startMinutes = $endMinutes;
        }

        return $slots;
    }

    private function makeGroup(string $grade, string $name, School $school, AcademicYear $year): Group
    {
        $group = Group::create([
            'grade' => $grade,
            'name' => $name,
            'slug' => Str::slug("{$school->slug}-{$grade}-{$name}"),
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
        ]);

        $group->students()->attach(
            Student::factory(15)->create(['school_id' => $school->id])->pluck('id'),
        );

        return $group;
    }

    private function seedScheduleEntries(Collection $lessonData, Collection $schedules, Collection $slots): void
    {
        $usedSlotDays = [];

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $teacher = $data['teacher'];
            $schedule = $schedules->get("{$teacher->id}_{$data['school_id']}");
            $assigned = 0;
            $attempts = 0;

            while ($assigned < 2 && $attempts < 60) {
                $attempts++;
                $slot = $slots->random();
                $day = fake()->numberBetween(1, 5);

                if (isset($usedSlotDays[$teacher->id][$slot->id][$day])) {
                    continue;
                }

                $usedSlotDays[$teacher->id][$slot->id][$day] = true;

                ScheduleEntry::create([
                    'schedule_id' => $schedule->id,
                    'schedule_slot_id' => $slot->id,
                    'lesson_id' => $lesson->id,
                    'day_of_week' => $day,
                    'classroom' => fake()->randomElement(self::ROOMS),
                ]);

                $assigned++;
            }
        }
    }

    private function seedSessions(Collection $lessonData): void
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subWeeks(8)->startOfWeek();

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $lesson->load(['scheduleEntries', 'group.students']);

            $dows = $lesson->scheduleEntries->pluck('day_of_week');
            $students = $lesson->group->students;

            if ($dows->isEmpty() || $students->isEmpty()) {
                continue;
            }

            $date = $startDate->copy();
            while ($date->lt($today)) {
                if ($dows->contains($date->dayOfWeekIso)) {
                    $this->createSessionWithAttendance($lesson, $date, $students);
                }
                $date->addDay();
            }
        }
    }

    private function createSessionWithAttendance(Lesson $lesson, Carbon $date, Collection $students): void
    {
        $session = ClassSession::create([
            'lesson_id' => $lesson->id,
            'date' => $date->toDateString(),
        ]);

        if (fake()->boolean(40)) {
            LessonNote::create([
                'lesson_id' => $lesson->id,
                'date' => $date->toDateString(),
                'notes' => fake()->randomElement(self::JOURNAL_NOTES),
            ]);
        }

        $attendance = Attendance::create([
            'classsession_id' => $session->id,
            'validated_at' => $date->copy()->setHour(fake()->numberBetween(9, 17)),
        ]);

        foreach ($students as $student) {
            $roll = fake()->numberBetween(1, 100);

            if ($roll <= 80) {
                continue;
            }

            $type = match (true) {
                $roll <= 92 => 'Absent',
                $roll <= 97 => 'Late',
                default => 'Excluded',
            };

            StudentAttendanceStatus::create([
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'type' => $type,
                'motive' => ($type === 'Absent' && fake()->boolean(30)) ? fake()->sentence() : null,
            ]);
        }
    }

    private function seedAssignments(Collection $lessonData): void
    {
        $today = Carbon::today();

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $subjectName = $data['subject_name'];
            $dows = $lesson->scheduleEntries->pluck('day_of_week');

            if ($dows->isEmpty()) {
                continue;
            }

            $usedDates = [];

            // Past assignments (3–5 over the last 8 weeks)
            $pastCount = fake()->numberBetween(3, 5);
            for ($i = 0; $i < $pastCount; $i++) {
                $date = $this->prevSessionDate($today, $dows, $usedDates);
                if (! $date) {
                    continue;
                }
                $usedDates[] = $date->toDateString();
                $isTest = fake()->boolean(35);
                Assignment::create([
                    'lesson_id'      => $lesson->id,
                    'created_by'     => $data['teacher']->id,
                    'type'           => $isTest ? 'test' : 'homework',
                    'title'          => $this->generateAssignmentTitle($subjectName, $isTest),
                    'scheduled_date' => $date->toDateString(),
                    'description'    => fake()->boolean(30) ? fake()->sentence() : null,
                ]);
            }

            // Upcoming assignments (2–3 over the next 6 weeks)
            $futureCount = fake()->numberBetween(2, 3);
            for ($i = 0; $i < $futureCount; $i++) {
                $date = $this->nextSessionDate($today, $dows, $usedDates);
                if (! $date) {
                    continue;
                }
                $usedDates[] = $date->toDateString();
                $isTest = fake()->boolean(30);
                Assignment::create([
                    'lesson_id'      => $lesson->id,
                    'created_by'     => $data['teacher']->id,
                    'type'           => $isTest ? 'test' : 'homework',
                    'title'          => $this->generateAssignmentTitle($subjectName, $isTest),
                    'scheduled_date' => $date->toDateString(),
                    'description'    => fake()->boolean(30) ? fake()->sentence() : null,
                ]);
            }
        }
    }

    private function nextSessionDate(Carbon $from, Collection $dows, array $used): ?Carbon
    {
        $date = $from->copy();

        for ($i = 1; $i <= 42; $i++) {
            $date->addDay();

            if ($dows->contains($date->dayOfWeekIso) && ! in_array($date->toDateString(), $used)) {
                return $date;
            }
        }

        return null;
    }

    private function prevSessionDate(Carbon $from, Collection $dows, array $used): ?Carbon
    {
        $date = $from->copy();

        for ($i = 1; $i <= 56; $i++) {
            $date->subDay();

            if ($dows->contains($date->dayOfWeekIso) && ! in_array($date->toDateString(), $used)) {
                return $date;
            }
        }

        return null;
    }

    private function generateAssignmentTitle(string $subjectName, bool $isTest): string
    {
        if ($isTest) {
            return sprintf(
                fake()->randomElement(self::TEST_TITLES),
                fake()->numberBetween(1, 12),
            );
        }

        $templates = self::HOMEWORK_TITLES[$subjectName] ?? ['Exercices p. %d', 'Révisions ch. %d'];

        return sprintf(
            fake()->randomElement($templates),
            fake()->numberBetween(10, 180),
            fake()->numberBetween(1, 10),
            fake()->numberBetween(5, 20),
            fake()->sentence(3),
        );
    }
}
