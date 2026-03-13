<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PunchController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SecurityWarningController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('kiosk.auth')->group(function () {
    Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.home');
    Route::get('/kiosk-camera', [KioskController::class, 'cameraIndex'])->name('kiosk.camera.home');
    Route::post('/kiosk/identify', [KioskController::class, 'identify'])
        ->middleware('throttle:kiosk-pin')
        ->name('kiosk.identify');
    Route::post('/kiosk/clock-in', [KioskController::class, 'clockIn'])
        ->middleware('throttle:kiosk-pin')
        ->name('kiosk.clock-in');
    Route::post('/kiosk/clock-out', [KioskController::class, 'clockOut'])
        ->middleware('throttle:kiosk-pin')
        ->name('kiosk.clock-out');
    Route::post('/kiosk-camera/clock-in', [KioskController::class, 'cameraClockIn'])
        ->middleware('throttle:kiosk-pin')
        ->name('kiosk.camera.clock-in');
    Route::post('/kiosk-camera/clock-out', [KioskController::class, 'cameraClockOut'])
        ->middleware('throttle:kiosk-pin')
        ->name('kiosk.camera.clock-out');
});

Route::middleware(['auth', 'verified', 'log.ip.mismatch'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/punches', [PunchController::class, 'index'])->name('punches.index');
    Route::get('/punches/summary', [PunchController::class, 'summary'])->name('punches.summary');
    Route::get('/punches/photos', [PunchController::class, 'photos'])->name('punches.photos');
    Route::post('/punches/{punch}/force-clock-out', [PunchController::class, 'forceClockOut'])->name('punches.force-clock-out');
    Route::get('/clock', [ClockController::class, 'index'])->name('clock.index');
    Route::post('/clock/in', [ClockController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock/out', [ClockController::class, 'clockOut'])->name('clock.out');

    Route::get('/staff', [StaffController::class, 'index'])->middleware('role:admin,hr')->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->middleware('role:admin,hr')->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->middleware('role:admin,hr')->name('staff.store');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->middleware('role:admin,hr')->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->middleware('role:admin,hr')->name('staff.update');
    Route::post('/staff/{staff}/reset-pin', [StaffController::class, 'resetPin'])->middleware('role:admin,hr')->name('staff.reset-pin');

    Route::get('/positions', [PositionController::class, 'index'])->middleware('role:admin')->name('positions.index');
    Route::get('/positions/create', [PositionController::class, 'create'])->middleware('role:admin')->name('positions.create');
    Route::post('/positions', [PositionController::class, 'store'])->middleware('role:admin')->name('positions.store');
    Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->middleware('role:admin')->name('positions.edit');
    Route::put('/positions/{position}', [PositionController::class, 'update'])->middleware('role:admin')->name('positions.update');

    Route::get('/locations', [LocationController::class, 'index'])->middleware('role:admin')->name('locations.index');
    Route::get('/locations/create', [LocationController::class, 'create'])->middleware('role:admin')->name('locations.create');
    Route::post('/locations', [LocationController::class, 'store'])->middleware('role:admin')->name('locations.store');
    Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->middleware('role:admin')->name('locations.edit');
    Route::put('/locations/{location}', [LocationController::class, 'update'])->middleware('role:admin')->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->middleware('role:admin')->name('locations.destroy');
    Route::post('/locations/{location}/kiosk-token/rotate', [LocationController::class, 'rotateKioskToken'])->middleware('role:admin')->name('locations.kiosk.rotate');

    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/form', [ScheduleController::class, 'form'])->name('schedules.form');
    Route::post('/schedules/form/reopen', [ScheduleController::class, 'reopenFormForModification'])->middleware('schedule.permission:create')->name('schedules.form.reopen');
    Route::post('/schedules/form/cancel-editing', [ScheduleController::class, 'cancelFormEditing'])->middleware('schedule.permission:create')->name('schedules.form.cancel-editing');
    Route::post('/schedules/form/submit-reapproval', [ScheduleController::class, 'submitFormForReapproval'])->middleware('schedule.permission:create')->name('schedules.form.submit-reapproval');
    Route::post('/schedules/form/add-line', [ScheduleController::class, 'addLineToForm'])->middleware('schedule.permission:create')->name('schedules.form.add-line');
    Route::post('/schedules/form/approve', [ScheduleController::class, 'approveForm'])->middleware('schedule.permission:approve')->name('schedules.form.approve');
    Route::post('/schedules/form/reject', [ScheduleController::class, 'rejectForm'])->middleware('schedule.permission:approve')->name('schedules.form.reject');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->middleware('schedule.permission:create')->name('schedules.create');
    Route::post('/schedules', [ScheduleController::class, 'store'])->middleware('schedule.permission:create')->name('schedules.store');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::get('/schedules/approvals', [ScheduleController::class, 'approvals'])->middleware('schedule.permission:approve')->name('schedules.approvals');
    Route::post('/schedules/{schedule}/approve', [ScheduleController::class, 'approve'])->middleware('schedule.permission:approve')->name('schedules.approve');
    Route::post('/schedules/{schedule}/reject', [ScheduleController::class, 'reject'])->middleware('schedule.permission:approve')->name('schedules.reject');

    Route::get('/reports/security-warnings', [SecurityWarningController::class, 'index'])->middleware('role:admin,manager')->name('reports.security-warnings');
    Route::post('/reports/security-warnings/{warning}/resolve', [SecurityWarningController::class, 'resolve'])->middleware('role:admin,manager')->name('reports.security-warnings.resolve');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
