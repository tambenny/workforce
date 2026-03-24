<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\TimePunch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PunchController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'exceptions' => ['nullable', 'boolean'],
            'open_now' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        abort_unless($user->canViewPunchLog(), 403, 'Insufficient role.');

        $isManagerView = $user->role === 'admin'
            || (in_array($user->role, ['manager', 'hr'], true) && $user->canViewCurrentStaffReport());
        $canViewCurrentStaff = $user->canViewCurrentStaffReport();
        $canViewPunchPhotos = $user->canViewPunchPhotos();
        $canViewPunchSummary = $user->canViewPunchSummary();
        $showExceptions = (bool) ($data['exceptions'] ?? false);
        $showOpenNow = (bool) ($data['open_now'] ?? false);
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $selectedUserId = (int) ($data['user_id'] ?? 0);

        $query = TimePunch::query();

        if ($isManagerView) {
            if ($user->role === 'manager') {
                $query->where('location_id', $user->location_id);
            }

            if ($selectedUserId > 0) {
                $query->where('user_id', $selectedUserId);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($showExceptions) {
            $query->whereNotNull('violation_note')
                ->where('violation_note', '!=', '');
        }

        if ($showOpenNow) {
            $query->whereNull('clock_out_at');
        }

        if ($dateFrom) {
            $query->whereDate('clock_in_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('clock_in_at', '<=', $dateTo);
        }

        $summaryQuery = clone $query;

        $punches = (clone $query)
            ->with(['location', 'kiosk', 'user', 'schedule'])
            ->latest('clock_in_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'exceptions' => (clone $summaryQuery)
                ->whereNotNull('violation_note')
                ->where('violation_note', '!=', '')
                ->count(),
            'open' => (clone $summaryQuery)->whereNull('clock_out_at')->count(),
            'staff' => (clone $summaryQuery)->distinct('user_id')->count('user_id'),
        ];

        [$scheduleSummaries, $scheduleMessages] = $this->buildPunchScheduleContext($punches->getCollection());

        $staffOptions = collect();
        if ($isManagerView) {
            $staffOptions = User::query()
                ->whereIn('role', ['manager', 'staff'])
                ->where('is_active', true)
                ->when($user->role === 'manager', fn ($query) => $query->where('location_id', $user->location_id))
                ->orderBy('name')
                ->get(['id', 'name', 'staff_id']);
        }

        return view('punches.index', compact('punches', 'isManagerView', 'canViewCurrentStaff', 'canViewPunchPhotos', 'canViewPunchSummary', 'showExceptions', 'showOpenNow', 'dateFrom', 'dateTo', 'selectedUserId', 'scheduleSummaries', 'scheduleMessages', 'staffOptions', 'summary'));
    }

    public function forceClockOut(Request $request, TimePunch $punch): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $actor = $request->user();
        abort_unless($actor->canViewCurrentStaffReport(), 403, 'Insufficient role.');

        if ($actor->role === 'manager' && (int) $punch->location_id !== (int) $actor->location_id) {
            abort(403, 'You cannot manage punches outside your location.');
        }

        if ($punch->clock_out_at) {
            return back()->withErrors(['clock' => 'This punch is already closed.']);
        }

        DB::transaction(function () use ($request, $actor, $punch, $data): void {
            $before = $punch->toArray();
            $note = trim((string) $punch->violation_note);
            $manualNote = 'Manual clock out: ' . trim($data['reason']);

            $punch->update([
                'clock_out_at' => now(),
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'violation_note' => $note !== '' ? $note . ' | ' . $manualNote : $manualNote,
            ]);

            AuditLog::create([
                'actor_user_id' => $actor->id,
                'target_user_id' => $punch->user_id,
                'action' => 'FORCE_CLOCK_OUT',
                'entity_type' => TimePunch::class,
                'entity_id' => $punch->id,
                'reason' => $data['reason'],
                'before_data' => $before,
                'after_data' => $punch->fresh()->toArray(),
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Staff member clocked out manually.');
    }

    public function summary(Request $request): View
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        abort_unless($user->canViewPunchSummary(), 403, 'Insufficient role.');

        $isManagerView = $user->role === 'admin'
            || (in_array($user->role, ['manager', 'hr'], true) && $user->canViewCurrentStaffReport());

        $dateFrom = $data['date_from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $dateTo = $data['date_to'] ?? Carbon::now()->toDateString();
        $selectedLocationId = (int) ($data['location_id'] ?? 0);

        $locations = Location::query()
            ->where('is_active', true)
            ->when($user->role === 'manager', fn ($query) => $query->where('id', $user->location_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view that location.');
        }

        $staffQuery = User::query()
            ->whereIn('role', ['manager', 'staff'])
            ->where('is_active', true)
            ->when($user->role === 'manager', fn ($query) => $query->where('location_id', $user->location_id))
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->orderBy('name');

        if (! $isManagerView) {
            $staffQuery->where('id', $user->id);
        }

        $staff = $staffQuery->get(['id', 'name', 'staff_id']);
        $selectedUserId = (int) ($data['user_id'] ?? 0);

        if ($selectedUserId > 0 && ! $staff->pluck('id')->contains($selectedUserId)) {
            abort(403, 'You are not allowed to view that staff member.');
        }

        $staffIds = $staff->pluck('id');

        $schedules = Schedule::query()
            ->whereIn('user_id', $staffIds)
            ->whereDate('shift_date', '>=', $dateFrom)
            ->whereDate('shift_date', '<=', $dateTo)
            ->where('status', 'approved')
            ->where('change_type', '!=', 'removed_after_approval')
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->get(['user_id', 'starts_at', 'ends_at']);

        $punches = TimePunch::query()
            ->whereIn('user_id', $staffIds)
            ->whereDate('clock_in_at', '>=', $dateFrom)
            ->whereDate('clock_in_at', '<=', $dateTo)
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->get(['user_id', 'clock_in_at', 'clock_out_at']);

        $scheduleSeconds = $schedules
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->sum(fn (Schedule $schedule) => max(0, $schedule->starts_at->diffInSeconds($schedule->ends_at))))
            ->all();

        $punchSeconds = $punches
            ->groupBy('user_id')
            ->map(function ($rows) {
                return $rows->sum(function (TimePunch $punch) {
                    $clockOut = $punch->clock_out_at ?? now();

                    return max(0, $punch->clock_in_at->diffInSeconds($clockOut));
                });
            })
            ->all();

        $rows = $staff
            ->when($selectedUserId > 0, fn ($collection) => $collection->where('id', $selectedUserId))
            ->map(function (User $staffMember) use ($scheduleSeconds, $punchSeconds) {
                $scheduled = (int) ($scheduleSeconds[$staffMember->id] ?? 0);
                $punched = (int) ($punchSeconds[$staffMember->id] ?? 0);

                return [
                    'id' => $staffMember->id,
                    'name' => $staffMember->name,
                    'staff_id' => $staffMember->staff_id,
                    'scheduled_seconds' => $scheduled,
                    'punched_seconds' => $punched,
                    'variance_seconds' => $punched - $scheduled,
                ];
            })
            ->filter(fn (array $row) => $row['scheduled_seconds'] > 0 || $row['punched_seconds'] > 0 || $selectedUserId > 0)
            ->values();

        return view('punches.summary', [
            'rows' => $rows,
            'staff' => $staff,
            'locations' => $locations,
            'selectedLocationId' => $selectedLocationId,
            'selectedUserId' => $selectedUserId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'isManagerView' => $isManagerView,
            'canViewPunchLog' => $user->canViewPunchLog(),
        ]);
    }

    public function current(Request $request): View
    {
        $data = $request->validate([
            'location_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        abort_unless($user->canViewCurrentStaffReport(), 403, 'Insufficient role.');

        $locations = Location::query()
            ->where('is_active', true)
            ->when($user->role === 'manager', fn ($query) => $query->where('id', $user->location_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) ($data['location_id'] ?? 0);

        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view that location.');
        }

        $visibleLocations = $selectedLocationId > 0
            ? $locations->where('id', $selectedLocationId)->values()
            : $locations->values();

        $visibleLocationIds = $visibleLocations->pluck('id')->all();

        $openPunchesQuery = TimePunch::with(['location', 'kiosk', 'user', 'schedule'])
            ->whereNull('clock_out_at');

        if ($visibleLocationIds === []) {
            $openPunchesQuery->whereRaw('1 = 0');
        } else {
            $openPunchesQuery->whereIn('location_id', $visibleLocationIds);
        }

        $openPunches = $openPunchesQuery
            ->orderBy('location_id')
            ->orderBy('clock_in_at')
            ->get();

        [$scheduleSummaries, $scheduleMessages] = $this->buildPunchScheduleContext($openPunches);

        $locationSummaries = $visibleLocations
            ->map(function (Location $location) use ($openPunches): array {
                return [
                    'location' => $location,
                    'open_count' => $openPunches->where('location_id', $location->id)->count(),
                ];
            })
            ->values();

        $locationGroups = $visibleLocations
            ->map(function (Location $location) use ($openPunches): array {
                $locationPunches = $openPunches
                    ->where('location_id', $location->id)
                    ->values();

                return [
                    'location' => $location,
                    'open_count' => $locationPunches->count(),
                    'punches' => $locationPunches,
                ];
            })
            ->filter(fn (array $group) => $selectedLocationId > 0 || $group['open_count'] > 0)
            ->values();

        $selectedLocation = $selectedLocationId > 0
            ? $visibleLocations->firstWhere('id', $selectedLocationId)
            : null;

        return view('punches.current', [
            'locationGroups' => $locationGroups,
            'locationSummaries' => $locationSummaries,
            'locations' => $locations,
            'selectedLocation' => $selectedLocation,
            'selectedLocationId' => $selectedLocationId,
            'scheduleSummaries' => $scheduleSummaries,
            'scheduleMessages' => $scheduleMessages,
            'totalOpenPunches' => $openPunches->count(),
            'activeLocationCount' => $locationSummaries->where('open_count', '>', 0)->count(),
            'isManagerView' => in_array($user->role, ['admin', 'manager', 'hr'], true),
            'canViewPunchLog' => $user->canViewPunchLog(),
            'canViewPunchSummary' => $user->canViewPunchSummary(),
        ]);
    }

    public function photos(Request $request): View
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        abort_unless($user->canViewPunchPhotos(), 403, 'Insufficient role.');

        $dateFrom = $data['date_from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $dateTo = $data['date_to'] ?? Carbon::now()->toDateString();
        $selectedUserId = (int) ($data['user_id'] ?? 0);
        $selectedLocationId = (int) ($data['location_id'] ?? 0);

        $locations = Location::query()
            ->where('is_active', true)
            ->when($user->role === 'manager', fn ($query) => $query->where('id', $user->location_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view that location.');
        }

        $staffOptions = User::query()
            ->whereIn('role', ['manager', 'staff'])
            ->where('is_active', true)
            ->when($user->role === 'manager', fn ($query) => $query->where('location_id', $user->location_id))
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->orderBy('name')
            ->get(['id', 'name', 'staff_id']);

        if ($selectedUserId > 0 && ! $staffOptions->pluck('id')->contains($selectedUserId)) {
            abort(403, 'You are not allowed to view that staff member.');
        }

        $photoQuery = TimePunch::query()
            ->where(function ($query) {
                $query->whereNotNull('clock_in_photo_path')
                    ->orWhereNotNull('clock_out_photo_path');
            })
            ->when($user->role === 'manager', fn ($query) => $query->where('location_id', $user->location_id))
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->when($selectedUserId > 0, fn ($query) => $query->where('user_id', $selectedUserId))
            ->whereDate('clock_in_at', '>=', $dateFrom)
            ->whereDate('clock_in_at', '<=', $dateTo);

        $punches = (clone $photoQuery)
            ->with(['location', 'kiosk', 'user'])
            ->latest('clock_in_at')
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'total' => (clone $photoQuery)->count(),
            'clock_in_photos' => (clone $photoQuery)->whereNotNull('clock_in_photo_path')->count(),
            'clock_out_photos' => (clone $photoQuery)->whereNotNull('clock_out_photo_path')->count(),
            'staff' => (clone $photoQuery)->distinct('user_id')->count('user_id'),
        ];

        $punches->getCollection()->transform(function (TimePunch $punch) {
            $punch->clock_in_photo_url = $punch->clock_in_photo_path
                ? Storage::disk('public')->url($punch->clock_in_photo_path)
                : null;
            $punch->clock_out_photo_url = $punch->clock_out_photo_path
                ? Storage::disk('public')->url($punch->clock_out_photo_path)
                : null;

            return $punch;
        });

        return view('punches.photos', [
            'punches' => $punches,
            'staffOptions' => $staffOptions,
            'locations' => $locations,
            'selectedUserId' => $selectedUserId,
            'selectedLocationId' => $selectedLocationId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summary' => $summary,
        ]);
    }

    private function scheduleSummaryKey(int $userId, string $shiftDate): string
    {
        return $userId . '|' . $shiftDate;
    }

    private function buildPunchScheduleContext(Collection $punches): array
    {
        $scheduleSummaries = [];
        $scheduleMessages = [];

        $userIds = $punches
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $shiftDates = $punches
            ->pluck('clock_in_at')
            ->filter()
            ->map(fn ($clockInAt) => $clockInAt->toDateString())
            ->unique()
            ->values();

        if ($userIds->isEmpty() || $shiftDates->isEmpty()) {
            return [$scheduleSummaries, $scheduleMessages];
        }

        $schedulesByKey = Schedule::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('shift_date', $shiftDates)
            ->where('status', '!=', 'rejected')
            ->where('change_type', '!=', 'removed_after_approval')
            ->orderBy('starts_at')
            ->get(['id', 'user_id', 'shift_date', 'starts_at', 'ends_at', 'status', 'change_type'])
            ->groupBy(fn (Schedule $schedule) => $this->scheduleSummaryKey($schedule->user_id, $schedule->shift_date->toDateString()));

        $scheduleSummaries = $schedulesByKey
            ->map(function ($schedules): string {
                return $schedules
                    ->map(fn (Schedule $schedule) => $schedule->starts_at->timezone(config('app.timezone'))->format('h:i A') . ' - ' . $schedule->ends_at->timezone(config('app.timezone'))->format('h:i A'))
                    ->implode(', ');
            })
            ->all();

        $scheduleMessages = $punches
            ->mapWithKeys(function (TimePunch $punch) use ($schedulesByKey): array {
                $shiftDate = $punch->clock_in_at?->toDateString();
                if (! $shiftDate) {
                    return [$punch->id => null];
                }

                $schedule = $punch->schedule;
                if (! $schedule || $schedule->status === 'rejected' || $schedule->change_type === 'removed_after_approval') {
                    $schedule = $this->findBestScheduleForPunch(
                        $punch,
                        $schedulesByKey->get($this->scheduleSummaryKey($punch->user_id, $shiftDate), collect()),
                    );
                }

                if (! $schedule) {
                    return [$punch->id => null];
                }

                $messages = [];
                if ($punch->clock_in_at && $punch->clock_in_at->lt($schedule->starts_at)) {
                    $messages[] = 'Clocked in before schedule';
                } elseif ($punch->clock_in_at && $punch->clock_in_at->gt($schedule->ends_at)) {
                    $messages[] = 'Clocked in after schedule';
                }

                if ($punch->clock_out_at && $punch->clock_out_at->lt($schedule->starts_at)) {
                    $messages[] = 'Clocked out before schedule';
                } elseif ($punch->clock_out_at && $punch->clock_out_at->gt($schedule->ends_at)) {
                    $messages[] = 'Clocked out after schedule';
                }

                return [$punch->id => $messages ? implode(' | ', $messages) : 'Within scheduled range'];
            })
            ->all();

        return [$scheduleSummaries, $scheduleMessages];
    }

    private function findBestScheduleForPunch(TimePunch $punch, Collection $schedules): ?Schedule
    {
        if ($schedules->isEmpty()) {
            return null;
        }

        $bestSchedule = null;
        $bestScore = -1;
        $bestDistance = PHP_INT_MAX;

        foreach ($schedules as $schedule) {
            $score = 0;
            $distance = PHP_INT_MAX;

            if ($punch->clock_in_at) {
                if ($punch->clock_in_at->betweenIncluded($schedule->starts_at, $schedule->ends_at)) {
                    $score += 4;
                }

                $distance = min(
                    abs($punch->clock_in_at->getTimestamp() - $schedule->starts_at->getTimestamp()),
                    abs($punch->clock_in_at->getTimestamp() - $schedule->ends_at->getTimestamp()),
                );
            }

            if ($punch->clock_out_at && $punch->clock_out_at->betweenIncluded($schedule->starts_at, $schedule->ends_at)) {
                $score += 4;
            }

            if ($punch->clock_in_at) {
                $punchEnd = $punch->clock_out_at ?? $punch->clock_in_at;
                $overlapStart = max($schedule->starts_at->getTimestamp(), $punch->clock_in_at->getTimestamp());
                $overlapEnd = min($schedule->ends_at->getTimestamp(), $punchEnd->getTimestamp());
                $score += max(0, $overlapEnd - $overlapStart);
            }

            if ($score > $bestScore || ($score === $bestScore && $distance < $bestDistance)) {
                $bestSchedule = $schedule;
                $bestScore = $score;
                $bestDistance = $distance;
            }
        }

        return $bestSchedule;
    }
}
