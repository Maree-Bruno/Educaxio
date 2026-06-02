<?php

use App\Http\Controllers\Admin\JoinRequestController as AdminJoinRequestController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\SchoolSlotTimeController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonNoteController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleEntryController;
use App\Http\Controllers\ScheduleSlotController;
use App\Http\Controllers\StudentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

Route::middleware('guest')->post('register/validate', function (Request $request) {
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
        'password' => ['required', 'string', Password::default(), 'confirmed'],
    ]);

    return redirect()->back();
});

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Accessible sans école approuvée
    Route::get('en-attente', [PendingController::class, 'index'])->name('pending');
    Route::post('en-attente/demandes', [PendingController::class, 'requestSchool'])->name('pending.join-requests.store');
    Route::delete('en-attente/demandes/{joinRequest}', [PendingController::class, 'cancelRequest'])->name('pending.join-requests.destroy');
    Route::patch('en-attente/matieres', [PendingController::class, 'syncSubjects'])->name('pending.subjects.sync');

    // Redirections GET anglais → français
    Route::redirect('pending', '/en-attente', 301);
});

Route::middleware(['auth', 'verified', 'school.approved'])->group(function () {
    Route::get('tableau-de-bord', DashboardController::class)->name('dashboard');

    // Classes
    Route::get('classes', [ClassListController::class, 'index'])->name('classlist');
    Route::get('classes/creer', [ClassListController::class, 'create'])->name('classlist.create');
    Route::get('classes/{group}', [ClassListController::class, 'show'])->name('classlist.show');
    Route::post('classes', [ClassListController::class, 'store'])->name('classlist.store');
    Route::patch('classes/{group}', [ClassListController::class, 'update'])->name('classlist.update');
    Route::delete('classes/{group}', [ClassListController::class, 'destroy'])->name('classlist.destroy');
    Route::post('classes/{group}/eleves', [ClassListController::class, 'attachStudent'])->name('classlist.students.attach');

    // Élèves
    Route::post('eleves', [StudentController::class, 'store'])->name('students.store');
    Route::get('eleves/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('eleves/{student}/modifier', [StudentController::class, 'edit'])->name('students.edit');
    Route::patch('eleves/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('eleves/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Redirections GET anglais → français
    Route::redirect('dashboard', '/tableau-de-bord', 301);
    Route::redirect('classlist', '/classes', 301);
    Route::redirect('classlist/create', '/classes/creer', 301);
    Route::get('classlist/{group}', fn ($group) => redirect("/classes/{$group}", 301));
    Route::get('students/{student}', fn ($student) => redirect("/eleves/{$student}", 301));

    // Teacher-only
    Route::middleware('role.teacher')->group(function () {
        Route::get('horaires', [ScheduleController::class, 'index'])->name('schedules');
        Route::patch('creneaux-type', [ScheduleSlotController::class, 'updateType'])->name('schedule-slots.update-type');
        Route::post('creneaux', [ScheduleEntryController::class, 'store'])->name('schedule-entries.store');
        Route::delete('creneaux/{scheduleEntry}', [ScheduleEntryController::class, 'destroy'])->name('schedule-entries.destroy');

        Route::get('agenda', AgendaController::class)->name('agenda');

        Route::get('presences', [AttendanceController::class, 'index'])->name('attendances');
        Route::post('presences', [AttendanceController::class, 'store'])->name('attendances.store');
        Route::delete('presences/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

        Route::post('notes-cours', [LessonNoteController::class, 'store'])->name('lesson-notes.store');

        Route::post('devoirs', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::patch('devoirs/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('devoirs/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        // Redirections GET anglais → français
        Route::redirect('schedules', '/horaires', 301);
        Route::redirect('attendances', '/presences', 301);
    });

    // Admin — school-scoped
    Route::prefix('ecoles/{school:slug}')
        ->middleware('school.admin')
        ->name('admin.')
        ->group(function () {
            Route::get('eleves', [AdminStudentController::class, 'index'])->name('students.index');
            Route::post('eleves', [AdminStudentController::class, 'store'])->name('students.store');
            Route::patch('eleves/{student}', [AdminStudentController::class, 'update'])->name('students.update');
            Route::delete('eleves/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');

            Route::get('professeurs', [AdminTeacherController::class, 'index'])->name('teachers.index');
            Route::patch('demandes/{joinRequest}/approuver', [AdminJoinRequestController::class, 'approve'])->name('join-requests.approve');
            Route::patch('demandes/{joinRequest}/refuser', [AdminJoinRequestController::class, 'reject'])->name('join-requests.reject');

            Route::get('cours', [AdminLessonController::class, 'index'])->name('lessons.index');
            Route::post('cours', [AdminLessonController::class, 'store'])->name('lessons.store');
            Route::patch('cours/{lesson}', [AdminLessonController::class, 'update'])->name('lessons.update');
            Route::put('cours/{lesson}/professeurs', [AdminLessonController::class, 'syncTeachers'])->name('lessons.teachers.sync');
            Route::delete('cours/{lesson}', [AdminLessonController::class, 'destroy'])->name('lessons.destroy');

            Route::put('creneaux-horaires', [SchoolSlotTimeController::class, 'update'])->name('slot-times.update');
        });

    // Redirections GET admin anglais → français
    Route::get('schools/{school}/students', fn ($school) => redirect("/ecoles/{$school}/eleves", 301));
    Route::get('schools/{school}/teachers', fn ($school) => redirect("/ecoles/{$school}/professeurs", 301));
    Route::get('schools/{school}/lessons', fn ($school) => redirect("/ecoles/{$school}/cours", 301));
});

require __DIR__.'/settings.php';
