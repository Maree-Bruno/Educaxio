<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassListController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    //Classlist
    Route::get('classlist', [ClassListController::class, 'index'])->name('classlist');
    Route::get('classlist/create', [ClassListController::class, 'create'])->name('classlist.create');
    Route::get('classlist/{group}', [ClassListController::class, 'show'])->name('classlist.show');
    Route::post('classlist', [ClassListController::class, 'store'])->name('classlist.store');
    Route::patch('classlist/{group}', [ClassListController::class, 'update'])->name('classlist.update');
    Route::delete('classlist/{group}', [ClassListController::class, 'destroy'])->name('classlist.destroy');
    // attendances
    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

});
require __DIR__.'/settings.php';
