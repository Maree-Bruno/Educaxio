<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
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

        $teacher = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

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
        $schools->each(fn (School $s) => $s->users()->attach($teacher->id, ['role' => 'teacher']));

        // ── Subjects ─────────────────────────────────────────────────────────
        $subjects = collect([
            'Anglais', 'Néerlandais', 'Mathématiques', 'Sciences',
            'Biologie', 'Physique', 'Chimie', 'Sciences humaines',
            'Éducation physique', 'Arts', 'Français', 'Histoire',
            'Géographie', 'Morale',
        ])->map(fn ($name) => Subject::create(['name' => $name]));

        $schools->each(fn (School $s) => $s->subjects()->attach($subjects->pluck('id')));
        $schools->each(fn (School $s) => $s->academicYears()->attach($academicYear->id));

        // ── Schedules + slots ─────────────────────────────────────────────────
        $slotLabels = [
            '1ère heure', '2e heure', '3e heure', '4e heure', '5e heure',
            '6e heure',   '7e heure', '8e heure', '9e heure', '10e heure',
        ];

        $schedulesBySchool = $schools->mapWithKeys(function (School $school) use ($teacher, $academicYear, $slotLabels) {
            $schedule = Schedule::create([
                'user_id' => $teacher->id,
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
            ]);

            foreach ($slotLabels as $i => $label) {
                $schedule->slots()->create([
                    'position' => $i + 1,
                    'label' => $label,
                    'type' => 'slot',
                ]);
            }

            $schedule->load('slots');

            return [$school->id => $schedule];
        });

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
        // ── Lessons (langues) ─────────────────────────────────────────────────
        $anglais = $subjects->firstWhere('name', 'Anglais');
        $neerlandais = $subjects->firstWhere('name', 'Néerlandais');

        $makeLesson = function (Group $group, Subject $subject) use ($teacher) {
            $lesson = Lesson::create([
                'name' => $subject->name,
                'group_id' => $group->id,
                'subject_id' => $subject->id,
            ]);
            $lesson->users()->attach($teacher->id);

            return $lesson;
        };

        $lessonData = [
            // Anglais — 4 classes (3 à Saint-Joseph, 1 à l'Athénée)
            ['lesson' => $makeLesson($sj3A, $anglais), 'school_id' => $saintJoseph->id],
            ['lesson' => $makeLesson($sj3B, $anglais), 'school_id' => $saintJoseph->id],
            ['lesson' => $makeLesson($sj4A, $anglais), 'school_id' => $saintJoseph->id],
            ['lesson' => $makeLesson($ar3A, $anglais), 'school_id' => $athenee->id],
            // Néerlandais — 4 classes (1 à Saint-Joseph, 3 à l'Athénée)
            ['lesson' => $makeLesson($sj2A, $neerlandais), 'school_id' => $saintJoseph->id],
            ['lesson' => $makeLesson($ar1B, $neerlandais), 'school_id' => $athenee->id],
            ['lesson' => $makeLesson($ar2C, $neerlandais), 'school_id' => $athenee->id],
            ['lesson' => $makeLesson($ar1D, $neerlandais), 'school_id' => $athenee->id],
        ];

        // ── Schedule entries — 4h par semaine par cours ───────────────────────
        $usedSlotDays = [];

        foreach ($lessonData as $data) {
            $lesson = $data['lesson'];
            $schedule = $schedulesBySchool[$data['school_id']];
            $slots = $schedule->slots;

            $assigned = 0;
            $attempts = 0;

            while ($assigned < 4 && $attempts < 50) {
                $attempts++;
                $slot = $slots->random();
                $day = fake()->numberBetween(1, 5);

                if (isset($usedSlotDays[$slot->id][$day])) {
                    continue;
                }

                $usedSlotDays[$slot->id][$day] = true;

                ScheduleEntry::create([
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
