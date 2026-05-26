<?php

use App\Http\Controllers\Admin\JoinRequestController as AdminJoinRequestController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassListController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleEntryController;
use App\Http\Controllers\ScheduleSlotController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Features;

Route::middleware('guest')->post('register/validate', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique(\App\Models\User::class)],
        'password' => ['required', 'string', Password::default(), 'confirmed'],
    ]);

    return redirect()->back();
});

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Accessible sans école approuvée
    Route::get('pending', [PendingController::class, 'index'])->name('pending');
    Route::post('pending/join-requests', [PendingController::class, 'requestSchool'])->name('pending.join-requests.store');
    Route::delete('pending/join-requests/{joinRequest}', [PendingController::class, 'cancelRequest'])->name('pending.join-requests.destroy');
    Route::patch('pending/subjects', [PendingController::class, 'syncSubjects'])->name('pending.subjects.sync');
});

Route::middleware(['auth', 'verified', 'school.approved'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Class list
    Route::get('classlist', [ClassListController::class, 'index'])->name('classlist');
    Route::get('classlist/create', [ClassListController::class, 'create'])->name('classlist.create');
    Route::get('classlist/{group}', [ClassListController::class, 'show'])->name('classlist.show');
    Route::post('classlist', [ClassListController::class, 'store'])->name('classlist.store');
    Route::patch('classlist/{group}', [ClassListController::class, 'update'])->name('classlist.update');
    Route::delete('classlist/{group}', [ClassListController::class, 'destroy'])->name('classlist.destroy');
    Route::post('classlist/{group}/students', [ClassListController::class, 'attachStudent'])->name('classlist.students.attach');

    // Students
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::patch('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Schedules
    Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules');

    // Schedule slots
    Route::patch('schedule-slots-type', [ScheduleSlotController::class, 'updateType'])->name('schedule-slots.update-type');

    // Schedule entries (recurring weekly template)
    Route::post('schedule-entries', [ScheduleEntryController::class, 'store'])->name('schedule-entries.store');
    Route::delete('schedule-entries/{scheduleEntry}', [ScheduleEntryController::class, 'destroy'])->name('schedule-entries.destroy');

    // Attendances
    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

    // Admin — school-scoped
    Route::prefix('schools/{school:slug}')
        ->middleware('school.admin')
        ->name('admin.')
        ->group(function () {
            Route::get('students', [AdminStudentController::class, 'index'])->name('students.index');
            Route::post('students', [AdminStudentController::class, 'store'])->name('students.store');
            Route::patch('students/{student}', [AdminStudentController::class, 'update'])->name('students.update');
            Route::delete('students/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');

            Route::get('teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
            Route::patch('join-requests/{joinRequest}/approve', [AdminJoinRequestController::class, 'approve'])->name('join-requests.approve');
            Route::patch('join-requests/{joinRequest}/reject', [AdminJoinRequestController::class, 'reject'])->name('join-requests.reject');

            Route::get('lessons', [AdminLessonController::class, 'index'])->name('lessons.index');
            Route::post('lessons', [AdminLessonController::class, 'store'])->name('lessons.store');
            Route::put('lessons/{lesson}/teachers', [AdminLessonController::class, 'syncTeachers'])->name('lessons.teachers.sync');
            Route::delete('lessons/{lesson}', [AdminLessonController::class, 'destroy'])->name('lessons.destroy');
        });
});

require __DIR__.'/settings.php';
