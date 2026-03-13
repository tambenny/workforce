<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSchedulePermission;
use App\Http\Middleware\KioskAuth;
use App\Http\Middleware\LogIpMismatch;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'schedule.permission' => EnsureSchedulePermission::class,
            'kiosk.auth' => KioskAuth::class,
            'log.ip.mismatch' => LogIpMismatch::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
