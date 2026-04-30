<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);

        $subjectNames = [
            'Anglais', 'Néerlandais', 'Mathématiques', 'Sciences',
            'Biologie', 'Physique', 'Chimie', 'Sciences humaines',
            'Éducation physique', 'Arts', 'Français', 'Histoire',
            'Géographie', 'Morale',
        ];
        $subjects = collect($subjectNames)->map(fn ($name) => Subject::create(['name' => $name]));

        $teacher = User::factory()->create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $groupData = [
            ['name' => 'A', 'grade' => '3'],
            ['name' => 'B', 'grade' => '3'],
            ['name' => 'A', 'grade' => '4'],
        ];

        foreach ($groupData as $data) {
            $group = Group::create([
                'name' => $data['name'],
                'grade' => $data['grade'],
                'academic_year_id' => $academicYear->id,
            ]);

            $students = Student::factory(15)->create(['group_id' => $group->id]);

            $subjects->random(4)->each(function (Subject $subject) use ($group, $teacher, $academicYear, $students) {
                $lesson = Lesson::factory()->create([
                    'group_id' => $group->id,
                    'subject_id' => $subject->id,
                    'user_id' => $teacher->id,
                    'academic_year_id' => $academicYear->id,
                ]);

                ClassSession::factory(5)->create(['lesson_id' => $lesson->id])
                    ->each(function (ClassSession $session) use ($students) {
                        $attendance = Attendance::factory()->create(['classsession_id' => $session->id]);

                        $students->random(3)->each(function (Student $student) use ($attendance) {
                            StudentAttendanceStatus::factory()->create([
                                'student_id' => $student->id,
                                'attendance_id' => $attendance->id,
                            ]);
                        });
                    });
            });
        }
    }
}
