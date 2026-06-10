<?php

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
use App\Models\SchoolJoinRequest;
use App\Models\SchoolSlotTime;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    // ── Static data ────────────────────────────────────────────────────────────

    private const SLOT_TIMES = [
        1 => ['08:30', '09:20'],
        2 => ['09:20', '10:10'],
        3 => ['10:20', '11:10'],
        4 => ['11:10', '12:00'],
        5 => ['12:00', '12:50'],
        6 => ['12:50', '13:40'],
        7 => ['13:40', '14:30'],
        8 => ['14:30', '15:20'],
        9 => ['15:20', '16:10'],
        10 => ['16:10', '17:00'],
    ];

    private const ROOMS = [
        '101', '102', '103', '201', '202', '203', '301', '302', '303',
        'Labo 1', 'Labo 2', 'Salle informatique', 'Gymnase',
    ];

    private const SCHOOLS_CONFIG = [
        [
            'slug' => 'saint-lambert',
            'domain' => 'sl',
            'name' => 'Institut Saint-Lambert',
            'admin_email' => 'admin@sl.example.com',
            'admin_name' => 'Admin Saint-Lambert',
        ],
        [
            'slug' => 'athenee-cite',
            'domain' => 'ar',
            'name' => 'Athénée Royal de la Cité',
            'admin_email' => 'admin@ar.example.com',
            'admin_name' => 'Admin AR Cité',
        ],
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

    // [email_prefix, display_name, [[subject_name, grades_or_null, hours_per_group]]]
    // grades_or_null: int[] of grades (1-6) covered, null = all 12 groups
    // hours_per_group: schedule entries created per lesson (4 / 2 / 1)
    // Constraint: sum of (groups × hours) ≤ 22h (teacher only teaches in one school)
    private const SCHOOL_TEACHERS = [
        'saint-lambert' => [
            ['anne.masson',     'Anne Masson',     [['Langue française',           [1, 2],       4]]],   // 16h
            ['benoit.gilles',   'Benoît Gilles',   [['Langue française',           [3, 4],       4]]],   // 16h
            ['claire.dupont',   'Claire Dupont',   [['Langue française',           [5, 6],       4]]],   // 16h
            ['david.lecomte',   'David Lecomte',   [['Mathématiques',              [1, 2],       4]]],   // 16h
            ['elodie.fontaine', 'Élodie Fontaine', [['Mathématiques',              [3, 4],       4]]],   // 16h
            ['frederic.ernst',  'Frédéric Ernst',  [['Mathématiques',              [5, 6],       4]]],   // 16h
            ['geert.peeters',   'Geert Peeters',   [['Langue néerlandaise',        [1, 2],       4]]],   // 16h
            ['helene.bodart',   'Hélène Bodart',   [['Langue néerlandaise',        [3, 4],       4]]],   // 16h
            ['isabelle.noel',   'Isabelle Noël',   [['Langue néerlandaise',        [5, 6],       4]]],   // 16h
            ['karin.denis',     'Karin Denis',     [['Langue anglaise',            [3, 4],       4]]],   // 16h
            ['laurent.simon',   'Laurent Simon',   [['Langue anglaise',            [5, 6],       4]]],   // 16h
            ['marie.renard',    'Marie Renard',    [['Sciences',                   [1, 2],       3]]],   // 12h
            ['valerie.degand',  'Valérie Degand',  [['Biologie',                   [3, 4, 5, 6], 2]]],   // 16h
            ['nicolas.collin',  'Nicolas Collin',  [['Physique',                   [3, 4, 5, 6], 2]]],   // 16h
            ['xavier.pirard',   'Xavier Pirard',   [['Chimie',                     [3, 4, 5, 6], 2]]],   // 16h
            ['ophelie.marchal', 'Ophélie Marchal', [                                                      // 20h
                ['Histoire',    [1, 2, 3], 2],
                ['Géographie',  [1, 2],    2],
            ]],
            ['patrick.lejeune', 'Patrick Lejeune', [                                                      // 20h
                ['Histoire',    [4, 5, 6], 2],
                ['Géographie',  [3, 4],    2],
            ]],
            ['rachel.pirard',   'Rachel Pirard',   [['Éducation physique',         [1, 2, 3],    2]]],   // 12h
            ['samuel.habran',   'Samuel Habran',   [['Éducation physique',         [4, 5, 6],    2]]],   // 12h
            ['tania.goffin',    'Tania Goffin',    [                                                      // 20h
                ['Philosophie et citoyenneté', null,    1],
                ['Géographie',                 [5, 6],  2],
            ]],
            ['ulrich.bodart',   'Ulrich Bodart',   [                                                      // 20h
                ['Économie',               [4, 5, 6], 2],
                ['Sciences informatiques', [3, 4],    2],
            ]],
        ],
        'athenee-cite' => [
            ['vincent.collignon', 'Vincent Collignon', [['Langue française',           [1, 2],       4]]],   // 16h
            ['wendy.lambert',     'Wendy Lambert',     [['Langue française',           [3, 4],       4]]],   // 16h
            ['yves.charlier',     'Yves Charlier',     [['Langue française',           [5, 6],       4]]],   // 16h
            ['zoe.bernard',       'Zoé Bernard',       [['Mathématiques',              [1, 2],       4]]],   // 16h
            ['alice.martin',      'Alice Martin',      [['Mathématiques',              [3, 4],       4]]],   // 16h
            ['bruno.michel',      'Bruno Michel',      [['Mathématiques',              [5, 6],       4]]],   // 16h
            ['charlotte.thomas',  'Charlotte Thomas',  [['Langue néerlandaise',        [1, 2],       4]]],   // 16h
            ['denis.dupont',      'Denis Dupont',      [['Langue néerlandaise',        [3, 4],       4]]],   // 16h
            ['elise.habran',      'Élise Habran',      [['Langue néerlandaise',        [5, 6],       4]]],   // 16h
            ['francois.noel',     'François Noël',     [['Langue anglaise',            [3, 4],       4]]],   // 16h
            ['gabrielle.ernst',   'Gabrielle Ernst',   [['Langue anglaise',            [5, 6],       4]]],   // 16h
            ['henri.gilles',      'Henri Gilles',      [['Sciences',                   [1, 2],       3]]],   // 12h
            ['julie.lecomte',     'Julie Lecomte',     [['Biologie',                   [3, 4, 5, 6], 2]]],   // 16h
            ['kevin.fontaine',    'Kevin Fontaine',    [['Physique',                   [3, 4, 5, 6], 2]]],   // 16h
            ['laura.ernst',       'Laura Ernst',       [['Chimie',                     [3, 4, 5, 6], 2]]],   // 16h
            ['marc.deprez',       'Marc Deprez',       [                                                      // 20h
                ['Histoire',    [1, 2, 3], 2],
                ['Géographie',  [1, 2],    2],
            ]],
            ['nadia.peeters',     'Nadia Peeters',     [                                                      // 20h
                ['Histoire',    [4, 5, 6], 2],
                ['Géographie',  [3, 4],    2],
            ]],
            ['omar.charlier',     'Omar Charlier',     [['Éducation physique',         [1, 2, 3],    2]]],   // 12h
            ['petra.noël',        'Petra Noël',        [['Éducation physique',         [4, 5, 6],    2]]],   // 12h
            ['quentin.simon',     'Quentin Simon',     [                                                      // 20h
                ['Philosophie et citoyenneté', null,    1],
                ['Géographie',                 [5, 6],  2],
            ]],
            ['raphael.masson',    'Raphaël Masson',    [                                                      // 20h
                ['Économie',               [4, 5, 6], 2],
                ['Sciences informatiques', [3, 4],    2],
            ]],
        ],
    ];

    // Teachers shared across both schools. Global 22h limit applies to the sum of all schools.
    private const SHARED_TEACHERS = [
        [
            'email' => 'thomas.dupuis@example.com',
            'name' => 'Thomas Dupuis',
            'lessons' => [
                'saint-lambert' => [['Langue allemande',   [6],          4]],  // 8h (LM3)
                'athenee-cite' => [['Langue allemande',   [6],          4]],  // 8h → total 16h
            ],
        ],
        [
            'email' => 'sophie.charlier@example.com',
            'name' => 'Sophie Charlier',
            'lessons' => [
                'saint-lambert' => [['Arts plastiques',    [1, 2, 3, 4], 1]],  // 8h
                'athenee-cite' => [['Arts plastiques',    [1, 2, 3, 4], 1]],  // 8h → total 16h
            ],
        ],
        [
            'email' => 'martin.renard@example.com',
            'name' => 'Martin Renard',
            'lessons' => [
                'saint-lambert' => [['Latin',              [3, 4, 5, 6], 1]],  // 8h
                'athenee-cite' => [['Latin',              [3, 4, 5, 6], 1]],  // 8h → total 16h
            ],
        ],
        [
            'email' => 'celine.dubois@example.com',
            'name' => 'Céline Dubois',
            'lessons' => [
                'saint-lambert' => [['Éducation musicale', [1, 2, 3, 4], 1]],  // 8h
                'athenee-cite' => [['Éducation musicale', [1, 2, 3, 4], 1]],  // 8h → total 16h
            ],
        ],
    ];

    // Teachers with pending join requests only — no school_user entry
    private const PENDING_TEACHERS = [
        'saint-lambert' => [
            ['pending1.sl@example.com', 'Isabel Pirard',   ['Mathématiques', 'Sciences informatiques']],
            ['pending2.sl@example.com', 'Marc Fontaine',   ['Histoire', 'Géographie']],
            ['pending3.sl@example.com', 'Nadia Deprez',    ['Langue française']],
        ],
        'athenee-cite' => [
            ['pending1.ar@example.com', 'Omar Collignon',  ['Physique', 'Chimie']],
            ['pending2.ar@example.com', 'Patricia Goffin', ['Biologie', 'Sciences']],
            ['pending3.ar@example.com', 'Quentin Lejeune', ['Éducation physique']],
        ],
    ];

    private const NOTES = [
        'Langue française' => [
            'Lecture analytique du chapitre 3. Discussion sur les procédés narratifs.',
            'Rédaction en classe. Bonne implication des élèves.',
            'Étude d\'un extrait de Molière. Registres comique et satirique.',
            'Dictée suivie d\'une mise en commun. Mots invariables à retravailler.',
            'Analyse d\'un discours politique. Travail sur l\'implicite.',
        ],
        'Mathématiques' => [
            'Introduction aux fonctions du second degré. Bonne compréhension générale.',
            'Exercices sur les équations. Plusieurs élèves en difficulté avec le discriminant.',
            'Démonstration du théorème de Pythagore. Exercices en binômes.',
            'Révisions. Questions pertinentes sur les intégrales.',
            'Correction de l\'évaluation. Analyse des erreurs fréquentes.',
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
        'Langue allemande' => [
            'Wortschatz Kapitel 4. Gruppen- und Einzelübungen.',
            'Hörverstehen: Nachrichtenbeitrag. Aussprache verbessern.',
            'Grammatik: Dativ und Akkusativ. Korrektur im Plenum.',
            'Sprechübungen: Dialoge. Gute Atmosphäre im Unterricht.',
            'Wiederholung vor der Prüfung. Gezielte Schülerfragen.',
        ],
        'Sciences' => [
            'Observation de cellules au microscope. Très bon engagement.',
            'Cours sur la mitose. Schémas complétés au tableau.',
            'TP : extraction d\'ADN. Séance très appréciée.',
            'Introduction à l\'écosystème. Discussion sur la biodiversité.',
            'Révisions. Fiche synthèse distribuée.',
        ],
        'Biologie' => [
            'Étude du système digestif. Bonnes questions des élèves.',
            'TP : dissection d\'une fleur. Engagement excellent.',
            'Génétique : les lois de Mendel. Exercices de croisements.',
            'Cours sur les systèmes nerveux et hormonal. Schémas complétés.',
            'Révisions. Questions pertinentes sur la reproduction.',
        ],
        'Physique' => [
            'Introduction aux lois de Newton. Exemples tirés du quotidien.',
            'TP : mesure de la vitesse. Résultats analysés collectivement.',
            'Cours sur l\'électricité. Montages en binômes.',
            'Optique : réflexion et réfraction. Démonstration au laser.',
            'Révisions. Exercices corrigés au tableau.',
        ],
        'Chimie' => [
            'Tableau périodique : structure et périodicité. Bonne participation.',
            'TP : réaction acide-base. Mesures de pH.',
            'Cours sur les liaisons chimiques. Modèles moléculaires utilisés.',
            'Thermochimie : enthalpie de réaction. Calculs en classe.',
            'Révisions. Exercices de stœchiométrie corrigés.',
        ],
        'Histoire' => [
            'Étude de la Première Guerre mondiale : causes et déclenchement.',
            'Analyse de documents sur la Shoah. Moment solennel et respectueux.',
            'Cours sur la Révolution industrielle. Lien avec le contemporain discuté.',
            'Exposés sur les régimes totalitaires. Bonne qualité d\'ensemble.',
            'Introduction à la guerre froide. Repères chronologiques mis en place.',
        ],
        'Géographie' => [
            'Étude des grandes zones climatiques. Cartes complétées.',
            'Cours sur la mondialisation. Débat sur les délocalisations.',
            'Les migrations internationales. Données statistiques analysées.',
            'Géographie urbaine : les mégapoles. Comparaison mondiale.',
            'Révisions. Exercices de localisation corrigés.',
        ],
        'Éducation physique' => [
            'Travail sur les fondamentaux du basket. Bonne cohésion d\'équipe.',
            'Athlétisme : sprint et relais. Chronométrages notés.',
            'Natation : amélioration de la technique de nage libre.',
            'Volleyball : règles et jeu en situation. Ambiance sportive.',
            'Stretching et relaxation. Séance appréciée.',
        ],
        'Philosophie et citoyenneté' => [
            'Discussion sur les libertés fondamentales. Échanges très riches.',
            'Étude du texte de Rousseau sur le contrat social.',
            'Débat sur l\'euthanasie. Respect mutuel et argumentation.',
            'Introduction à l\'éthique environnementale.',
            'Travail sur les droits de l\'enfant. Mise en perspective historique.',
        ],
        'Économie' => [
            'Introduction à la microéconomie : offre et demande.',
            'Étude d\'un graphique boursier. Analyse collective.',
            'Cours sur le chômage et ses causes. Débat constructif.',
            'Les politiques fiscales. Comparaison européenne.',
            'Révisions. Exercices de calcul de PIB corrigés.',
        ],
        'Sciences informatiques' => [
            'Introduction aux algorithmes. Exercices de tri.',
            'Cours sur les bases de données relationnelles. Exercices SQL.',
            'Programmation Python : fonctions et boucles. Bonne progression.',
            'Sécurité informatique : mots de passe et phishing. Sensibilisation.',
            'Révisions. Mini-projet de codage en binômes.',
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
        'Langue française' => ['Rédaction p. 34', 'Analyse de texte p. 56', 'Conjugaison – fiche 4', 'Lecture ch. 3 à 5'],
        'Mathématiques' => ['Ex. p. 45 n°1 à 5', 'Série algèbre p. 78', 'Problèmes p. 92', 'Fiche de révision 3'],
        'Langue anglaise' => ['Vocabulary p. 28–30', 'Grammar exercises 5 to 8', 'Read and summarize ch. 4', 'Listening worksheet 3'],
        'Langue néerlandaise' => ['Woordenschat h. 6 p. 42', 'Grammatica oefeningen 3–6', 'Tekst lezen h. 5', 'Schrijfoefening p. 38'],
        'Sciences' => ['Résumé ch. 4', 'Fiche d\'observation p. 18', 'Questions p. 22–24', 'Schéma à compléter ch. 3'],
        'Biologie' => ['Résumé ch. 5', 'Schéma du système nerveux', 'Questions p. 30–32', 'Fiche génétique p. 44'],
        'Histoire' => ['Résumé du cours – ch. 6', 'Analyse de document p. 38', 'Questions p. 45–47', 'Frise chronologique 3'],
        'Géographie' => ['Carte à compléter p. 24', 'Résumé – zones climatiques', 'Questions p. 36–38', 'Exercice de localisation'],
        'Physique' => ['Exercices ch. 3 p. 42', 'Problèmes d\'optique p. 58', 'Fiche de révision 2', 'Exercices de cinématique'],
        'Chimie' => ['Exercices de stœchiométrie', 'Fiche périodique à compléter', 'Problèmes de pH p. 40', 'Révisions ch. 4'],
        'Éducation physique' => ['Fiche technique basket', 'Règles du volleyball à revoir', 'Fiche d\'étirement p. 12', 'Révisions athlétisme'],
        'Philosophie et citoyenneté' => ['Résumé Rousseau ch. 2', 'Argumentation écrite p. 18', 'Lecture texte Kant', 'Réflexion sur la liberté'],
        'Économie' => ['Exercices offre/demande', 'Analyse graphique boursier', 'Questions p. 55–57', 'Calculs de PIB'],
        'Sciences informatiques' => ['Algorithme de tri à rédiger', 'Requêtes SQL p. 30', 'Exercices Python ch. 3', 'Mini-projet à rendre'],
        'Langue allemande' => ['Wortschatz Kap. 4 p. 38', 'Grammatikübungen 3–5', 'Text lesen Kap. 3', 'Schreibübung p. 44'],
        'default' => ['Exercices p. 55', 'Révisions ch. 7', 'Fiche de travail 4', 'Exercices p. 72'],
    ];

    private const TEST_TITLES = [
        'Interrogation – Chapitre 4',
        'Test récapitulatif – unité 2',
        'Contrôle de connaissances',
        'Évaluation formative – ch. 5',
        'Interrogation orale',
    ];

    // ── Instance state ──────────────────────────────────────────────────────────

    private AcademicYear $currentYear;

    private AcademicYear $archivedYear;

    private Collection $subjects;

    private Collection $sharedTeachers;

    private array $ctx = [];

    /** Slots already used by shared teachers, keyed by user_id → [slotIndex][day]. */
    private array $globalUsedSlots = [];

    // ── Entry point ─────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->archivedYear = AcademicYear::create(['year' => '2024-2025']);
        $this->currentYear = AcademicYear::create(['year' => '2025-2026']);
        $this->subjects = $this->buildSubjects();
        $this->sharedTeachers = collect(self::SHARED_TEACHERS)->map(fn ($cfg) => User::create([
            'name' => $cfg['name'],
            'email' => $cfg['email'],
            'password' => 'password',
        ]));

        foreach (self::SCHOOLS_CONFIG as $cfg) {
            $slug = $cfg['slug'];
            $this->ctx[$slug] = [
                'school' => null,
                'domain' => $cfg['domain'],
                'slots' => collect(),
                'students' => collect(),
                'archivedGroups' => collect(),
                'archivedGroupStudents' => collect(),
                'currentGroups' => collect(),
                'currentGroupStudents' => collect(),
                'teachers' => collect(),
                'lessonData' => collect(),
                'schedules' => collect(),
            ];
            $this->seedSchool($cfg);
        }
    }

    private function seedSchool(array $cfg): void
    {
        $slug = $cfg['slug'];
        $this->createSchoolAndAdmin($cfg);
        $this->createSlots($slug);
        $this->createStudentsAndArchivedGroups($slug);
        $this->createCurrentGroups($slug);
        $this->attachPerSchoolTeachers($slug);
        $this->attachSharedTeacher($slug);
        $this->createLessons($slug);
        $this->createSchedules($slug);
        $this->createSessionsAndAttendance($slug);
        $this->createAssignments($slug);
        $this->createJoinRequests($slug);
    }

    // ── School setup ────────────────────────────────────────────────────────────

    private function createSchoolAndAdmin(array $cfg): void
    {
        $slug = $cfg['slug'];

        $admin = User::create([
            'name' => $cfg['admin_name'],
            'email' => $cfg['admin_email'],
            'password' => 'password',
        ]);

        $school = School::create([
            'name' => $cfg['name'],
            'slug' => $slug,
        ]);

        $school->users()->attach($admin->id, ['role' => 'admin']);

        $school->academicYears()->attach(
            $this->currentYear->id,
            AcademicYear::defaultDates($this->currentYear->year),
        );
        $school->academicYears()->attach($this->archivedYear->id, array_merge(
            AcademicYear::defaultDates($this->archivedYear->year),
            ['archived_at' => now()],
        ));

        $school->subjects()->attach($this->subjects->pluck('id'));

        $this->ctx[$slug]['school'] = $school;
    }

    private function buildSubjects(): Collection
    {
        // Langue française is the teaching language — it is NOT a modern language (LM).
        // Only foreign languages get is_language = true so the LM level selector appears.
        $languageNames = ['Langue néerlandaise', 'Langue anglaise', 'Langue allemande'];

        $names = [
            'Langue française', 'Langue néerlandaise', 'Langue anglaise', 'Langue allemande',
            'Mathématiques', 'Sciences', 'Biologie', 'Physique', 'Chimie',
            'Histoire', 'Géographie', 'Éducation physique',
            'Philosophie et citoyenneté', 'Économie', 'Sciences informatiques',
            'Arts plastiques', 'Latin', 'Éducation musicale',
        ];

        return collect($names)->mapWithKeys(fn ($name) => [
            $name => Subject::firstOrCreate(
                ['name' => $name],
                ['is_language' => in_array($name, $languageNames)],
            ),
        ]);
    }

    private function createSlots(string $slug): void
    {
        $school = $this->ctx[$slug]['school'];
        $slots = collect();

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
                ['school_id' => $school->id, 'schedule_slot_id' => $slot->id],
                ['start_time' => $start, 'end_time' => $end],
            );

            $slots->push($slot);
        }

        $this->ctx[$slug]['slots'] = $slots;
    }

    // ── Students & groups ───────────────────────────────────────────────────────

    private function createStudentsAndArchivedGroups(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $school = $ctx['school'];

        // Build 260 students (220 for 2024-2025 + 40 new for 2025-2026 grade 1).
        // Timothée Lambert is explicitly first in school 1 for the year-progression demo.
        $students = collect();

        if ($slug === 'saint-lambert') {
            $students->push(Student::create([
                'firstname' => 'Timothée',
                'lastname' => 'Lambert',
                'school_id' => $school->id,
            ]));
        }

        $faker = Faker::create('fr_BE');
        $faker->seed(crc32($slug));

        $needed = 260 - $students->count();

        for ($i = 0; $i < $needed; $i++) {
            $students->push(Student::create([
                'firstname' => $faker->firstName(),
                'lastname' => $faker->lastName(),
                'school_id' => $school->id,
            ]));
        }

        $ctx['students'] = $students;

        // Assign students[0..219] to archived-year groups.
        $offset = 0;
        $archivedGroups = collect();

        foreach (self::GROUPS_CONFIG as [$grade, $section, $size]) {
            $group = Group::create([
                'grade' => $grade,
                'name' => $section,
                'slug' => Str::slug("{$slug}-2024-2025-{$grade}{$section}"),
                'school_id' => $school->id,
                'academic_year_id' => $this->archivedYear->id,
            ]);

            $slice = $students->slice($offset, $size)->values();
            $group->students()->attach($slice->pluck('id'));
            $ctx['archivedGroupStudents']->put($group->id, $slice);

            $archivedGroups->push($group);
            $offset += $size;
        }

        $ctx['archivedGroups'] = $archivedGroups;
    }

    private function createCurrentGroups(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $school = $ctx['school'];
        $newStudents = $ctx['students']->slice(220)->values(); // students 220–259

        $currentGroups = collect();

        foreach (self::GROUPS_CONFIG as [$grade, $section, $size]) {
            $group = Group::create([
                'grade' => $grade,
                'name' => $section,
                'slug' => Str::slug("{$slug}-2025-2026-{$grade}{$section}"),
                'school_id' => $school->id,
                'academic_year_id' => $this->currentYear->id,
            ]);

            if ((int) $grade === 1) {
                // New intake for grade 1
                $half = (int) ($newStudents->count() / 2);
                $slice = $section === 'A'
                    ? $newStudents->take($half)->values()
                    : $newStudents->skip($half)->values();
            } else {
                // Promote from previous grade in archived year
                $prevGrade = (int) $grade - 1;
                $prevGroups = $ctx['archivedGroups']
                    ->filter(fn ($g) => (int) $g->grade === $prevGrade)->values();
                $prevStudents = $prevGroups
                    ->flatMap(fn ($g) => $ctx['archivedGroupStudents']->get($g->id, collect()))
                    ->values();

                $half = (int) ($prevStudents->count() / 2);
                $slice = $section === 'A'
                    ? $prevStudents->take($half)->values()
                    : $prevStudents->skip($half)->values();
            }

            $group->students()->attach($slice->pluck('id'));
            $ctx['currentGroupStudents']->put($group->id, $slice);

            $currentGroups->push($group);
        }

        $ctx['currentGroups'] = $currentGroups;
    }

    // ── Teachers ────────────────────────────────────────────────────────────────

    private function attachPerSchoolTeachers(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $school = $ctx['school'];

        $domain = $ctx['domain'];

        foreach (self::SCHOOL_TEACHERS[$slug] as [$prefix, $name]) {
            $email = "{$prefix}@{$domain}.example.com";
            $teacher = User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
            ]);
            $school->users()->attach($teacher->id, ['role' => 'teacher']);
            $ctx['teachers']->put($email, $teacher);
        }
    }

    private function attachSharedTeacher(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $school = $ctx['school'];

        foreach (self::SHARED_TEACHERS as $i => $cfg) {
            $teacher = $this->sharedTeachers->get($i);
            $school->users()->attach($teacher->id, ['role' => 'teacher']);
            $ctx['teachers']->put($cfg['email'], $teacher);
        }
    }

    private function createJoinRequests(string $slug): void
    {
        $school = $this->ctx[$slug]['school'];

        foreach (self::PENDING_TEACHERS[$slug] as [$email, $name, $subjectNames]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password'],
            );

            $subjectIds = $this->subjects->only($subjectNames)->pluck('id');
            $user->subjects()->syncWithoutDetaching($subjectIds);

            SchoolJoinRequest::create([
                'user_id'   => $user->id,
                'school_id' => $school->id,
                'status'    => 'pending',
            ]);
        }
    }

    // ── Lessons ─────────────────────────────────────────────────────────────────

    private function createLessons(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $currentGroups = $ctx['currentGroups'];

        // Per-school teachers
        $domain = $ctx['domain'];

        foreach (self::SCHOOL_TEACHERS[$slug] as [$prefix, , $assignments]) {
            $email = "{$prefix}@{$domain}.example.com";
            $teacher = $ctx['teachers']->get($email);

            if (! $teacher) {
                continue;
            }

            $this->buildTeacherLessons($ctx, $teacher, $assignments, $currentGroups);
        }

        // Shared teachers
        foreach (self::SHARED_TEACHERS as $i => $cfg) {
            $assignments = $cfg['lessons'][$slug] ?? [];
            if ($assignments) {
                $teacher = $this->sharedTeachers->get($i);
                $this->buildTeacherLessons($ctx, $teacher, $assignments, $currentGroups);
            }
        }
    }

    private function buildTeacherLessons(array &$ctx, User $teacher, array $assignments, Collection $currentGroups): void
    {
        foreach ($assignments as [$subjectName, $grades, $hours]) {
            $subject = $this->subjects->get($subjectName);

            if (! $subject) {
                continue;
            }

            $groups = $grades === null
                ? $currentGroups->values()
                : $currentGroups->filter(fn ($g) => in_array((int) $g->grade, $grades))->values();

            foreach ($groups as $group) {
                // FWB: NL = LM1 (all grades), EN = LM2 (from gr 3), DE = LM3 (optional gr 5-6)
                $lmLevel = match (true) {
                    str_contains($subjectName, 'néerlandaise') => 1,
                    str_contains($subjectName, 'anglaise') => 2,
                    str_contains($subjectName, 'allemande') => 3,
                    default => null,
                };

                $lesson = Lesson::create([
                    'group_id' => $group->id,
                    'subject_id' => $subject->id,
                    'lm_level' => $lmLevel,
                ]);
                $lesson->users()->attach($teacher->id);

                $ctx['lessonData']->push([
                    'lesson' => $lesson,
                    'teacher' => $teacher,
                    'subject_name' => $subjectName,
                    'group_id' => $group->id,
                    'hours' => $hours,
                ]);
            }

            $teacher->subjects()->syncWithoutDetaching([$subject->id]);
        }
    }

    // ── Schedules ───────────────────────────────────────────────────────────────

    private function createSchedules(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $school = $ctx['school'];
        $year = $this->currentYear;

        foreach ($ctx['teachers'] as $teacher) {
            $schedule = Schedule::create([
                'user_id' => $teacher->id,
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
            ]);
            $ctx['schedules']->put($teacher->id, $schedule);
        }

        mt_srand(2025 + crc32($slug));

        $lessonsByTeacher = $ctx['lessonData']->groupBy(fn ($d) => $d['teacher']->id);

        foreach ($lessonsByTeacher as $teacherId => $teacherLessons) {
            $schedule = $ctx['schedules']->get($teacherId);

            if (! $schedule) {
                continue;
            }

            // Build all 50 (slotIndex, day) pairs and shuffle
            $pairs = [];
            for ($s = 0; $s < 10; $s++) {
                for ($d = 1; $d <= 5; $d++) {
                    $pairs[] = [$s, $d];
                }
            }
            shuffle($pairs);

            $teacherUsed = $this->globalUsedSlots[$teacherId] ?? [];

            foreach ($teacherLessons->values() as $i => $data) {
                $hours = $data['hours'];
                $lessonDayCount = array_fill_keys(range(1, 5), 0);
                $assigned = 0;

                foreach ($pairs as [$slotIndex, $day]) {
                    if ($assigned >= $hours) {
                        break;
                    }

                    if (isset($teacherUsed[$slotIndex][$day])) {
                        continue;
                    }

                    if ($lessonDayCount[$day] >= 2) {
                        continue;
                    }

                    $slot = $ctx['slots']->get($slotIndex);

                    if (! $slot) {
                        continue;
                    }

                    ScheduleEntry::create([
                        'schedule_id' => $schedule->id,
                        'schedule_slot_id' => $slot->id,
                        'lesson_id' => $data['lesson']->id,
                        'day_of_week' => $day,
                        'classroom' => self::ROOMS[($i * 4 + $assigned) % count(self::ROOMS)],
                    ]);

                    $teacherUsed[$slotIndex][$day] = true;
                    $lessonDayCount[$day]++;
                    $assigned++;
                }
            }

            $this->globalUsedSlots[$teacherId] = $teacherUsed;
        }
    }

    // ── Sessions & attendance ───────────────────────────────────────────────────

    private function createSessionsAndAttendance(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $ctx['lessonData']->each(fn ($d) => $d['lesson']->load('scheduleEntries'));

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

            foreach ($ctx['lessonData'] as $data) {
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

                $groupStudents = $ctx['currentGroupStudents']->get($data['group_id'], collect());
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

    // ── Assignments ─────────────────────────────────────────────────────────────

    private function createAssignments(string $slug): void
    {
        $ctx = &$this->ctx[$slug];
        $today = Carbon::today();

        foreach ($ctx['lessonData'] as $data) {
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

    // ── Date helpers ────────────────────────────────────────────────────────────

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
