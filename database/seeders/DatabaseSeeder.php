<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentAttendanceStatus;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);

        $schools = collect([
            ['name' => 'Institut Saint-Joseph', 'slug' => 'saint-joseph'],
            ['name' => 'Athénée Royal de Bruxelles', 'slug' => 'athenee-royal-bruxelles'],
        ])->map(fn ($data) => School::create($data));

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
            ['name' => 'A', 'grade' => '3', 'school' => 'saint-joseph'],
            ['name' => 'B', 'grade' => '3', 'school' => 'saint-joseph'],
            ['name' => 'A', 'grade' => '4', 'school' => 'saint-joseph'],
            ['name' => 'A', 'grade' => '2', 'school' => 'saint-joseph'],
            ['name' => 'B', 'grade' => '1', 'school' => 'athenee-royal-bruxelles'],
            ['name' => 'A', 'grade' => '7', 'school' => 'athenee-royal-bruxelles'],
            ['name' => 'C', 'grade' => '2', 'school' => 'athenee-royal-bruxelles'],
            ['name' => 'D', 'grade' => '1', 'school' => 'athenee-royal-bruxelles'],
            ['name' => 'A', 'grade' => '3', 'school' => 'athenee-royal-bruxelles'],
        ];

        foreach ($groupData as $data) {
            $school = $schools->firstWhere('slug', $data['school']);
            $group = Group::create([
                'name' => $data['name'],
                'grade' => $data['grade'],
                'slug' => Str::slug("{$school->slug}-{$data['grade']}-{$data['name']}"),
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
            ]);

            $students = Student::factory(15)->create(['group_id' => $group->id]);

            $subject = $subjects->random();
            $lesson = Lesson::factory()->create([
                'name' => $subject->name,
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
        }
    }
}
