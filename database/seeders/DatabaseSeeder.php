<?php

/** @noinspection D */

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\LessonNote;
use App\Models\Lesson;
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
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['101', '102', '103', '201', '202', '203', '301', '302', '303'];

        $adminSaintJoseph = User::factory()->create([
            'name' => 'Admin Saint-Joseph',
            'email' => 'admin.sj@example.com',
            'password' => 'password',
        ]);

        $adminAthenee = User::factory()->create([
            'name' => 'Admin Athénée',
            'email' => 'admin.ar@example.com',
            'password' => 'password',
        ]);

        $profAnglais = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $profMaths = User::factory()->create([
            'name' => 'Marie Dupont',
            'email' => 'prof.maths@example.com',
            'password' => 'password',
        ]);

        $profFrancais = User::factory()->create([
            'name' => 'Pierre Martin',
            'email' => 'prof.francais@example.com',
            'password' => 'password',
        ]);

        $profNl = User::factory()->create([
            'name' => 'Sophie Leclercq',
            'email' => 'prof.nl@example.com',
            'password' => 'password',
        ]);

        $teachers = collect([$profAnglais, $profMaths, $profFrancais, $profNl]);
        $academicYear = AcademicYear::create(['year' => '2025-2026']);
        $schools = collect([
            ['name' => 'Institut Saint-Joseph', 'slug' => 'saint-joseph'],
            ['name' => 'Athénée Royal de Bruxelles', 'slug' => 'athenee-royal-bruxelles'],
        ])->map(fn ($data) => School::create($data));

        $saintJoseph = $schools->firstWhere('slug', 'saint-joseph');
        $athenee = $schools->firstWhere('slug', 'athenee-royal-bruxelles');

        $saintJoseph->users()->attach($adminSaintJoseph->id, ['role' => 'admin']);
        $athenee->users()->attach($adminAthenee->id, ['role' => 'admin']);
        $schools->each(fn (School $s) => $teachers->each(
            fn (User $t) => $s->users()->attach($t->id, ['role' => 'teacher'])
        ));
        $subjects = collect([
            'Anglais', 'Néerlandais', 'Mathématiques', 'Français', 'Sciences',
            'Biologie', 'Physique', 'Chimie', 'Sciences humaines',
            'Éducation physique', 'Arts', 'Histoire', 'Géographie', 'Morale',
        ])->map(fn ($name) => Subject::create(['name' => $name]));

        $schools->each(fn (School $s) => $s->subjects()->attach($subjects->pluck('id')));
        $schools->each(fn (School $s) => $s->academicYears()->attach($academicYear->id));

        // ── Matières enseignées par chaque prof ───────────────────────────────
        $anglais = $subjects->firstWhere('name', 'Anglais');
        $neerlandais = $subjects->firstWhere('name', 'Néerlandais');
        $maths = $subjects->firstWhere('name', 'Mathématiques');
        $francais = $subjects->firstWhere('name', 'Français');
        $sciences = $subjects->firstWhere('name', 'Sciences');

        $teacherSubjects = [
            $profAnglais->id => [$anglais],
            $profNl->id => [$neerlandais],
            $profMaths->id => [$maths, $sciences],
            $profFrancais->id => [$francais],
        ];

        $slotLabels = [
            '1ère heure', '2e heure', '3e heure', '4e heure',
            '5e heure', '6e heure', '7e heure', '8e heure', '9e heure', '10e heure',
        ];

        $slots = collect();
        $startMinutes = 8 * 60 + 30;
        foreach ($slotLabels as $i => $label) {
            $endMinutes = $startMinutes + 50;
            $slots->push(ScheduleSlot::create([
                'position'   => $i + 1,
                'label'      => $label,
                'type'       => 'slot',
                'start_time' => sprintf('%02d:%02d', intdiv($startMinutes, 60), $startMinutes % 60),
                'end_time'   => sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60),
            ]));
            $startMinutes = $endMinutes;
        }

        $schedulesByTeacherSchool = [];
        foreach ($teachers as $teacher) {
            foreach ($schools as $school) {
                $schedulesByTeacherSchool[$teacher->id][$school->id] = Schedule::create([
                    'user_id' => $teacher->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                ]);
            }
        }
        $makeGroup = function (string $grade, string $name, School $school) use ($academicYear) {
            $group = Group::create([
                'name' => $name,
                'grade' => $grade,
                'slug' => Str::slug("{$school->slug}-{$grade}-{$name}"),
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
            ]);
            $students = Student::factory(15)->create(['school_id' => $school->id]);
            $group->students()->attach($students->pluck('id'));

            return $group;
        };

        $sj3A = $makeGroup('3', 'A', $saintJoseph);
        $sj3B = $makeGroup('3', 'B', $saintJoseph);
        $sj4A = $makeGroup('4', 'A', $saintJoseph);
        $sj2A = $makeGroup('2', 'A', $saintJoseph);

        $ar3A = $makeGroup('3', 'A', $athenee);
        $ar1B = $makeGroup('1', 'B', $athenee);
        $ar2C = $makeGroup('2', 'C', $athenee);
        $ar1D = $makeGroup('1', 'D', $athenee);

        $sjGroups = collect([$sj3A, $sj3B, $sj4A, $sj2A]);
        $arGroups = collect([$ar3A, $ar1B, $ar2C, $ar1D]);

        $lessonData = [];

        foreach ([$saintJoseph->id => $sjGroups, $athenee->id => $arGroups] as $schoolId => $groups) {
            foreach ($groups as $group) {
                foreach ($teacherSubjects as $teacherId => $subjectList) {
                    $teacher = $teachers->firstWhere('id', $teacherId);

                    foreach ($subjectList as $subject) {
                        $lesson = Lesson::create([
                            'name' => $subject->name,
                            'group_id' => $group->id,
                            'subject_id' => $subject->id,
                        ]);
                        $lesson->users()->attach($teacherId);

                        $lessonData[] = [
                            'lesson' => $lesson,
                            'teacher' => $teacher,
                            'school_id' => $schoolId,
                        ];
                    }
                }
            }
        }
        $usedSlotDays = [];

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $teacher = $data['teacher'];
            $schedule = $schedulesByTeacherSchool[$teacher->id][$data['school_id']];

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
                    'classroom' => fake()->randomElement($rooms),
                ]);

                $assigned++;
            }
        }
        $today = Carbon::today();
        $startDate = $today->copy()->subWeeks(8)->startOfWeek(); // lundi il y a 8 semaines

        $journalNotes = [
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

        $homeworkTitles = [
            'Anglais' => [
                'Vocabulaire p. %d–%d', 'Grammar ex. %d à %d', 'Read and summarize ch. %d', 'Listening worksheet',
            ],
            'Néerlandais' => [
                'Woordenschat p. %d', 'Grammatica oefeningen %d–%d', 'Tekst lezen h. %d', 'Schrijfoefening',
            ],
            'Mathématiques' => [
                'Ex. p. %d n° %d à %d', 'Série d\'exercices – algèbre', 'Problèmes p. %d', 'Fiche de révision',
            ],
            'Sciences' => ['Résumé ch. %d', 'Fiche d\'observation', 'Questions p. %d–%d', 'Schéma à compléter'],
            'Français' => ['Rédaction : %s', 'Analyse de texte p. %d', 'Conjugaison – fiche %d', 'Lecture ch. %d'],
        ];

        $testTitles = [
            'Interrogation – Chapitre %d',
            'Test récapitulatif',
            'Contrôle de connaissances',
            'Mini-test vocabulaire',
            'Interrogation surprise',
            'Évaluation formative',
        ];

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $lesson->load(['scheduleEntries', 'group.students']);

            $students = $lesson->group->students;
            $dows = $lesson->scheduleEntries->pluck('day_of_week');

            if ($dows->isEmpty() || $students->isEmpty()) {
                continue;
            }
            $date = $startDate->copy();
            while ($date->lt($today)) {
                if ($dows->contains($date->dayOfWeekIso)) {
                    $session = ClassSession::create([
                        'lesson_id' => $lesson->id,
                        'date'      => $date->toDateString(),
                    ]);

                    if (fake()->boolean(40)) {
                        LessonNote::create([
                            'lesson_id' => $lesson->id,
                            'date'      => $date->toDateString(),
                            'notes'     => fake()->randomElement($journalNotes),
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
                            'motive' => ($type === 'Absent' && fake()->boolean(30))
                                ? fake()->sentence()
                                : null,
                        ]);
                    }
                }

                $date->addDay();
            }

            $subjectName = $lesson->name;
            $assignCount = fake()->numberBetween(2, 3);
            $usedFutureDates = [];

            for ($i = 0; $i < $assignCount; $i++) {
                $futureDate = $today->copy();
                $found = false;

                for ($j = 1; $j <= 42; $j++) {
                    $futureDate->addDay();
                    $ds = $futureDate->toDateString();

                    if ($dows->contains($futureDate->dayOfWeekIso) && ! in_array($ds, $usedFutureDates)) {
                        $usedFutureDates[] = $ds;
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    continue;
                }

                $isTest = fake()->boolean(30);

                if ($isTest) {
                    $title = sprintf(
                        fake()->randomElement($testTitles),
                        fake()->numberBetween(1, 12),
                    );
                } else {
                    $templates = $homeworkTitles[$subjectName] ?? ['Exercices p. %d', 'Révisions ch. %d'];
                    $template = fake()->randomElement($templates);
                    $title = sprintf(
                        $template,
                        fake()->numberBetween(10, 180),
                        fake()->numberBetween(1, 10),
                        fake()->numberBetween(5, 20),
                        fake()->sentence(3),
                    );
                }

                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => $isTest ? 'test' : 'homework',
                    'title' => $title,
                    'scheduled_date' => $futureDate->toDateString(),
                    'description' => fake()->boolean(30) ? fake()->sentence() : null,
                ]);
            }
        }
    }
}
