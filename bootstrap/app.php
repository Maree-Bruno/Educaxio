<?php

use App\Http\Middleware\EnsureApprovedSchool;
use App\Http\Middleware\EnsureSchoolAdmin;
use App\Http\Middleware\EnsureTeacherRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Console\Commands\ArchiveAcademicYears;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'school.admin' => EnsureSchoolAdmin::class,
            'school.approved' => EnsureApprovedSchool::class,
            'role.teacher' => EnsureTeacherRole::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(ArchiveAcademicYears::class)->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
