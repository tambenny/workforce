<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SecurityWarning;
use App\Models\TimePunch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->canViewDashboard()) {
            return redirect()->route($user->preferredHomeRouteName());
        }

        if (in_array($user->role, ['admin', 'manager', 'hr'], true)) {
            $openPunchesQuery = TimePunch::query()
                ->whereNull('clock_out_at');
            $pendingSchedulesQuery = Schedule::query()
                ->where('status', 'submitted');
            $warningsQuery = SecurityWarning::query()
                ->whereNull('resolved_at');

            if ($user->role === 'manager') {
                $openPunchesQuery->where('location_id', $user->location_id);
                $pendingSchedulesQuery->where('location_id', $user->location_id);
                $warningsQuery->where('location_id', $user->location_id);
            }

            return view('dashboard', [
                'role' => $user->role,
                'dashboardLocation' => $user->role === 'manager' ? $user->location : null,
                'openPunches' => $openPunchesQuery->count(),
                'openLocations' => (clone $openPunchesQuery)->distinct('location_id')->count('location_id'),
                'pendingSchedules' => $pendingSchedulesQuery->count(),
                'unresolvedWarnings' => $warningsQuery->count(),
            ]);
        }

        $myOpenPunch = TimePunch::where('user_id', $user->id)
            ->whereNull('clock_out_at')
            ->first();

        return view('dashboard', [
            'role' => $user->role,
            'dashboardLocation' => $user->location,
            'myOpenPunch' => $myOpenPunch,
        ]);
    }
}
