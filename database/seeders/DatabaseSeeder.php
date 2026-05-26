<?php /** @noinspection D */

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ScheduleSlot;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['101', '102', '103', '201', '202', '203', '301', '302', '303'];

        // ── Users ────────────────────────────────────────────────────────────
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

        // Teachers — chacun a une ou deux matières
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

        // ── Academic year ────────────────────────────────────────────────────
        $academicYear = AcademicYear::create(['year' => '2025-2026']);

        // ── Schools ──────────────────────────────────────────────────────────
        $schools = collect([
            ['name' => 'Institut Saint-Joseph',      'slug' => 'saint-joseph'],
            ['name' => 'Athénée Royal de Bruxelles', 'slug' => 'athenee-royal-bruxelles'],
        ])->map(fn ($data) => School::create($data));

        $saintJoseph = $schools->firstWhere('slug', 'saint-joseph');
        $athenee = $schools->firstWhere('slug', 'athenee-royal-bruxelles');

        $saintJoseph->users()->attach($adminSaintJoseph->id, ['role' => 'admin']);
        $athenee->users()->attach($adminAthenee->id, ['role' => 'admin']);
        $schools->each(fn (School $s) => $teachers->each(
            fn (User $t) => $s->users()->attach($t->id, ['role' => 'teacher'])
        ));

        // ── Subjects ─────────────────────────────────────────────────────────
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

        // prof → matières qu'il enseigne
        $teacherSubjects = [
            $profAnglais->id => [$anglais],
            $profNl->id => [$neerlandais],
            $profMaths->id => [$maths, $sciences],
            $profFrancais->id => [$francais],
        ];

        // ── Schedules + slots ─────────────────────────────────────────────────
        $slotLabels = [
            '1ère heure', '2e heure', '3e heure', '4e heure', '5e heure',
            '6e heure',   '7e heure', '8e heure', '9e heure', '10e heure',
        ];

        // 10 slots globaux (communs à tous les profs)
        $slots = collect();
        foreach ($slotLabels as $i => $label) {
            $slots->push(ScheduleSlot::create([
                'position' => $i + 1,
                'label' => $label,
                'type' => 'slot',
            ]));
        }

        // schedule[teacher_id][school_id] = Schedule
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

        // ── Groups & students ─────────────────────────────────────────────────
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

        // Saint-Joseph
        $sj3A = $makeGroup('3', 'A', $saintJoseph);
        $sj3B = $makeGroup('3', 'B', $saintJoseph);
        $sj4A = $makeGroup('4', 'A', $saintJoseph);
        $sj2A = $makeGroup('2', 'A', $saintJoseph);

        // Athénée
        $ar3A = $makeGroup('3', 'A', $athenee);
        $ar1B = $makeGroup('1', 'B', $athenee);
        $ar2C = $makeGroup('2', 'C', $athenee);
        $ar1D = $makeGroup('1', 'D', $athenee);

        // ── Lessons : (groupe, matière) → prof responsable ────────────────────
        // Chaque groupe a les 5 matières de base ; chaque prof prend ses matières
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

        // ── Schedule entries — 3 créneaux par leçon ───────────────────────────
        // Chaque prof a son propre schedule ; on évite les conflits slot+jour par prof
        $usedSlotDays = []; // [teacher_id][slot_id][day] = true

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $teacher = $data['teacher'];
            $schedule = $schedulesByTeacherSchool[$teacher->id][$data['school_id']];

            $assigned = 0;
            $attempts = 0;

            while ($assigned < 3 && $attempts < 60) {
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
    }
}
