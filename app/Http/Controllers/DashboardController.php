<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SecurityWarning;
use App\Models\TimePunch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'manager'], true)) {
            $openPunches = TimePunch::whereNull('clock_out_at')->count();
            $pendingSchedules = Schedule::where('status', 'submitted')->count();
            $unresolvedWarnings = SecurityWarning::whereNull('resolved_at')->count();

            return view('dashboard', [
                'role' => $user->role,
                'openPunches' => $openPunches,
                'pendingSchedules' => $pendingSchedules,
                'unresolvedWarnings' => $unresolvedWarnings,
            ]);
        }

        $myOpenPunch = TimePunch::where('user_id', $user->id)
            ->whereNull('clock_out_at')
            ->first();

        return view('dashboard', [
            'role' => $user->role,
            'myOpenPunch' => $myOpenPunch,
        ]);
    }
}
