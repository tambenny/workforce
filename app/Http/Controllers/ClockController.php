<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\SecurityWarning;
use App\Models\TimePunch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClockController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('location');
        abort_unless($user->canUseWebClock(), 403, 'Insufficient role.');

        $openPunch = TimePunch::with('schedule')->where('user_id', $user->id)->whereNull('clock_out_at')->first();
        $todaySchedule = null;
        if ($user->requires_schedule_for_clock) {
            $todaySchedule = Schedule::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->orderByDesc('starts_at')
                ->first();
        }

        $currentIp = (string) $request->ip();
        $allowedIp = $user->location?->allowed_ip;
        $isNetworkRestricted = filled($allowedIp);
        $isCurrentIpAllowed = ! $isNetworkRestricted || $currentIp === $allowedIp;

        $clockInBlockReason = null;
        if ($openPunch) {
            $clockInBlockReason = 'You already have an open punch.';
        } elseif ($user->requires_schedule_for_clock && ! $todaySchedule) {
            $clockInBlockReason = 'No active approved schedule for this time window.';
        } elseif (! $isCurrentIpAllowed) {
            $clockInBlockReason = 'Clock in must be performed from store machine network.';
        }

        $clockOutBlockReason = null;
        if (! $openPunch) {
            $clockOutBlockReason = 'No open punch found.';
        } elseif (! $isCurrentIpAllowed) {
            $clockOutBlockReason = 'Clock out must be performed from store machine network.';
        }

        return view('clock.index', compact(
            'user',
            'openPunch',
            'todaySchedule',
            'currentIp',
            'allowedIp',
            'isNetworkRestricted',
            'isCurrentIpAllowed',
            'clockInBlockReason',
            'clockOutBlockReason',
        ));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('location');
        abort_unless($user->canUseWebClock(), 403, 'Insufficient role.');

        $ip = (string) $request->ip();

        $schedule = null;
        if ($user->requires_schedule_for_clock) {
            $schedule = Schedule::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->orderByDesc('starts_at')
                ->first();

            if (! $schedule) {
                return back()->withErrors(['clock' => 'No active approved schedule for this time window.']);
            }
        }

        if ($user->location && $user->location->allowed_ip && $ip !== $user->location->allowed_ip) {
            SecurityWarning::create([
                'user_id' => $user->id,
                'location_id' => $user->location_id,
                'warning_type' => 'CLOCK_IP_MISMATCH',
                'ip_address' => $ip,
                'message' => "Clock in blocked from IP {$ip}. Allowed store machine IP is {$user->location->allowed_ip}.",
            ]);

            return back()->withErrors(['clock' => 'Clock in must be performed from store machine network.']);
        }

        try {
            DB::transaction(function () use ($request, $user, $schedule, $ip): void {
                $open = TimePunch::where('user_id', $user->id)->whereNull('clock_out_at')->lockForUpdate()->first();
                if ($open) {
                    throw new \RuntimeException('You already have an open punch.');
                }

                $punch = TimePunch::create([
                    'user_id' => $user->id,
                    'location_id' => $schedule?->location_id ?? $user->location_id,
                    'kiosk_id' => null,
                    'schedule_id' => $schedule?->id,
                    'source' => 'web',
                    'clock_in_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => (string) $request->userAgent(),
                ]);

                AuditLog::create([
                    'actor_user_id' => $user->id,
                    'target_user_id' => $user->id,
                    'action' => 'CLOCK_IN_WEB',
                    'entity_type' => TimePunch::class,
                    'entity_id' => $punch->id,
                    'after_data' => $punch->toArray(),
                    'ip_address' => $ip,
                    'user_agent' => (string) $request->userAgent(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['clock' => $e->getMessage()]);
        }

        return back()->with('status', 'Clock in successful.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('location');
        abort_unless($user->canUseWebClock(), 403, 'Insufficient role.');

        $ip = (string) $request->ip();

        try {
            DB::transaction(function () use ($request, $user, $ip): void {
                $open = TimePunch::with('schedule')
                    ->where('user_id', $user->id)
                    ->whereNull('clock_out_at')
                    ->lockForUpdate()
                    ->first();

                if (! $open) {
                    throw new \RuntimeException('No open punch found.');
                }

                if ($user->location && $user->location->allowed_ip && $ip !== $user->location->allowed_ip) {
                    SecurityWarning::create([
                        'user_id' => $user->id,
                        'location_id' => $user->location_id,
                        'warning_type' => 'CLOCK_IP_MISMATCH',
                        'ip_address' => $ip,
                        'message' => "Clock out blocked from IP {$ip}. Allowed store machine IP is {$user->location->allowed_ip}.",
                    ]);

                    throw new \RuntimeException('Clock out must be performed from store machine network.');
                }

                $before = $open->toArray();
                $outTime = now();
                $violation = null;

                if ($open->schedule && $outTime->gt($open->schedule->ends_at)) {
                    $outTime = $open->schedule->ends_at;
                    $violation = 'Auto-capped at scheduled end time.';
                }

                $open->update([
                    'clock_out_at' => $outTime,
                    'ip_address' => $ip,
                    'user_agent' => (string) $request->userAgent(),
                    'violation_note' => $violation,
                ]);

                AuditLog::create([
                    'actor_user_id' => $user->id,
                    'target_user_id' => $user->id,
                    'action' => 'CLOCK_OUT_WEB',
                    'entity_type' => TimePunch::class,
                    'entity_id' => $open->id,
                    'before_data' => $before,
                    'after_data' => $open->fresh()->toArray(),
                    'ip_address' => $ip,
                    'user_agent' => (string) $request->userAgent(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['clock' => $e->getMessage()]);
        }

        return back()->with('status', 'Clock out successful.');
    }
}
