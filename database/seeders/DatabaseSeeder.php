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
use App\Models\SchoolSlotTime;
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
    private const ROOMS = ['101', '102', '103', '201', '202', '203', '301', '302', '303', 'Labo 1', 'Labo 2', 'Salle informatique', 'Gymnase'];

    private const JOURNAL_NOTES = [
        'Langue française' => [
            'Lecture analytique du chapitre 3 du roman. Discussion sur les procédés narratifs.',
            'Exercices de conjugaison au subjonctif. Quelques confusions avec le conditionnel.',
            'Rédaction en classe : récit autobiographique. Bonne implication de la plupart des élèves.',
            'Correction collective de la dissertation. Points positifs : argumentation. À revoir : structure.',
            'Étude d\'un extrait de Molière. Analyse des registres comique et satirique.',
            'Travail sur les figures de style. Bonne participation, exercices corrigés ensemble.',
            'Dictée suivie d\'une mise en commun. Résultats mitigés, mots invariables à retravailler.',
            'Introduction à la poésie symboliste. Lecture de Verlaine et Rimbaud.',
            'Analyse d\'un discours politique. Travail sur l\'implicite et l\'argument.',
            'Atelier d\'écriture : poème en prose. Très bonne créativité.',
        ],
        'Mathématiques' => [
            'Introduction aux fonctions du second degré. Bonne compréhension générale.',
            'Exercices sur les équations du second degré. Plusieurs élèves en difficulté avec le discriminant.',
            'Correction de la série d\'exercices. Points bloquants identifiés et retravaillés.',
            'Démonstration du théorème de Pythagore. Exercices d\'application en binômes.',
            'Révisions pour l\'interrogation. Questions pertinentes sur les intégrales.',
            'Travail sur les probabilités conditionnelles. Ambiance sérieuse.',
            'Introduction à la trigonométrie. Schémas au tableau pour visualiser.',
            'Fiche de révision algèbre. Quelques élèves absents lors de la notion précédente à rattraper.',
            'Exercices de géométrie analytique. Bon rythme de travail.',
            'Correction de l\'évaluation. Analyse des erreurs fréquentes.',
        ],
        'Langue anglaise' => [
            'Reading comprehension on climate change. Good level of discussion.',
            'Grammar focus: past perfect vs. simple past. Common mistakes corrected.',
            'Listening exercise with British accents. Several students struggled.',
            'Oral presentations (3 students). Good preparation, clear improvement since last term.',
            'Vocabulary on current affairs. Interactive quiz at the end of class.',
            'Writing workshop: opinion essay structure. Peer correction exercise.',
            'Film clip discussion — "The Social Dilemma". Lively debate about social media.',
            'Review for upcoming test. Students asked good questions.',
            'Role-play: job interview scenarios. Fun and productive session.',
            'Reading: short story. Analysed narrative perspective and tone.',
        ],
        'Langue néerlandaise' => [
            'Woordenschat hoofdstuk 5. Oefeningen individueel en daarna in groep.',
            'Luisteroefening: nieuwsfragment. Resultaten wisselend, uitspraak verbeteren.',
            'Grammatica: de/het-woorden. Klassikale correctie van de fouten.',
            'Spreekvaardigheid: dialoogjes over dagelijks leven. Goede sfeer in de klas.',
            'Schrijfoefening: een e-mail schrijven. Meeste leerlingen redden zich goed.',
            'Herhaling voor de toets. Enkele leerlingen kwamen met gerichte vragen.',
            'Nieuwe tekst: toerisme in Nederland. Bespreking van nieuwe woordenschat.',
            'Dictee en verbetering. Werkwoordspelling blijft een aandachtspunt.',
            'Rollenspel: aan de kassa in de supermarkt. Luchtige sfeer, goed resultaat.',
            'Leesbegrip: artikel over klimaat. Samenvatting individueel gemaakt.',
        ],
        'Sciences' => [
            'Observation de cellules végétales au microscope. Très bon engagement des élèves.',
            'Cours sur la mitose et la méiose. Schémas complétés ensemble au tableau.',
            'TP : extraction d\'ADN d\'une banane. Séance très appréciée.',
            'Introduction à l\'écosystème. Discussion sur la biodiversité locale.',
            'Correction du compte-rendu de TP. Présentation des résultats à améliorer.',
            'Vidéo documentaire sur le changement climatique. Questions pertinentes.',
            'Révisions pour l\'évaluation. Fiche synthèse distribuée.',
            'Dissection d\'un cœur de bœuf. Bonne implication malgré quelques réticences.',
            'Étude des chaînes alimentaires. Schéma complété collectivement.',
            'TP photosynthèse : mesure de production d\'O2. Résultats cohérents.',
        ],
        'Histoire' => [
            'Étude de la Première Guerre mondiale : causes et déclenchement.',
            'Analyse de documents sur la Shoah. Moment solennel et respectueux.',
            'Cours sur la Révolution industrielle. Lien avec le monde contemporain discuté.',
            'Correction de l\'analyse de carte historique. Méthode à retravailler.',
            'Exposés sur les régimes totalitaires. Bonne qualité d\'ensemble.',
            'Introduction à la guerre froide. Repères chronologiques mis en place.',
            'Lecture d\'un témoignage de résistant. Fort impact sur la classe.',
            'Révision générale pour l\'examen de fin de trimestre.',
            'Étude de cas : la crise de Cuba. Travail en groupes, très actif.',
            'Introduction à la décolonisation. Bonne participation.',
        ],
        'default' => [
            'Introduction du chapitre. Exercices corrigés collectivement.',
            'Retour sur les notions vues la semaine passée. Quelques difficultés signalées.',
            'Exposé préparé par les élèves. Bonne participation générale.',
            'Révisions pour l\'interrogation. Ambiance studieuse.',
            'Correction du devoir maison. Questions pertinentes.',
            'Travail en groupes. Résultats satisfaisants dans l\'ensemble.',
            'Début d\'une nouvelle séquence. Bon démarrage.',
            'Exercices individuels. Plusieurs élèves en difficulté sur la notion.',
            'Visionnage d\'un document + questions. Très bon engagement de la classe.',
            'Mise en commun des travaux de recherche.',
        ],
    ];

    private const HOMEWORK_TITLES = [
        'Langue néerlandaise' => ['Woordenschat h. %d p. %d', 'Grammatica oefeningen %d–%d', 'Tekst lezen h. %d', 'Schrijfoefening p. %d'],
        'Langue anglaise' => ['Vocabulary p. %d–%d', 'Grammar exercises %d to %d', 'Read and summarize ch. %d', 'Listening worksheet %d'],
        'Langue allemande' => ['Wortschatz S. %d', 'Grammatik Übungen %d–%d', 'Text lesen Kap. %d', 'Schreibübung S. %d'],
        'Langue française' => ['Rédaction p. %d', 'Analyse de texte p. %d', 'Conjugaison – fiche %d', 'Lecture ch. %d à %d'],
        'Mathématiques' => ['Ex. p. %d n° %d à %d', 'Série algèbre p. %d', 'Problèmes p. %d', 'Fiche de révision %d'],
        'Sciences' => ['Résumé ch. %d', 'Fiche d\'observation p. %d', 'Questions p. %d–%d', 'Schéma à compléter ch. %d'],
        'Physique' => ['Ex. p. %d n° %d à %d', 'Problèmes – ch. %d', 'Fiche de lois ch. %d', 'Exercices de conversion p. %d'],
        'Chimie' => ['Équations à équilibrer p. %d', 'Exercices – ch. %d', 'Fiche nomenclature %d', 'Questions p. %d–%d'],
        'Biologie' => ['Résumé ch. %d', 'Schéma à légender p. %d', 'Questions p. %d–%d', 'Compte-rendu TP %d'],
        'Histoire' => ['Résumé du cours – ch. %d', 'Analyse de document p. %d', 'Frise chronologique %d', 'Questions p. %d–%d'],
        'Géographie' => ['Croquis ch. %d', 'Questions p. %d–%d', 'Analyse de carte p. %d', 'Résumé ch. %d'],
    ];

    private const TEST_TITLES = [
        'Interrogation – Chapitre %d',
        'Test récapitulatif – unité %d',
        'Contrôle de connaissances',
        'Mini-test vocabulaire',
        'Interrogation surprise',
        'Évaluation formative – ch. %d',
        'Test de compétences',
        'Interrogation orale',
    ];

    // Horaire FWB réaliste : périodes 55 min, récré 10 min, repas 60 min
    private const SLOT_TIMES = [
        1  => ['08:30', '09:25'],
        2  => ['09:25', '10:20'],
        3  => ['10:30', '11:25'],
        4  => ['11:25', '12:20'],
        5  => ['13:20', '14:15'],
        6  => ['14:15', '15:10'],
        7  => ['15:20', '16:15'],
        8  => ['16:15', '17:10'],
        9  => ['17:20', '18:15'],
        10 => ['18:15', '19:10'],
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
        $profSciences = $this->createUser('Luc Renard', 'prof.sciences@example.com');
        $profHistoire = $this->createUser('Nathalie Bodart', 'prof.histoire@example.com');

        $teachers = collect([$profAnglais, $profMaths, $profFrancais, $profNl, $profSciences, $profHistoire]);

        // ── Schools & academic years ───────────────────────────────────────────
        $academicYear = AcademicYear::create(['year' => '2025-2026']);
        $archivedYear1 = AcademicYear::create(['year' => '2024-2025']);
        $archivedYear2 = AcademicYear::create(['year' => '2023-2024']);

        $saintJoseph = School::create(['name' => 'Institut Saint-Joseph', 'slug' => 'saint-joseph']);
        $athenee = School::create(['name' => 'Athénée Royal de Bruxelles', 'slug' => 'athenee-royal-bruxelles']);

        $saintJoseph->users()->attach($adminSj->id, ['role' => 'admin']);
        $athenee->users()->attach($adminAr->id, ['role' => 'admin']);

        foreach ([$saintJoseph, $athenee] as $school) {
            $school->academicYears()->attach($academicYear->id, AcademicYear::defaultDates($academicYear->year));
            $school->academicYears()->attach($archivedYear1->id, array_merge(
                AcademicYear::defaultDates($archivedYear1->year),
                ['archived_at' => now()],
            ));
            $school->academicYears()->attach($archivedYear2->id, array_merge(
                AcademicYear::defaultDates($archivedYear2->year),
                ['archived_at' => now()],
            ));
            $teachers->each(fn (User $t) => $school->users()->attach($t->id, ['role' => 'teacher']));
        }

        // ── Subjects ──────────────────────────────────────────────────────────
        $languageNames = ['Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande'];

        $subjects = collect([
            'Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande',
            'Mathématiques', 'Sciences', 'Biologie', 'Physique', 'Chimie',
            'Histoire', 'Géographie', 'Éducation physique',
            'Philosophie et citoyenneté', 'Économie', 'Sciences informatiques',
            'Arts plastiques', 'Latin',
        ])->map(fn (string $name) => Subject::create([
            'name' => $name,
            'is_language' => in_array($name, $languageNames),
        ]));

        foreach ([$saintJoseph, $athenee] as $school) {
            $school->subjects()->attach($subjects->pluck('id'));
        }

        // ── Teacher subject preferences ───────────────────────────────────────
        $teacherSubjectNames = [
            $profAnglais->id => ['Langue anglaise', 'Langue allemande'],
            $profNl->id => ['Langue néerlandaise', 'Langue anglaise'],
            $profMaths->id => ['Mathématiques', 'Sciences', 'Physique', 'Chimie'],
            $profFrancais->id => ['Langue française', 'Latin'],
            $profSciences->id => ['Sciences', 'Biologie', 'Chimie'],
            $profHistoire->id => ['Histoire', 'Géographie', 'Philosophie et citoyenneté'],
        ];

        foreach ($teacherSubjectNames as $teacherId => $names) {
            $teachers->firstWhere('id', $teacherId)
                ->subjects()
                ->attach($subjects->whereIn('name', $names)->pluck('id'));
        }

        // ── Lesson definitions ────────────────────────────────────────────────
        $lessonSubjectResolved = [
            $profAnglais->id => [[$subjects->firstWhere('name', 'Langue anglaise'), 1]],
            $profNl->id => [[$subjects->firstWhere('name', 'Langue néerlandaise'), null]],
            $profMaths->id => [[$subjects->firstWhere('name', 'Mathématiques'), null]],
            $profFrancais->id => [[$subjects->firstWhere('name', 'Langue française'), null]],
            $profSciences->id => [[$subjects->firstWhere('name', 'Sciences'), null]],
            $profHistoire->id => [[$subjects->firstWhere('name', 'Histoire'), null]],
        ];

        // ── Schedule slots & school slot times ────────────────────────────────
        $slots = $this->createScheduleSlots();

        foreach ([$saintJoseph, $athenee] as $school) {
            foreach ($slots as $slot) {
                $times = self::SLOT_TIMES[$slot->position] ?? null;
                if ($times) {
                    SchoolSlotTime::create([
                        'school_id' => $school->id,
                        'schedule_slot_id' => $slot->id,
                        'start_time' => $times[0],
                        'end_time' => $times[1],
                    ]);
                }
            }
        }

        // ── Student cohorts ───────────────────────────────────────────────────
        $sjA = Student::factory(22)->create(['school_id' => $saintJoseph->id]);
        $sjB = Student::factory(21)->create(['school_id' => $saintJoseph->id]);
        $sjC = Student::factory(19)->create(['school_id' => $saintJoseph->id]);
        $sjD = Student::factory(23)->create(['school_id' => $saintJoseph->id]);
        $sjE = Student::factory(24)->create(['school_id' => $saintJoseph->id]);

        $arA = Student::factory(25)->create(['school_id' => $athenee->id]);
        $arB = Student::factory(20)->create(['school_id' => $athenee->id]);
        $arC = Student::factory(22)->create(['school_id' => $athenee->id]);
        $arD = Student::factory(18)->create(['school_id' => $athenee->id]);

        // ── 2023-2024 (archived year 2) ───────────────────────────────────────
        $sjGroupsY2 = collect([
            $this->makeGroup('3', 'A', $saintJoseph, $archivedYear2, $sjA),
            $this->makeGroup('2', 'B', $saintJoseph, $archivedYear2, $sjB),
            $this->makeGroup('5', 'C', $saintJoseph, $archivedYear2, $sjC),
        ]);

        $arGroupsY2 = collect([
            $this->makeGroup('1', 'A', $athenee, $archivedYear2, $arA),
            $this->makeGroup('4', 'B', $athenee, $archivedYear2, $arB),
        ]);

        $lessonDataY2 = $this->buildLessonData(
            [$saintJoseph->id => $sjGroupsY2, $athenee->id => $arGroupsY2],
            $lessonSubjectResolved,
            $teachers,
        );

        $schedulesY2 = $this->buildSchedules($archivedYear2, $teachers, [$saintJoseph, $athenee]);
        $this->seedScheduleEntries($lessonDataY2, $schedulesY2, $slots);

        $endY2 = Carbon::parse('2024-06-30');
        $this->seedSessions($lessonDataY2, Carbon::parse('2024-02-05'), $endY2);
        $this->seedAssignments($lessonDataY2, $endY2, includeUpcoming: false);

        // ── 2024-2025 (archived year 1) ───────────────────────────────────────
        $sjGroupsY1 = collect([
            $this->makeGroup('4', 'A', $saintJoseph, $archivedYear1, $sjA),
            $this->makeGroup('3', 'B', $saintJoseph, $archivedYear1, $sjB),
            $this->makeGroup('6', 'C', $saintJoseph, $archivedYear1, $sjC), // dernière année pour sjC
            $this->makeGroup('1', 'D', $saintJoseph, $archivedYear1, $sjD),
        ]);

        $arGroupsY1 = collect([
            $this->makeGroup('2', 'A', $athenee, $archivedYear1, $arA),
            $this->makeGroup('5', 'B', $athenee, $archivedYear1, $arB),
            $this->makeGroup('1', 'C', $athenee, $archivedYear1, $arC),
        ]);

        $lessonDataY1 = $this->buildLessonData(
            [$saintJoseph->id => $sjGroupsY1, $athenee->id => $arGroupsY1],
            $lessonSubjectResolved,
            $teachers,
            maxGroupsTotal: 5,
        );

        $schedulesY1 = $this->buildSchedules($archivedYear1, $teachers, [$saintJoseph, $athenee]);
        $this->seedScheduleEntries($lessonDataY1, $schedulesY1, $slots);

        $endY1 = Carbon::parse('2025-06-30');
        $this->seedSessions($lessonDataY1, Carbon::parse('2024-09-02'), $endY1);
        $this->seedAssignments($lessonDataY1, $endY1, includeUpcoming: false);

        // ── 2025-2026 (current year) ──────────────────────────────────────────
        $sjGroups = collect([
            $this->makeGroup('5', 'A', $saintJoseph, $academicYear, $sjA),
            $this->makeGroup('4', 'B', $saintJoseph, $academicYear, $sjB),
            // sjC a terminé ses études après 2024-2025
            $this->makeGroup('2', 'D', $saintJoseph, $academicYear, $sjD),
            $this->makeGroup('1', 'E', $saintJoseph, $academicYear, $sjE),
        ]);

        $arGroups = collect([
            $this->makeGroup('3', 'A', $athenee, $academicYear, $arA),
            $this->makeGroup('6', 'B', $athenee, $academicYear, $arB), // dernière année pour arB
            $this->makeGroup('2', 'C', $athenee, $academicYear, $arC),
            $this->makeGroup('1', 'D', $athenee, $academicYear, $arD),
        ]);

        $lessonData = $this->buildLessonData(
            [$saintJoseph->id => $sjGroups, $athenee->id => $arGroups],
            $lessonSubjectResolved,
            $teachers,
            maxGroupsTotal: 5,
        );

        $schedules = $this->buildSchedules($academicYear, $teachers, [$saintJoseph, $athenee]);
        $this->seedScheduleEntries($lessonData, $schedules, $slots);

        $today = Carbon::today();
        $this->seedSessions($lessonData, Carbon::parse('2025-09-01'), $today);
        $this->seedAssignments($lessonData, $today, includeUpcoming: true);
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

        foreach (self::SLOT_TIMES as $position => [$start, $end]) {
            $slots->push(ScheduleSlot::create([
                'position' => $position,
                'label' => $position === 1 ? '1ère heure' : "{$position}e heure",
                'type' => 'slot',
                'start_time' => $start,
                'end_time' => $end,
            ]));
        }

        return $slots;
    }

    private function makeGroup(string $grade, string $name, School $school, AcademicYear $year, Collection $students): Group
    {
        $group = Group::create([
            'grade' => $grade,
            'name' => $name,
            'slug' => Str::slug("{$school->slug}-{$year->year}-{$grade}-{$name}"),
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
        ]);

        $group->students()->attach($students->pluck('id'));

        return $group;
    }

    /**
     * @param  array<int, Collection<int, Group>>  $schoolGroups
     * @param  array<int, list<array{0: Subject, 1: int|null}>>  $lessonSubjects
     */
    private function buildLessonData(array $schoolGroups, array $lessonSubjects, Collection $teachers, int $maxGroupsTotal = PHP_INT_MAX): Collection
    {
        $lessonData = collect();

        // Interleave groups from all schools: SJ1, AR1, SJ2, AR2, ...
        $interleavedGroups = collect();
        $schoolIds = array_keys($schoolGroups);
        $maxLen = max(array_map(fn ($g) => $g->count(), array_values($schoolGroups)));
        for ($i = 0; $i < $maxLen; $i++) {
            foreach ($schoolIds as $sid) {
                $group = $schoolGroups[$sid]->get($i);
                if ($group !== null) {
                    $interleavedGroups->push(['school_id' => $sid, 'group' => $group]);
                }
            }
        }

        // Each teacher gets at most $maxGroupsTotal groups across ALL schools combined
        foreach ($lessonSubjects as $teacherId => $subjectList) {
            $groupCount = 0;
            foreach ($interleavedGroups as ['school_id' => $schoolId, 'group' => $group]) {
                if ($groupCount >= $maxGroupsTotal) {
                    break;
                }
                foreach ($subjectList as [$subject, $lmLevel]) {
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

                $groupCount++;
            }
        }

        return $lessonData;
    }

    /** @param list<School> $schools */
    private function buildSchedules(AcademicYear $year, Collection $teachers, array $schools): Collection
    {
        $schedules = collect();

        foreach ($teachers as $teacher) {
            foreach ($schools as $school) {
                $schedules->put("{$teacher->id}_{$school->id}", Schedule::create([
                    'user_id' => $teacher->id,
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                ]));
            }
        }

        return $schedules;
    }

    private function seedScheduleEntries(Collection $lessonData, Collection $schedules, Collection $slots): void
    {
        $usedSlotDays = [];

        // Distribute exactly 22 periods per teacher across all their lessons
        $lessonsByTeacher = $lessonData->groupBy(fn ($d) => $d['teacher']->id);

        foreach ($lessonsByTeacher as $lessonGroup) {
            $lessonCount = $lessonGroup->count();
            $base = intdiv(22, $lessonCount);
            $extra = 22 % $lessonCount;

            foreach ($lessonGroup->values() as $i => $data) {
                $target = $base + ($i < $extra ? 1 : 0);
                $lesson = $data['lesson'];
                $teacher = $data['teacher'];
                $schedule = $schedules->get("{$teacher->id}_{$data['school_id']}");
                $assigned = 0;
                $attempts = 0;

                while ($assigned < $target && $attempts < 200) {
                    $attempts++;
                    $slot = $slots->random();
                    $day = fake()->numberBetween(1, 5);
                    $key = "{$teacher->id}_{$slot->id}_{$day}";

                    if (isset($usedSlotDays[$key])) {
                        continue;
                    }

                    $usedSlotDays[$key] = true;

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
    }

    private function seedSessions(Collection $lessonData, Carbon $from, Carbon $to): void
    {
        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $lesson->load(['scheduleEntries', 'group.students']);

            $dows = $lesson->scheduleEntries->pluck('day_of_week');
            $students = $lesson->group->students;

            if ($dows->isEmpty() || $students->isEmpty()) {
                continue;
            }

            $date = $from->copy()->startOfWeek();
            while ($date->lt($to)) {
                if ($dows->contains($date->dayOfWeekIso) && ! fake()->boolean(4)) {
                    $this->createSessionWithAttendance($lesson, $date, $students, $data['subject_name']);
                }
                $date->addDay();
            }
        }
    }

    private function createSessionWithAttendance(Lesson $lesson, Carbon $date, Collection $students, string $subjectName): void
    {
        $session = ClassSession::create([
            'lesson_id' => $lesson->id,
            'date' => $date->toDateString(),
        ]);

        if (fake()->boolean(45)) {
            $pool = self::JOURNAL_NOTES[$subjectName] ?? self::JOURNAL_NOTES['default'];
            LessonNote::create([
                'lesson_id' => $lesson->id,
                'date' => $date->toDateString(),
                'notes' => fake()->randomElement($pool),
            ]);
        }

        $attendance = Attendance::create([
            'classsession_id' => $session->id,
            'validated_at' => $date->copy()
                ->setHour(fake()->numberBetween(9, 17))
                ->setMinute(fake()->randomElement([0, 15, 30, 45])),
        ]);

        foreach ($students as $student) {
            $roll = fake()->numberBetween(1, 1000);

            if ($roll <= 940) {
                continue;
            }

            $type = match (true) {
                $roll <= 980 => 'Absent',
                $roll <= 995 => 'Late',
                default => 'Excluded',
            };

            StudentAttendanceStatus::create([
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'type' => $type,
                'motive' => ($type === 'Absent' && fake()->boolean(40)) ? fake()->sentence() : null,
            ]);
        }
    }

    private function seedAssignments(Collection $lessonData, Carbon $referenceDate, bool $includeUpcoming): void
    {
        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $subjectName = $data['subject_name'];
            $dows = $lesson->scheduleEntries->pluck('day_of_week');

            if ($dows->isEmpty()) {
                continue;
            }

            $usedDates = [];

            $pastCount = fake()->numberBetween(4, 8);
            for ($i = 0; $i < $pastCount; $i++) {
                $date = $this->prevSessionDate($referenceDate, $dows, $usedDates);
                if (! $date) {
                    continue;
                }
                $usedDates[] = $date->toDateString();
                $isTest = fake()->boolean(30);
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => $isTest ? 'test' : 'homework',
                    'title' => $this->generateAssignmentTitle($subjectName, $isTest),
                    'scheduled_date' => $date->toDateString(),
                    'description' => fake()->boolean(40) ? fake()->sentence(fake()->numberBetween(5, 15)) : null,
                ]);
            }

            if (! $includeUpcoming) {
                continue;
            }

            $futureCount = fake()->numberBetween(2, 4);
            for ($i = 0; $i < $futureCount; $i++) {
                $date = $this->nextSessionDate($referenceDate, $dows, $usedDates);
                if (! $date) {
                    continue;
                }
                $usedDates[] = $date->toDateString();
                $isTest = fake()->boolean(25);
                Assignment::create([
                    'lesson_id' => $lesson->id,
                    'created_by' => $data['teacher']->id,
                    'type' => $isTest ? 'test' : 'homework',
                    'title' => $this->generateAssignmentTitle($subjectName, $isTest),
                    'scheduled_date' => $date->toDateString(),
                    'description' => fake()->boolean(40) ? fake()->sentence(fake()->numberBetween(5, 15)) : null,
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

        for ($i = 1; $i <= 84; $i++) {
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
            fake()->numberBetween(10, 200),
            fake()->numberBetween(1, 15),
            fake()->numberBetween(5, 25),
            fake()->sentence(3),
        );
    }
}
