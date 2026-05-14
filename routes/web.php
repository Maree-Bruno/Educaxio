<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassListController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Class list
    Route::get('classlist', [ClassListController::class, 'index'])->name('classlist');
    Route::get('classlist/create', [ClassListController::class, 'create'])->name('classlist.create');
    Route::get('classlist/{group}', [ClassListController::class, 'show'])->name('classlist.show');
    Route::post('classlist', [ClassListController::class, 'store'])->name('classlist.store');
    Route::patch('classlist/{group}', [ClassListController::class, 'update'])->name('classlist.update');
    Route::delete('classlist/{group}', [ClassListController::class, 'destroy'])->name('classlist.destroy');

    // Students
    Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::patch('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');


    // attendances
    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

});
require __DIR__.'/settings.php';
