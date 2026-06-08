<?php /** @noinspection D */

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
use App\Models\SchoolSlotTime;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private const SLOT_TIMES = [
        1 => ['08:30', '09:25'],
        2 => ['09:25', '10:20'],
        3 => ['10:30', '11:25'],
        4 => ['11:25', '12:20'],
        5 => ['13:20', '14:15'],
        6 => ['14:15', '15:10'],
        7 => ['15:20', '16:15'],
        8 => ['16:15', '17:10'],
        9 => ['17:20', '18:15'],
        10 => ['18:15', '19:10'],
    ];

    private const ROOMS = ['101', '102', '103', '201', '202', '203', '301', '302', '303', 'Labo 1', 'Labo 2', 'Salle informatique', 'Gymnase'];

    private const LASTNAMES = [
        'Dubois', 'Lambert', 'Masson', 'Renard', 'Simon',
        'Lecomte', 'Fontaine', 'Marchal', 'Denis', 'Pirard',
        'Collin', 'Ernst', 'Deprez', 'Gilles', 'Habran',
        'Lejeune', 'Noël', 'Peeters', 'Bodart', 'Goffin',
    ];

    private const FIRSTNAMES = [
        'Emma', 'Lucas', 'Léa', 'Nathan', 'Chloé',
        'Tom', 'Inès', 'Théo', 'Camille', 'Axel', 'Manon',
    ];

    // [grade, section, size]
    private const GROUPS_CONFIG = [
        ['1', 'A', 20], ['1', 'B', 20],
        ['2', 'A', 18], ['2', 'B', 18],
        ['3', 'A', 18], ['3', 'B', 18],
        ['4', 'A', 18], ['4', 'B', 18],
        ['5', 'A', 18], ['5', 'B', 18],
        ['6', 'A', 18], ['6', 'B', 18],
    ];

    // [email, name, main_subject, group_indices (null = all 12)]
    private const TEACHERS_CONFIG = [
        ['math@example.com',    'Marie Dupont',    'Mathématiques',       null],
        ['french@example.com',  'Pierre Martin',   'Langue française',    null],
        ['dutch@example.com',   'Sophie Leclercq', 'Langue néerlandaise', [0, 1, 2, 3, 4, 5]],
        ['english@example.com', 'John Doe',        'Langue anglaise',     [6, 7, 8, 9, 10, 11]],
        ['science@example.com', 'Luc Renard',      'Sciences',            [0, 1, 2, 3, 4, 5, 6, 7]],
        ['history@example.com', 'Nathalie Bodart', 'Histoire',            [4, 5, 6, 7, 8, 9, 10, 11]],
        ['pe@example.com',      'Marc Charlier',   'Éducation physique',  null],
    ];

    private const NOTES = [
        'Mathématiques' => [
            'Introduction aux fonctions du second degré. Bonne compréhension générale.',
            'Exercices sur les équations. Plusieurs élèves en difficulté avec le discriminant.',
            'Démonstration du théorème de Pythagore. Exercices en binômes.',
            'Révisions. Questions pertinentes sur les intégrales.',
            'Correction de l\'évaluation. Analyse des erreurs fréquentes.',
        ],
        'Langue française' => [
            'Lecture analytique du chapitre 3. Discussion sur les procédés narratifs.',
            'Rédaction en classe. Bonne implication des élèves.',
            'Étude d\'un extrait de Molière. Registres comique et satirique.',
            'Dictée suivie d\'une mise en commun. Mots invariables à retravailler.',
            'Analyse d\'un discours politique. Travail sur l\'implicite.',
        ],
        'Langue anglaise' => [
            'Reading comprehension on climate change. Good discussion.',
            'Grammar focus: past perfect vs. simple past.',
            'Oral presentations. Clear improvement since last term.',
            'Writing workshop: opinion essay structure. Peer correction.',
            'Film clip discussion. Lively debate about social media.',
        ],
        'Langue néerlandaise' => [
            'Woordenschat hoofdstuk 5. Oefeningen individueel en in groep.',
            'Luisteroefening: nieuwsfragment. Uitspraak verbeteren.',
            'Grammatica: de/het-woorden. Klassikale correctie.',
            'Spreekvaardigheid: dialoogjes. Goede sfeer in de klas.',
            'Herhaling voor de toets. Gerichte vragen van leerlingen.',
        ],
        'Sciences' => [
            'Observation de cellules au microscope. Très bon engagement.',
            'Cours sur la mitose. Schémas complétés au tableau.',
            'TP : extraction d\'ADN. Séance très appréciée.',
            'Introduction à l\'écosystème. Discussion sur la biodiversité.',
            'Révisions. Fiche synthèse distribuée.',
        ],
        'Histoire' => [
            'Étude de la Première Guerre mondiale : causes et déclenchement.',
            'Analyse de documents sur la Shoah. Moment solennel et respectueux.',
            'Cours sur la Révolution industrielle. Lien avec le contemporain discuté.',
            'Exposés sur les régimes totalitaires. Bonne qualité d\'ensemble.',
            'Introduction à la guerre froide. Repères chronologiques mis en place.',
        ],
        'default' => [
            'Introduction du chapitre. Exercices corrigés collectivement.',
            'Retour sur les notions vues. Quelques difficultés signalées.',
            'Exposé des élèves. Bonne participation générale.',
            'Révisions. Ambiance studieuse.',
            'Correction du devoir. Questions pertinentes.',
        ],
    ];

    private const HOMEWORK_TITLES = [
        'Mathématiques' => ['Ex. p. 45 n°1 à 5', 'Série algèbre p. 78', 'Problèmes p. 92', 'Fiche de révision 3'],
        'Langue française' => ['Rédaction p. 34', 'Analyse de texte p. 56', 'Conjugaison – fiche 4', 'Lecture ch. 3 à 5'],
        'Langue anglaise' => ['Vocabulary p. 28–30', 'Grammar exercises 5 to 8', 'Read and summarize ch. 4', 'Listening worksheet 3'],
        'Langue néerlandaise' => ['Woordenschat h. 6 p. 42', 'Grammatica oefeningen 3–6', 'Tekst lezen h. 5', 'Schrijfoefening p. 38'],
        'Sciences' => ['Résumé ch. 4', 'Fiche d\'observation p. 18', 'Questions p. 22–24', 'Schéma à compléter ch. 3'],
        'Histoire' => ['Résumé du cours – ch. 6', 'Analyse de document p. 38', 'Questions p. 45–47', 'Frise chronologique 3'],
        'default' => ['Exercices p. 55', 'Révisions ch. 7', 'Fiche de travail 4', 'Exercices p. 72'],
    ];

    private const TEST_TITLES = [
        'Interrogation – Chapitre 4',
        'Test récapitulatif – unité 2',
        'Contrôle de connaissances',
        'Évaluation formative – ch. 5',
        'Interrogation orale',
    ];

    // ── Instance state ─────────────────────────────────────────────────────────

    private School $school;

    private AcademicYear $currentYear;

    private AcademicYear $archivedYear;

    private Collection $slots;

    private Collection $subjects;

    private Collection $teachers;

    private Collection $groups;

    private Collection $groupStudents;

    private Collection $students;

    private Collection $lessonData;

    private Collection $schedules;

    public function run(): void
    {
        $this->createUsersAndSchool();
        $this->createSlots();
        $this->createSubjects();
        $this->createStudents();
        $this->createGroups();
        $this->createLessons();
        $this->createSchedules();
        $this->createSessionsAndAttendance();
        $this->createAssignments();
    }

    private function createUsersAndSchool(): void
    {
        $admin = User::create([
            'name' => 'Admin Éducaxio',
            'email' => 'admin@educaxio.be',
            'password' => 'password',
        ]);

        $this->teachers = collect();
        foreach (self::TEACHERS_CONFIG as [$email, $name]) {
            $this->teachers->put($email, User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
            ]));
        }

        $this->school = School::create([
            'name' => 'Institut Éducaxio',
            'slug' => 'educaxio-demo',
        ]);

        $this->currentYear = AcademicYear::create(['year' => '2025-2026']);
        $this->archivedYear = AcademicYear::create(['year' => '2024-2025']);

        $this->school->users()->attach($admin->id, ['role' => 'admin']);
        $this->teachers->each(fn ($t) => $this->school->users()->attach($t->id, ['role' => 'teacher']));

        $this->school->academicYears()->attach(
            $this->currentYear->id,
            AcademicYear::defaultDates($this->currentYear->year),
        );
        $this->school->academicYears()->attach($this->archivedYear->id, array_merge(
            AcademicYear::defaultDates($this->archivedYear->year),
            ['archived_at' => now()],
        ));
    }

    private function createSlots(): void
    {
        $this->slots = collect();

        foreach (self::SLOT_TIMES as $position => [$start, $end]) {
            $slot = ScheduleSlot::firstOrCreate(
                ['position' => $position],
                [
                    'label' => $position === 1 ? '1ère heure' : "{$position}e heure",
                    'type' => 'slot',
                    'start_time' => $start,
                    'end_time' => $end,
                ],
            );

            SchoolSlotTime::firstOrCreate(
                ['school_id' => $this->school->id, 'schedule_slot_id' => $slot->id],
                ['start_time' => $start, 'end_time' => $end],
            );

            $this->slots->push($slot);
        }
    }

    private function createSubjects(): void
    {
        $languageNames = ['Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande'];

        $names = [
            'Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande',
            'Mathématiques', 'Sciences', 'Biologie', 'Physique', 'Chimie',
            'Histoire', 'Géographie', 'Éducation physique',
            'Philosophie et citoyenneté', 'Économie', 'Sciences informatiques',
            'Arts plastiques', 'Latin',
        ];

        $this->subjects = collect($names)->mapWithKeys(fn ($name) => [
            $name => Subject::firstOrCreate(
                ['name' => $name],
                ['is_language' => in_array($name, $languageNames)],
            ),
        ]);

        $this->school->subjects()->attach($this->subjects->pluck('id'));
    }

    private function createStudents(): void
    {
        $this->students = collect();

        foreach (self::LASTNAMES as $last) {
            foreach (self::FIRSTNAMES as $first) {
                $this->students->push(Student::create([
                    'lastname' => $last,
                    'firstname' => $first,
                    'school_id' => $this->school->id,
                ]));
            }
        }
    }

    private function createGroups(): void
    {
        $this->groups = collect();
        $this->groupStudents = collect();
        $offset = 0;

        foreach (self::GROUPS_CONFIG as [$grade, $section, $size]) {
            $slug = Str::slug("educaxio-demo-2025-2026-{$grade}-{$section}");

            $group = Group::create([
                'grade' => $grade,
                'name' => $section,
                'slug' => $slug,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->currentYear->id,
            ]);

            $slice = $this->students->slice($offset, $size)->values();
            $group->students()->attach($slice->pluck('id'));

            $this->groups->push($group);
            $this->groupStudents->put($group->id, $slice);

            $offset += $size;
        }
    }

    private function createLessons(): void
    {
        $this->lessonData = collect();

        foreach (self::TEACHERS_CONFIG as [$email, , $subjectName, $groupIndices]) {
            $teacher = $this->teachers->get($email);
            $subject = $this->subjects->get($subjectName);
            $teacherGroups = $groupIndices !== null
                ? $this->groups->filter(fn ($g, $k) => in_array($k, $groupIndices))->values()
                : $this->groups->values();

            foreach ($teacherGroups as $group) {
                $lmLevel = match (true) {
                    $subjectName === 'Langue néerlandaise' => (int) $group->grade <= 2 ? 1 : 2,
                    $subjectName === 'Langue anglaise' => (int) $group->grade <= 5 ? 1 : 2,
                    default => null,
                };

                $lesson = Lesson::create([
                    'group_id' => $group->id,
                    'subject_id' => $subject->id,
                    'lm_level' => $lmLevel,
                ]);
                $lesson->users()->attach($teacher->id);

                $this->lessonData->push([
                    'lesson' => $lesson,
                    'teacher' => $teacher,
                    'subject_name' => $subjectName,
                    'group_id' => $group->id,
                ]);
            }
        }
    }

    private function createSchedules(): void
    {
        $this->schedules = collect();

        foreach ($this->teachers as $email => $teacher) {
            $schedule = Schedule::create([
                'user_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->currentYear->id,
            ]);
            $this->schedules->put($teacher->id, $schedule);
        }

        $lessonsByTeacher = $this->lessonData->groupBy(fn ($d) => $d['teacher']->id);

        mt_srand(2025);

        foreach ($lessonsByTeacher as $teacherId => $teacherLessons) {
            $schedule = $this->schedules->get($teacherId);

            // Build all 50 (slotIndex, day) pairs, shuffle them once per teacher
            $pairs = [];
            for ($s = 0; $s < 10; $s++) {
                for ($d = 1; $d <= 5; $d++) {
                    $pairs[] = [$s, $d];
                }
            }
            shuffle($pairs);

            $teacherUsed = [];

            foreach ($teacherLessons->values() as $i => $data) {
                $lessonDayCount = array_fill_keys(range(1, 5), 0);
                $assigned       = 0;

                foreach ($pairs as [$slotIndex, $day]) {
                    if ($assigned >= 4) {
                        break;
                    }

                    if (isset($teacherUsed[$slotIndex][$day])) {
                        continue;
                    }

                    if ($lessonDayCount[$day] >= 2) {
                        continue;
                    }

                    $slot = $this->slots->get($slotIndex);

                    if (! $slot) {
                        continue;
                    }

                    ScheduleEntry::create([
                        'schedule_id'      => $schedule->id,
                        'schedule_slot_id' => $slot->id,
                        'lesson_id'        => $data['lesson']->id,
                        'day_of_week'      => $day,
                        'classroom'        => self::ROOMS[($i * 4 + $assigned) % count(self::ROOMS)],
                    ]);

                    $teacherUsed[$slotIndex][$day] = true;
                    $lessonDayCount[$day]++;
                    $assigned++;
                }
            }
        }
    }

    private function createSessionsAndAttendance(): void
    {
        $this->lessonData->each(fn ($d) => $d['lesson']->load('scheduleEntries'));

        $from = Carbon::parse('2025-09-01');
        $to = Carbon::yesterday();
        $date = $from->copy()->startOfDay();
        $counters = [];

        while ($date->lte($to)) {
            if ($date->isWeekend()) {
                $date->addDay();

                continue;
            }

            $dow = $date->dayOfWeekIso;

            foreach ($this->lessonData as $data) {
                $lesson = $data['lesson'];
                $entry = $lesson->scheduleEntries->firstWhere('day_of_week', $dow);

                if (! $entry) {
                    continue;
                }

                $n = $counters[$lesson->id] = ($counters[$lesson->id] ?? 0) + 1;

                if ($n % 25 === 0) {
                    continue;
                }

                $session = ClassSession::create([
                    'lesson_id' => $lesson->id,
                    'date' => $date->toDateString(),
                ]);

                if ($n % 3 === 0) {
                    $notes = self::NOTES[$data['subject_name']] ?? self::NOTES['default'];
                    LessonNote::create([
                        'lesson_id' => $lesson->id,
                        'date' => $date->toDateString(),
                        'notes' => $notes[($n / 3 - 1) % count($notes)],
                    ]);
                }

                $attendance = Attendance::create([
                    'classsession_id' => $session->id,
                    'validated_at' => $date->copy()->setHour(9)->setMinute(30),
                ]);

                $groupStudents = $this->groupStudents->get($data['group_id'], collect());
                $this->seedAttendanceStatuses($attendance, $groupStudents, $n);
            }

            $date->addDay();
        }
    }

    private function seedAttendanceStatuses(Attendance $attendance, Collection $students, int $n): void
    {
        foreach ($students as $i => $student) {
            $status = match (true) {
                $i === 0 && $n % 8 === 0 => 'Absent',
                $i === 2 && $n % 12 === 0 => 'Absent',
                $i === 4 && $n % 10 === 0 => 'Late',
                $i === 6 && $n % 15 === 0 => 'Absent',
                default => null,
            };

            if ($status === null) {
                continue;
            }

            StudentAttendanceStatus::create([
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'type' => $status,
                'motive' => ($status === 'Absent' && $i === 6) ? 'Rendez-vous médical' : null,
            ]);
        }
    }

    private function createAssignments(): void
    {
        $today = Carbon::today();

        foreach ($this->lessonData as $data) {
            $lesson = $data['lesson'];
            $subject = $data['subject_name'];
            $dows = $lesson->scheduleEntries->pluck('day_of_week');

            if ($dows->isEmpty()) {
                continue;
            }

            $homeworks = self::HOMEWORK_TITLES[$subject] ?? self::HOMEWORK_TITLES['default'];
            $used = [];

            for ($i = 0; $i < 3; $i++) {
                $date = $this->prevDate($today, $dows, $used, $i * 7 + 7);
                if (! $date) {
                    continue;
                }
                $used[] = $date->toDateString();
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => 'homework',
                    'title' => $homeworks[$i % count($homeworks)],
                    'scheduled_date' => $date->toDateString(),
                ]);
            }

            $testDate = $this->prevDate($today, $dows, $used, 21);
            if ($testDate) {
                $used[] = $testDate->toDateString();
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => 'test',
                    'title' => self::TEST_TITLES[$lesson->id % count(self::TEST_TITLES)],
                    'scheduled_date' => $testDate->toDateString(),
                ]);
            }

            for ($i = 0; $i < 2; $i++) {
                $date = $this->nextDate($today, $dows, $used, $i * 7 + 7);
                if (! $date) {
                    continue;
                }
                $used[] = $date->toDateString();
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => 'homework',
                    'title' => $homeworks[($i + 3) % count($homeworks)],
                    'scheduled_date' => $date->toDateString(),
                ]);
            }

            $futureTest = $this->nextDate($today, $dows, $used, 14);
            if ($futureTest) {
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => 'test',
                    'title' => self::TEST_TITLES[($lesson->id + 2) % count(self::TEST_TITLES)],
                    'scheduled_date' => $futureTest->toDateString(),
                ]);
            }
        }
    }

    private function prevDate(Carbon $from, Collection $dows, array $used, int $daysBack): ?Carbon
    {
        $date = $from->copy()->subDays($daysBack);
        for ($i = 0; $i < 30; $i++, $date->subDay()) {
            if ($dows->contains($date->dayOfWeekIso) && ! in_array($date->toDateString(), $used)) {
                return $date->copy();
            }
        }

        return null;
    }

    private function nextDate(Carbon $from, Collection $dows, array $used, int $daysAhead): ?Carbon
    {
        $date = $from->copy()->addDays($daysAhead);
        for ($i = 0; $i < 30; $i++, $date->addDay()) {
            if ($dows->contains($date->dayOfWeekIso) && ! in_array($date->toDateString(), $used)) {
                return $date->copy();
            }
        }

        return null;
    }
}
