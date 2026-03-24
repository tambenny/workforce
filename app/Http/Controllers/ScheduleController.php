<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleForm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canViewSchedules(), 403, 'Insufficient role.');

        $locations = Location::query()
            ->where('is_active', true)
            ->when(in_array($request->user()->role, ['manager', 'staff'], true), function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) $request->query(
            'location_id',
            in_array($request->user()->role, ['admin', 'hr'], true)
                ? 0
                : $this->defaultAccessibleLocationId($request->user(), $locations)
        );
        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view schedules for this location.');
        }

        $showHistory = $request->boolean('history');
        $staffName = trim((string) $request->query('staff_name', ''));
        $today = Carbon::today()->toDateString();

        $baseFormsQuery = ScheduleForm::query()
            ->from('schedule_forms as f')
            ->join('locations as l', 'l.id', '=', 'f.location_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'f.created_by')
            ->leftJoin('schedules as s', 's.schedule_form_id', '=', 'f.id')
            ->when(! $showHistory, fn ($query) => $query->whereDate('f.shift_date', '>=', $today))
            ->when($selectedLocationId > 0, fn ($query) => $query->where('f.location_id', $selectedLocationId))
            ->when($staffName !== '', function ($query) use ($staffName): void {
                $query->whereExists(function ($sub) use ($staffName): void {
                    $sub->select(DB::raw(1))
                        ->from('schedules as sf')
                        ->join('users as su', 'su.id', '=', 'sf.user_id')
                        ->whereColumn('sf.schedule_form_id', 'f.id')
                        ->where('su.name', 'like', "%{$staffName}%");
                });
            })
            ->selectRaw("
                f.id as form_id,
                f.location_id,
                f.shift_date,
                f.created_by,
                f.version,
                f.status as form_status,
                DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s') as submitted_at,
                COUNT(s.id) as lines_count,
                MIN(s.starts_at) as starts_at_min,
                MAX(s.ends_at) as ends_at_max,
                SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
                SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN s.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                l.name as location_name,
                creator.name as creator_name
            ")
            ->when($request->user()->role === 'staff', function ($query) use ($request): void {
                $query->whereExists(function ($sub) use ($request): void {
                    $sub->select(DB::raw(1))
                        ->from('schedules as sx')
                        ->whereColumn('sx.schedule_form_id', 'f.id')
                        ->where('sx.user_id', $request->user()->id);
                });
            })
            ->when(
                ! in_array($request->user()->role, ['admin', 'hr', 'manager', 'staff'], true) && $request->user()->hasSchedulePermission('create'),
                fn ($query) => $query->where('f.created_by', $request->user()->id)
            )
            ->groupBy(
                'f.id',
                'f.location_id',
                'f.shift_date',
                'f.created_by',
                'f.version',
                'f.status',
                DB::raw("DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s')"),
                'l.name',
                'creator.name'
            );

        $pendingForms = (clone $baseFormsQuery)
            ->where('f.status', 'submitted')
            ->orderByDesc(DB::raw("DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s')"))
            ->paginate(20, ['*'], 'pending_page')
            ->withQueryString();

        $completedForms = (clone $baseFormsQuery)
            ->whereIn('f.status', ['approved', 'rejected', 'partially_approved', 'editing'])
            ->orderByDesc(DB::raw("DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s')"))
            ->paginate(20, ['*'], 'completed_page')
            ->withQueryString();

        $staffSchedules = Schedule::query()
            ->from('schedules as s')
            ->join('schedule_forms as f', 'f.id', '=', 's.schedule_form_id')
            ->join('locations as l', 'l.id', '=', 's.location_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('users as creator', 'creator.id', '=', 's.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 's.approved_by')
            ->when($staffName !== '', fn ($query) => $query->where('u.name', 'like', "%{$staffName}%"))
            ->when(! $showHistory, fn ($query) => $query->whereDate('s.shift_date', '>=', $today))
            ->when($selectedLocationId > 0, fn ($query) => $query->where('s.location_id', $selectedLocationId))
            ->when($request->user()->role === 'staff', fn ($query) => $query->where('s.user_id', $request->user()->id))
            ->when(
                ! in_array($request->user()->role, ['admin', 'hr', 'manager', 'staff'], true) && $request->user()->hasSchedulePermission('create'),
                fn ($query) => $query->where('s.created_by', $request->user()->id)
            )
            ->selectRaw("
                s.id as schedule_id,
                s.schedule_form_id as form_id,
                s.shift_date,
                s.starts_at,
                s.ends_at,
                s.status,
                s.notes,
                l.name as location_name,
                u.name as staff_name,
                creator.name as creator_name,
                approver.name as approver_name
            ")
            ->orderBy('s.shift_date')
            ->orderBy('u.name')
            ->orderBy('s.starts_at')
            ->paginate(20, ['*'], 'staff_page')
            ->withQueryString();

        return view('schedules.index', compact('pendingForms', 'completedForms', 'showHistory', 'locations', 'selectedLocationId', 'staffName', 'staffSchedules'));
    }

    public function summary(Request $request): View
    {
        abort_unless($request->user()->canViewScheduleSummary(), 403, 'Insufficient role.');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'location_id' => ['nullable', 'integer'],
            'staff_name' => ['nullable', 'string', 'max:100'],
        ]);

        $locations = Location::query()
            ->where('is_active', true)
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->user()->role === 'manager' && $locations->isEmpty()) {
            abort(403, 'A manager must be assigned to an active location before viewing weekly schedule summaries.');
        }

        $selectedLocationId = (int) ($data['location_id'] ?? ($request->user()->role === 'manager'
            ? $this->defaultAccessibleLocationId($request->user(), $locations)
            : 0));
        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view that location.');
        }

        $staffName = trim((string) ($data['staff_name'] ?? ''));
        $rangeStart = Carbon::parse($data['date_from'] ?? Carbon::today()->startOfWeek()->toDateString())->startOfDay();
        $rangeEnd = Carbon::parse($data['date_to'] ?? $rangeStart->copy()->endOfWeek()->toDateString())->endOfDay();

        $schedules = Schedule::query()
            ->with([
                'user.position:id,name',
                'location:id,name',
                'creator:id,name',
                'approver:id,name',
                'form:id,location_id,shift_date,created_by,version,status,approved_by,approved_at,created_at',
                'form.creator:id,name',
                'form.approver:id,name',
            ])
            ->whereDate('shift_date', '>=', $rangeStart->toDateString())
            ->whereDate('shift_date', '<=', $rangeEnd->toDateString())
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->when($staffName !== '', function ($query) use ($staffName): void {
                $query->whereHas('user', fn ($staffQuery) => $staffQuery->where('name', 'like', "%{$staffName}%"));
            })
            ->where('change_type', '!=', 'removed_after_approval')
            ->orderBy('shift_date')
            ->orderBy('starts_at')
            ->orderBy('user_id')
            ->get()
            ->map(function (Schedule $schedule): Schedule {
                $schedule->setAttribute('duration_seconds', $this->scheduleDurationSeconds($schedule));

                return $schedule;
            });

        $totals = [
            'planned_seconds' => $schedules->sum('duration_seconds'),
            'approved_seconds' => $schedules->where('status', 'approved')->sum('duration_seconds'),
            'submitted_seconds' => $schedules->where('status', 'submitted')->sum('duration_seconds'),
            'scheduled_seconds' => $schedules
                ->whereIn('status', ['approved', 'submitted'])
                ->sum('duration_seconds'),
            'rejected_seconds' => $schedules->where('status', 'rejected')->sum('duration_seconds'),
            'staff_count' => $schedules->pluck('user_id')->unique()->count(),
            'line_count' => $schedules->count(),
            'form_count' => $schedules->pluck('schedule_form_id')->filter()->unique()->count(),
        ];

        $daySpan = (int) $rangeStart->copy()->startOfDay()->diffInDays($rangeEnd->copy()->startOfDay());

        $dailySummaries = collect(range(0, $daySpan))
            ->map(function (int $offset) use ($rangeStart, $schedules): array {
                $date = $rangeStart->copy()->addDays($offset);
                $daySchedules = $schedules
                    ->filter(fn (Schedule $schedule): bool => $schedule->shift_date->isSameDay($date))
                    ->values();

                return [
                    'date' => $date,
                    'planned_seconds' => $daySchedules->sum('duration_seconds'),
                    'approved_seconds' => $daySchedules->where('status', 'approved')->sum('duration_seconds'),
                    'submitted_seconds' => $daySchedules->where('status', 'submitted')->sum('duration_seconds'),
                    'scheduled_seconds' => $daySchedules
                        ->whereIn('status', ['approved', 'submitted'])
                        ->sum('duration_seconds'),
                    'rejected_seconds' => $daySchedules->where('status', 'rejected')->sum('duration_seconds'),
                    'line_count' => $daySchedules->count(),
                    'staff_count' => $daySchedules->pluck('user_id')->unique()->count(),
                ];
            });

        $staffSummaries = $schedules
            ->groupBy('user_id')
            ->map(function (Collection $rows): array {
                /** @var \App\Models\Schedule $first */
                $first = $rows->first();

                return [
                    'user' => $first->user,
                    'line_count' => $rows->count(),
                    'planned_seconds' => $rows->sum('duration_seconds'),
                    'approved_seconds' => $rows->where('status', 'approved')->sum('duration_seconds'),
                    'submitted_seconds' => $rows->where('status', 'submitted')->sum('duration_seconds'),
                    'rejected_seconds' => $rows->where('status', 'rejected')->sum('duration_seconds'),
                ];
            })
            ->sortByDesc('planned_seconds')
            ->values();

        $formSummaries = $schedules
            ->groupBy('schedule_form_id')
            ->map(function (Collection $rows, int|string $formId): array {
                /** @var \App\Models\Schedule $first */
                $first = $rows->first();
                $form = $first->form;
                $sortedByStart = $rows->sortBy('starts_at')->values();
                $sortedByEnd = $rows->sortByDesc('ends_at')->values();

                return [
                    'form_id' => (int) $formId,
                    'form' => $form,
                    'shift_date' => $form?->shift_date ?? $first->shift_date,
                    'location_name' => $first->location?->name ?? '-',
                    'form_status' => $form?->status ?? $first->status,
                    'creator_name' => $form?->creator?->name ?? 'System',
                    'approver_name' => $form?->approver?->name,
                    'submitted_at' => $form?->created_at ?? $first->created_at,
                    'lines_count' => $rows->count(),
                    'planned_seconds' => $rows->sum('duration_seconds'),
                    'approved_count' => $rows->where('status', 'approved')->count(),
                    'submitted_count' => $rows->where('status', 'submitted')->count(),
                    'rejected_count' => $rows->where('status', 'rejected')->count(),
                    'starts_at_min' => $sortedByStart->first()?->starts_at,
                    'ends_at_max' => $sortedByEnd->first()?->ends_at,
                ];
            })
            ->sortBy(fn (array $row) => ($row['shift_date']?->format('Ymd') ?? '00000000') . '|' . ($row['starts_at_min']?->format('His') ?? '000000'))
            ->values();

        $selectedLocation = $selectedLocationId > 0
            ? $locations->firstWhere('id', $selectedLocationId)
            : null;

        return view('schedules.summary', [
            'locations' => $locations,
            'selectedLocation' => $selectedLocation,
            'selectedLocationId' => $selectedLocationId,
            'staffName' => $staffName,
            'dateFrom' => $rangeStart->toDateString(),
            'dateTo' => $rangeEnd->toDateString(),
            'totals' => $totals,
            'dailySummaries' => $dailySummaries,
            'staffSummaries' => $staffSummaries,
            'formSummaries' => $formSummaries,
            'scheduleRows' => $schedules,
        ]);
    }

    public function timeline(Request $request): View
    {
        abort_unless($request->user()->canViewScheduleDetails(), 403, 'Insufficient role.');

        $data = $request->validate([
            'shift_date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer'],
            'staff_name' => ['nullable', 'string', 'max:100'],
        ]);

        $locations = Location::query()
            ->where('is_active', true)
            ->when(in_array($request->user()->role, ['manager', 'staff'], true), function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->user()->role === 'manager' && $locations->isEmpty()) {
            abort(403, 'A manager must be assigned to an active location before viewing schedule timelines.');
        }

        $selectedDate = Carbon::parse($data['shift_date'] ?? Carbon::today()->toDateString())->toDateString();
        $selectedLocationId = (int) ($data['location_id'] ?? (in_array($request->user()->role, ['manager', 'staff'], true)
            ? $this->defaultAccessibleLocationId($request->user(), $locations)
            : 0));

        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view that location.');
        }

        $staffName = trim((string) ($data['staff_name'] ?? ''));

        $schedules = Schedule::query()
            ->with([
                'user.position:id,name',
                'location:id,name',
                'form:id,location_id,shift_date,created_by,version,status',
            ])
            ->whereDate('shift_date', $selectedDate)
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->when($staffName !== '', function ($query) use ($staffName): void {
                $query->whereHas('user', fn ($staffQuery) => $staffQuery->where('name', 'like', "%{$staffName}%"));
            })
            ->when($request->user()->role === 'staff', fn ($query) => $query->where('user_id', $request->user()->id))
            ->where('change_type', '!=', 'removed_after_approval')
            ->orderBy('starts_at')
            ->orderBy('user_id')
            ->get();

        $defaultChartStart = Carbon::parse("{$selectedDate} 06:00:00");
        $defaultChartEnd = Carbon::parse("{$selectedDate} 22:00:00");
        $selectedDateEnd = Carbon::parse($selectedDate)->addDay()->startOfDay();
        $earliestStart = $schedules->map(fn (Schedule $schedule) => $schedule->starts_at->copy())->sort()->first();
        $latestEnd = $schedules->map(fn (Schedule $schedule) => $schedule->ends_at->copy())->sortDesc()->first();

        $chartStart = $earliestStart?->copy()->startOfHour() ?? $defaultChartStart;
        $chartEnd = $latestEnd
            ? (($latestEnd->minute === 0 && $latestEnd->second === 0)
                ? $latestEnd->copy()
                : $latestEnd->copy()->addHour()->startOfHour())
            : $defaultChartEnd;
        $chartEnd = $chartEnd->gt($selectedDateEnd)
            ? $selectedDateEnd->copy()
            : $chartEnd;

        if ($chartEnd->lte($chartStart)) {
            $chartEnd = $chartStart->copy()->addHour();
        }

        $totalMinutes = max(60, $chartStart->diffInMinutes($chartEnd));
        $timelineMarkers = $this->buildScheduleTimelineMarkers($chartStart, $chartEnd, $totalMinutes);
        $timelineRows = $this->buildScheduleTimelineRows($schedules, $chartStart, $totalMinutes);

        $selectedLocation = $selectedLocationId > 0
            ? $locations->firstWhere('id', $selectedLocationId)
            : null;

        return view('schedules.timeline', [
            'locations' => $locations,
            'selectedDate' => $selectedDate,
            'selectedLocation' => $selectedLocation,
            'selectedLocationId' => $selectedLocationId,
            'staffName' => $staffName,
            'timelineRows' => $timelineRows,
            'timelineMarkers' => $timelineMarkers,
            'chartStart' => $chartStart,
            'chartEnd' => $chartEnd,
            'summary' => [
                'staff_count' => $timelineRows->count(),
                'shift_count' => $schedules->count(),
                'approved_count' => $schedules->where('status', 'approved')->count(),
                'submitted_count' => $schedules->where('status', 'submitted')->count(),
                'rejected_count' => $schedules->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function form(Request $request): View
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
        ]);

        $form = ScheduleForm::with(['location', 'creator', 'approver'])->findOrFail((int) $data['form_id']);

        if ($request->user()->role === 'manager') {
            abort_if((int) $form->location_id !== (int) $request->user()->location_id, 403, 'You cannot access this form.');
        }

        abort_unless($request->user()->canViewScheduleDetails(), 403, 'Insufficient role.');

        $schedules = $this->formQuery((int) $data['form_id'])
            ->with(['user.position', 'location', 'creator', 'approver', 'rejector'])
            ->when($request->user()->role === 'staff', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->get()
            ->sortBy([
                fn (Schedule $schedule) => $schedule->user?->staff_id ?: 'zzzzzz',
                fn (Schedule $schedule) => $schedule->user?->name ?: '',
                fn (Schedule $schedule) => $schedule->starts_at?->format('His') ?: '999999',
                fn (Schedule $schedule) => $schedule->id,
            ])
            ->values();

        abort_if($schedules->isEmpty(), 404);

        $isApprovalView = $request->boolean('approval');
        $canManage = $request->user()->hasSchedulePermission('create') || $request->user()->hasSchedulePermission('approve');
        $canApprove = $request->user()->hasSchedulePermission('approve');
        $hasReapprovalChanges = $this->formHasReapprovalChanges($form->id);
        $hasPendingModificationUnlock = $request->boolean('edit') && $this->hasPendingModificationUnlock($request, $form);
        if ($form->status === 'editing' && ! $hasReapprovalChanges && ! $hasPendingModificationUnlock) {
            $form->update([
                'version' => max(1, $form->version - 1),
                'status' => 'approved',
            ]);
            $form->refresh();
        }

        $canReopenForEdit = $this->canModifyForm($request->user(), $form)
            && in_array($form->status, ['approved', 'editing'], true)
            && ! $hasPendingModificationUnlock;
        $canSubmitReapproval = $this->canModifyForm($request->user(), $form)
            && $form->status === 'editing'
            && $hasPendingModificationUnlock;
        $canCancelEditing = $this->canModifyForm($request->user(), $form)
            && (
                ($form->status === 'editing' && ! $hasReapprovalChanges)
                || ($form->status === 'approved' && $hasPendingModificationUnlock)
            );
        $addableStaff = collect();
        if (! $isApprovalView && $this->canModifyForm($request->user(), $form)) {
            $addableStaff = $this->staffScopeForScheduler($request->user())
                ->where('is_active', true)
                ->where('location_id', $form->location_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('schedules.form', [
            'form' => $form,
            'schedules' => $schedules,
            'canManage' => $canManage,
            'canApprove' => $canApprove,
            'isApprovalView' => $isApprovalView,
            'canReopenForEdit' => $canReopenForEdit,
            'canSubmitReapproval' => $canSubmitReapproval,
            'canCancelEditing' => $canCancelEditing,
            'hasReapprovalChanges' => $hasReapprovalChanges,
            'hasPendingModificationUnlock' => $hasPendingModificationUnlock,
            'addableStaff' => $addableStaff,
        ]);
    }

    public function create(Request $request): View
    {
        if ($request->user()->role === 'manager' && ! $request->user()->location_id) {
            abort(403, 'A manager must be assigned to a location before creating schedules.');
        }

        $locations = Location::query()
            ->where('is_active', true)
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) old('location_id', $request->query('location_id', $this->defaultAccessibleLocationId($request->user(), $locations)));
        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to create schedules for this location.');
        }

        $staff = $this->staffScopeForScheduler($request->user())
            ->with('position')
            ->where('is_active', true)
            ->when($selectedLocationId > 0, fn ($query) => $query->where('location_id', $selectedLocationId))
            ->orderBy('name')
            ->get();

        return view('schedules.create', compact('staff', 'locations', 'selectedLocationId'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->role === 'manager' && ! $request->user()->location_id) {
            return back()->withErrors(['roster' => 'Your manager account must be assigned to a location before creating schedules.'])->withInput();
        }

        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'shift_date' => ['required', 'date'],
            'roster' => ['required', 'array'],
            'roster.*.selected' => ['nullable', 'boolean'],
            'roster.*.clock_in' => ['nullable', 'date_format:H:i'],
            'roster.*.clock_out' => ['nullable', 'date_format:H:i'],
            'roster.*.lines' => ['nullable', 'array'],
            'roster.*.lines.*.selected' => ['nullable', 'boolean'],
            'roster.*.lines.*.clock_in' => ['nullable', 'date_format:H:i'],
            'roster.*.lines.*.clock_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $locationId = (int) $data['location_id'];
        if ($request->user()->role === 'manager' && $locationId !== (int) $request->user()->location_id) {
            return back()->withErrors(['roster' => 'You can only create schedules for your own location.'])->withInput();
        }

        $selected = $this->selectedRosterEntries($data['roster']);

        if ($selected->isEmpty()) {
            return back()->withErrors(['roster' => 'Select at least one roster entry.'])->withInput();
        }

        $staff = $this->staffScopeForScheduler($request->user())
            ->with('location')
            ->where('is_active', true)
            ->where('location_id', $locationId)
            ->whereIn('id', $selected->pluck('user_id')->unique())
            ->get()
            ->keyBy('id');

        $shiftDate = Carbon::parse($data['shift_date'])->toDateString();
        $validatedEntries = [];

        foreach ($selected as $entry) {
            $user = $staff->get((int) $entry['user_id']);

            if (! $user) {
                return back()->withErrors(['roster' => 'One or more selected staff are not allowed for your location scope.'])->withInput();
            }

            if ($request->user()->role === 'manager' && (int) $user->location_id !== (int) $request->user()->location_id) {
                return back()->withErrors(['roster' => "Selected user {$user->name} is outside your assigned location."])->withInput();
            }

            if (! $user->location_id) {
                return back()->withErrors(['roster' => "Selected user {$user->name} must have a location."])->withInput();
            }

            $clockIn = $entry['clock_in'] ?? null;
            $clockOut = $entry['clock_out'] ?? null;
            if (! $clockIn || ! $clockOut) {
                return back()->withErrors(['roster' => "Clock in and clock out are required for {$user->name}."])->withInput();
            }

            $scheduleWindow = $this->buildScheduleWindow($shiftDate, $clockIn, $clockOut);
            if (! $scheduleWindow) {
                return back()->withErrors(['roster' => "Clock out cannot match clock in for {$user->name}. Use an earlier clock out time for next-day shifts."])->withInput();
            }

            [$startsAt, $endsAt] = $scheduleWindow;

            $overlappingSchedules = $this->findOverlappingSchedules(
                $user->id,
                $startsAt,
                $endsAt,
            );

            if ($overlappingSchedules->isNotEmpty()) {
                return back()
                    ->withErrors(['roster' => $this->scheduleOverlapErrorMessage($user->name, $overlappingSchedules)])
                    ->withInput();
            }

            $overlappingDraftEntries = $this->findOverlappingDraftEntries(
                collect($validatedEntries),
                $user->id,
                $startsAt,
                $endsAt,
            );

            if ($overlappingDraftEntries->isNotEmpty()) {
                return back()
                    ->withErrors(['roster' => $this->scheduleOverlapErrorMessage($user->name, $overlappingDraftEntries)])
                    ->withInput();
            }

            $validatedEntries[] = [
                'user' => $user,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        }

        if (empty($validatedEntries)) {
            return back()->withErrors(['roster' => 'No schedules were created.'])->withInput();
        }

        DB::transaction(function () use ($request, $locationId, $shiftDate, $data, $validatedEntries): void {
            $form = ScheduleForm::create([
                'location_id' => $locationId,
                'shift_date' => $shiftDate,
                'created_by' => $request->user()->id,
                'status' => 'submitted',
            ]);

            foreach ($validatedEntries as $entry) {
                /** @var \App\Models\User $user */
                $user = $entry['user'];
                /** @var \Illuminate\Support\Carbon $startsAt */
                $startsAt = $entry['starts_at'];
                /** @var \Illuminate\Support\Carbon $endsAt */
                $endsAt = $entry['ends_at'];

                $schedule = Schedule::create([
                    'schedule_form_id' => $form->id,
                    'reapproval_cycle' => $form->version,
                    'user_id' => $user->id,
                    'location_id' => $user->location_id,
                    'shift_date' => $shiftDate,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'submitted',
                    'change_type' => 'original',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                AuditLog::create([
                    'actor_user_id' => $request->user()->id,
                    'target_user_id' => $user->id,
                    'action' => 'SCHEDULE_SUBMITTED',
                    'entity_type' => Schedule::class,
                    'entity_id' => $schedule->id,
                    'after_data' => $schedule->toArray(),
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]);
            }
        });

        return redirect()->route('schedules.index')->with('status', count($validatedEntries) . ' schedule(s) submitted for approval.');
    }

    private function staffScopeForScheduler(User $actor): Builder
    {
        $query = User::query();

        if ($actor->role === 'manager') {
            return $query
                ->where('location_id', $actor->location_id)
                ->where(function ($q) use ($actor): void {
                    $q->where('role', 'staff')
                        ->orWhere('id', $actor->id);
                });
        }

        return $query->whereIn('role', ['manager', 'staff']);
    }

    public function approvals(Request $request): View
    {
        $locations = Location::query()
            ->where('is_active', true)
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) $request->query(
            'location_id',
            $request->user()->role === 'manager'
                ? $this->defaultAccessibleLocationId($request->user(), $locations)
                : 0
        );
        if ($selectedLocationId > 0 && ! $locations->pluck('id')->contains($selectedLocationId)) {
            abort(403, 'You are not allowed to view approvals for this location.');
        }
        $showHistory = $request->boolean('history');
        $today = Carbon::today()->toDateString();

        $forms = ScheduleForm::query()
            ->from('schedule_forms as f')
            ->join('locations as l', 'l.id', '=', 'f.location_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'f.created_by')
            ->leftJoin('schedules as s', 's.schedule_form_id', '=', 'f.id')
            ->where('f.status', 'submitted')
            ->when($selectedLocationId > 0, fn ($query) => $query->where('f.location_id', $selectedLocationId))
            ->when(! $showHistory, fn ($query) => $query->whereDate('f.shift_date', '>=', $today))
            ->selectRaw("
                f.id as form_id,
                f.location_id,
                f.shift_date,
                f.created_by,
                f.version,
                f.status as form_status,
                DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s') as submitted_at,
                COUNT(s.id) as lines_count,
                MIN(s.starts_at) as starts_at_min,
                MAX(s.ends_at) as ends_at_max,
                l.name as location_name,
                creator.name as creator_name
            ")
            ->groupBy(
                'f.id',
                'f.location_id',
                'f.shift_date',
                'f.created_by',
                'f.version',
                'f.status',
                DB::raw("DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s')"),
                'l.name',
                'creator.name'
            )
            ->orderByDesc('f.shift_date')
            ->orderByDesc(DB::raw("DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s')"))
            ->paginate(20)
            ->withQueryString();

        return view('schedules.approvals', compact('locations', 'forms', 'selectedLocationId', 'showHistory'));
    }

    public function approve(Request $request, Schedule $schedule): RedirectResponse
    {
        if ($schedule->status !== 'submitted') {
            return back()->withErrors(['status' => 'Only submitted schedules can be approved.']);
        }

        $before = $schedule->toArray();
        $schedule->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $schedule->user_id,
            'action' => 'SCHEDULE_APPROVED',
            'entity_type' => Schedule::class,
            'entity_id' => $schedule->id,
            'before_data' => $before,
            'after_data' => $schedule->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $this->syncFormStatus((int) $schedule->schedule_form_id);

        return back()->with('status', 'Schedule approved.');
    }

    public function reject(Request $request, Schedule $schedule): RedirectResponse
    {
        if (! in_array($schedule->status, ['submitted', 'approved'], true)) {
            return back()->withErrors(['status' => 'Only submitted or approved schedules can be rejected.']);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $reason = trim((string) ($data['reason'] ?? '')) ?: 'Rejected by approver';

        $before = $schedule->toArray();
        $schedule->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $schedule->user_id,
            'action' => 'SCHEDULE_REJECTED',
            'entity_type' => Schedule::class,
            'entity_id' => $schedule->id,
            'reason' => $reason,
            'before_data' => $before,
            'after_data' => $schedule->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $this->syncFormStatus((int) $schedule->schedule_form_id);

        return back()->with('status', 'Schedule rejected.');
    }

    public function approveForm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if ($request->user()->role === 'manager' && (int) $form->location_id !== (int) $request->user()->location_id) {
            abort(403, 'You are not allowed to approve this form.');
        }

        $query = $this->formQuery($form->id)
            ->where('status', 'submitted')
            ->where('location_id', $form->location_id);

        $schedules = $query->get();
        if ($schedules->isEmpty()) {
            return back()->withErrors(['status' => 'No submitted lines found for this form.']);
        }

        foreach ($schedules as $schedule) {
            $before = $schedule->toArray();
            $schedule->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            AuditLog::create([
                'actor_user_id' => $request->user()->id,
                'target_user_id' => $schedule->user_id,
                'action' => 'SCHEDULE_APPROVED',
                'entity_type' => Schedule::class,
                'entity_id' => $schedule->id,
                'before_data' => $before,
                'after_data' => $schedule->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        $form->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->route('schedules.approvals', ['location_id' => $form->location_id])->with('status', 'Whole form approved.');
    }

    public function rejectForm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if ($request->user()->role === 'manager' && (int) $form->location_id !== (int) $request->user()->location_id) {
            abort(403, 'You are not allowed to reject this form.');
        }

        $query = $this->formQuery($form->id)
            ->where('status', 'submitted')
            ->where('location_id', $form->location_id);

        $schedules = $query->get();
        if ($schedules->isEmpty()) {
            return back()->withErrors(['status' => 'No submitted lines found for this form.']);
        }

        foreach ($schedules as $schedule) {
            $before = $schedule->toArray();
            $schedule->update([
                'status' => 'rejected',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'rejection_reason' => $data['reason'],
                'approved_by' => null,
                'approved_at' => null,
            ]);

            AuditLog::create([
                'actor_user_id' => $request->user()->id,
                'target_user_id' => $schedule->user_id,
                'action' => 'SCHEDULE_REJECTED',
                'entity_type' => Schedule::class,
                'entity_id' => $schedule->id,
                'reason' => $data['reason'],
                'before_data' => $before,
                'after_data' => $schedule->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        $form->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
            'rejection_reason' => $data['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->route('schedules.approvals', ['location_id' => $form->location_id])->with('status', 'Whole form rejected.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $schedule->loadMissing('user', 'form');

        if (! $this->canManageSchedule($request->user(), $schedule)) {
            abort(403, 'You are not allowed to modify this schedule.');
        }
        $hasPendingModificationUnlock = $schedule->form
            && in_array($schedule->form->status, ['approved', 'editing'], true)
            && $this->hasPendingModificationUnlock($request, $schedule->form);

        if ($schedule->form && in_array($schedule->form->status, ['approved', 'editing'], true) && ! $hasPendingModificationUnlock) {
            return back()->withErrors(['schedule' => 'This approved form is locked. Click "Modify Schedule" first.']);
        }

        $data = $request->validate([
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shiftDate = $schedule->shift_date->toDateString();
        $scheduleWindow = $this->buildScheduleWindow($shiftDate, $data['clock_in'], $data['clock_out']);
        if (! $scheduleWindow) {
            return back()->withErrors(['schedule' => 'Clock out cannot match clock in. Use an earlier clock out time for next-day shifts.']);
        }

        [$startsAt, $endsAt] = $scheduleWindow;

        $overlappingSchedules = $this->findOverlappingSchedules(
            $schedule->user_id,
            $startsAt,
            $endsAt,
            $schedule->id,
        );

        if ($overlappingSchedules->isNotEmpty()) {
            return back()->withErrors([
                'schedule' => $this->scheduleOverlapErrorMessage($schedule->user?->name ?? 'This staff member', $overlappingSchedules),
            ]);
        }

        $before = $schedule->toArray();

        DB::transaction(function () use ($request, $schedule, $startsAt, $endsAt, $data, $hasPendingModificationUnlock): void {
            $form = $schedule->form?->fresh();
            $reapprovalReason = $form ? $this->getPendingModificationReason($request, $form) : null;
            $isReapprovalStart = $form && $form->status === 'approved' && $hasPendingModificationUnlock;
            $isEditing = ($form && $form->status === 'editing') || $isReapprovalStart;
            $newChangeType = $schedule->change_type;
            if ($isEditing && $schedule->change_type !== 'added_after_approval') {
                $newChangeType = 'modified_after_approval';
            }

            if ($isReapprovalStart) {
                $form->update([
                    'version' => $form->version + 1,
                    'status' => 'editing',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            $schedule->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $data['notes'] ?? $schedule->notes,
                'change_type' => $newChangeType,
                'change_reason' => $isEditing ? ($reapprovalReason ? "Changed after approval: {$reapprovalReason}" : 'Changed during re-approval cycle') : $schedule->change_reason,
                'changed_by' => $isEditing ? $request->user()->id : $schedule->changed_by,
                'changed_at' => $isEditing ? now() : $schedule->changed_at,
                'status' => $isEditing ? 'submitted' : 'submitted',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            if (! $isEditing) {
                Schedule::query()
                    ->where('schedule_form_id', $schedule->schedule_form_id)
                    ->update([
                        'status' => 'submitted',
                        'approved_by' => null,
                        'approved_at' => null,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'rejection_reason' => null,
                    ]);
            }

            if ($form) {
                $nextFormStatus = $isEditing ? 'editing' : 'submitted';
                $form->update([
                    'status' => $nextFormStatus,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }
        });

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $schedule->user_id,
            'action' => 'SCHEDULE_UPDATED',
            'entity_type' => Schedule::class,
            'entity_id' => $schedule->id,
            'before_data' => $before,
            'after_data' => $schedule->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $statusMessage = $schedule->form && $schedule->form->status === 'editing'
            ? 'Schedule updated. Click "Submit for Re-Approval" when done.'
            : 'Schedule updated and re-submitted for approval.';

        return back()->with('status', $statusMessage);
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        if (! $this->canDeleteSchedule($request->user(), $schedule)) {
            abort(403, 'You are not allowed to delete this schedule.');
        }
        $hasPendingModificationUnlock = $schedule->form
            && in_array($schedule->form->status, ['approved', 'editing'], true)
            && $this->hasPendingModificationUnlock($request, $schedule->form);

        if ($schedule->form && in_array($schedule->form->status, ['approved', 'editing'], true) && ! $hasPendingModificationUnlock) {
            return back()->withErrors(['schedule' => 'This approved form is locked. Click "Modify Schedule" first.']);
        }

        if ($schedule->punches()->exists()) {
            return back()->withErrors(['schedule' => 'Cannot delete schedule with time punches.']);
        }

        $before = $schedule->toArray();
        $targetUserId = $schedule->user_id;
        $scheduleId = $schedule->id;
        $formId = $schedule->schedule_form_id;

        DB::transaction(function () use ($request, $schedule, $formId, $hasPendingModificationUnlock): void {
            $formBeforeDelete = $schedule->form?->fresh();
            $isReapprovalStart = $formBeforeDelete && $formBeforeDelete->status === 'approved' && $hasPendingModificationUnlock;
            $isEditing = ($formBeforeDelete && $formBeforeDelete->status === 'editing') || $isReapprovalStart;

            if ($isReapprovalStart) {
                $formBeforeDelete->update([
                    'version' => $formBeforeDelete->version + 1,
                    'status' => 'editing',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            $schedule->delete();

            $remaining = Schedule::query()->where('schedule_form_id', $formId)->count();
            if ($remaining === 0) {
                ScheduleForm::query()->where('id', $formId)->delete();
                return;
            }

            if (! $isEditing) {
                Schedule::query()
                    ->where('schedule_form_id', $formId)
                    ->update([
                        'status' => 'submitted',
                        'approved_by' => null,
                        'approved_at' => null,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'rejection_reason' => null,
                    ]);
            }

            $form = ScheduleForm::query()->find($formId);
            if ($form) {
                $nextFormStatus = $form->status === 'editing' ? 'editing' : 'submitted';
                $form->update([
                    'status' => $nextFormStatus,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }
        });

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $targetUserId,
            'action' => 'SCHEDULE_DELETED',
            'entity_type' => Schedule::class,
            'entity_id' => $scheduleId,
            'before_data' => $before,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $formAfterDelete = ScheduleForm::query()->find($formId);
        $statusMessage = $formAfterDelete && $formAfterDelete->status === 'editing'
            ? 'Schedule line deleted. Click "Submit for Re-Approval" when done.'
            : 'Schedule deleted.';

        return back()->with('status', $statusMessage);
    }

    public function reopenFormForModification(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if (! $this->canModifyForm($request->user(), $form)) {
            abort(403, 'You are not allowed to modify this schedule form.');
        }
        if (! in_array($form->status, ['approved', 'editing'], true)) {
            return back()->withErrors(['status' => 'Only approved or in-progress forms can be unlocked for modification.']);
        }

        $before = $form->toArray();
        $this->rememberPendingModificationUnlock($request, $form, $data['reason']);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $form->created_by,
            'action' => 'SCHEDULE_FORM_MODIFICATION_UNLOCKED',
            'entity_type' => ScheduleForm::class,
            'entity_id' => $form->id,
            'reason' => $data['reason'],
            'before_data' => $before,
            'after_data' => $form->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('schedules.form', [
            'form_id' => $form->id,
            'edit' => 1,
        ])->with('status', $form->status === 'editing'
            ? 'Modification unlocked for this session. Continue your changes, then submit for re-approval.'
            : 'Modification unlocked for this session. The form stays locked until you save a real change.');
    }

    public function cancelFormEditing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if (! $this->canModifyForm($request->user(), $form)) {
            abort(403, 'You are not allowed to modify this schedule form.');
        }
        if ($form->status === 'approved' && $this->hasPendingModificationUnlock($request, $form)) {
            $this->forgetPendingModificationUnlock($request, $form->id);

            AuditLog::create([
                'actor_user_id' => $request->user()->id,
                'target_user_id' => $form->created_by,
                'action' => 'SCHEDULE_FORM_MODIFICATION_UNLOCK_CANCELED',
                'entity_type' => ScheduleForm::class,
                'entity_id' => $form->id,
                'before_data' => $form->toArray(),
                'after_data' => $form->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return back()->with('status', 'Modification canceled. The form remains locked.');
        }

        if ($form->status !== 'editing') {
            return back()->withErrors(['status' => 'Form is not in editing mode.']);
        }

        $hasChanges = $this->formHasReapprovalChanges($form->id);

        if ($hasChanges) {
            return back()->withErrors(['status' => 'This form already has changes. Submit it for re-approval instead of canceling.']);
        }

        $before = $form->toArray();

        DB::transaction(function () use ($form): void {
            $form->update([
                'version' => max(1, $form->version - 1),
                'status' => 'approved',
            ]);
        });

        $this->forgetPendingModificationUnlock($request, $form->id);

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $form->created_by,
            'action' => 'SCHEDULE_FORM_EDITING_CANCELED',
            'entity_type' => ScheduleForm::class,
            'entity_id' => $form->id,
            'before_data' => $before,
            'after_data' => $form->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Editing canceled. The form is locked again.');
    }

    public function addLineToForm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if (! $this->canModifyForm($request->user(), $form)) {
            abort(403, 'You are not allowed to add staff to this form.');
        }
        $hasPendingModificationUnlock = in_array($form->status, ['approved', 'editing'], true)
            && $this->hasPendingModificationUnlock($request, $form);

        if (in_array($form->status, ['approved', 'editing'], true) && ! $hasPendingModificationUnlock) {
            return back()->withErrors(['schedule' => 'This approved form is locked. Click "Modify Schedule" first.']);
        }

        $staff = $this->staffScopeForScheduler($request->user())
            ->where('is_active', true)
            ->where('location_id', $form->location_id)
            ->where('id', (int) $data['user_id'])
            ->first();
        if (! $staff) {
            return back()->withErrors(['schedule' => 'Selected staff is outside your allowed location scope.']);
        }

        $shiftDate = $form->shift_date->toDateString();
        $scheduleWindow = $this->buildScheduleWindow($shiftDate, $data['clock_in'], $data['clock_out']);
        if (! $scheduleWindow) {
            return back()->withErrors(['schedule' => 'Clock out cannot match clock in. Use an earlier clock out time for next-day shifts.']);
        }

        [$startsAt, $endsAt] = $scheduleWindow;

        $overlappingSchedules = $this->findOverlappingSchedules(
            $staff->id,
            $startsAt,
            $endsAt,
        );

        if ($overlappingSchedules->isNotEmpty()) {
            return back()->withErrors([
                'schedule' => $this->scheduleOverlapErrorMessage($staff->name, $overlappingSchedules),
            ]);
        }

        DB::transaction(function () use ($request, $form, $staff, $shiftDate, $startsAt, $endsAt, $data, $hasPendingModificationUnlock): void {
            $form = $form->fresh();
            $reapprovalReason = $this->getPendingModificationReason($request, $form);
            $isReapprovalStart = $form->status === 'approved' && $hasPendingModificationUnlock;

            if ($isReapprovalStart) {
                $form->update([
                    'version' => $form->version + 1,
                    'status' => 'editing',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            $schedule = Schedule::create([
                'schedule_form_id' => $form->id,
                'reapproval_cycle' => $form->version,
                'user_id' => $staff->id,
                'location_id' => $form->location_id,
                'shift_date' => $shiftDate,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'submitted',
                'change_type' => $form->status === 'editing' ? 'added_after_approval' : 'original',
                'change_reason' => $form->status === 'editing'
                    ? ($reapprovalReason ? "Added after approval: {$reapprovalReason}" : 'Added during re-approval cycle')
                    : null,
                'changed_by' => $form->status === 'editing' ? $request->user()->id : null,
                'changed_at' => $form->status === 'editing' ? now() : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $nextFormStatus = $form->status === 'editing' ? 'editing' : 'submitted';
            $form->update([
                'status' => $nextFormStatus,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            AuditLog::create([
                'actor_user_id' => $request->user()->id,
                'target_user_id' => $staff->id,
                'action' => 'SCHEDULE_ADDED_TO_FORM',
                'entity_type' => Schedule::class,
                'entity_id' => $schedule->id,
                'after_data' => $schedule->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        $statusMessage = $form->status === 'editing'
            ? 'Staff line added. Click "Submit for Re-Approval" when done.'
            : 'Staff line added and form re-submitted for approval.';

        return back()->with('status', $statusMessage);
    }

    public function submitFormForReapproval(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
        ]);

        $form = ScheduleForm::findOrFail((int) $data['form_id']);
        if (! $this->canModifyForm($request->user(), $form)) {
            abort(403, 'You are not allowed to submit this form for re-approval.');
        }
        if ($form->status !== 'editing') {
            return back()->withErrors(['status' => 'Form is not in editing mode.']);
        }

        $before = $form->toArray();
        DB::transaction(function () use ($form): void {
            Schedule::query()
                ->where('schedule_form_id', $form->id)
                ->whereIn('change_type', ['added_after_approval', 'modified_after_approval', 'removed_after_approval'])
                ->update([
                    'status' => 'submitted',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);

            $form->update([
                'status' => 'submitted',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
        });

        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $form->created_by,
            'action' => 'SCHEDULE_FORM_SUBMITTED_FOR_REAPPROVAL',
            'entity_type' => ScheduleForm::class,
            'entity_id' => $form->id,
            'before_data' => $before,
            'after_data' => $form->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $this->forgetPendingModificationUnlock($request, $form->id);

        return back()->with('status', 'Submitted for re-approval.');
    }

    private function canManageSchedule(User $actor, Schedule $schedule): bool
    {
        if (! $actor->hasSchedulePermission('create') && ! $actor->hasSchedulePermission('approve')) {
            return false;
        }

        if ($actor->role === 'manager') {
            if ((int) $schedule->location_id !== (int) $actor->location_id) {
                return false;
            }

            if (! $actor->hasSchedulePermission('approve') && (int) $schedule->created_by !== (int) $actor->id) {
                return false;
            }
        }

        return true;
    }

    private function canDeleteSchedule(User $actor, Schedule $schedule): bool
    {
        if ($actor->role === 'admin') {
            return true;
        }

        if (! $actor->hasSchedulePermission('create')) {
            return false;
        }

        if ($actor->role === 'manager') {
            return (int) $schedule->location_id === (int) $actor->location_id;
        }

        return (int) $schedule->created_by === (int) $actor->id;
    }

    private function canModifyForm(User $actor, ScheduleForm $form): bool
    {
        if ($actor->role === 'admin') {
            return true;
        }

        if (! $actor->hasSchedulePermission('create')) {
            return false;
        }

        if ($actor->role === 'manager') {
            return (int) $form->location_id === (int) $actor->location_id;
        }

        return (int) $form->created_by === (int) $actor->id;
    }

    private function formQuery(int $formId): Builder
    {
        return Schedule::query()
            ->where('schedule_form_id', $formId);
    }

    private function formHasReapprovalChanges(int $formId): bool
    {
        return Schedule::query()
            ->where('schedule_form_id', $formId)
            ->where(function ($query): void {
                $query->where('change_type', '!=', 'original')
                    ->orWhere('status', '!=', 'approved');
            })
            ->exists();
    }

    private function hasPendingModificationUnlock(Request $request, ScheduleForm $form): bool
    {
        return $request->session()->has($this->modificationUnlockSessionKey($form->id));
    }

    private function getPendingModificationReason(Request $request, ScheduleForm $form): ?string
    {
        return $request->session()->get($this->modificationUnlockSessionKey($form->id));
    }

    private function rememberPendingModificationUnlock(Request $request, ScheduleForm $form, string $reason): void
    {
        $request->session()->put($this->modificationUnlockSessionKey($form->id), $reason);
    }

    private function forgetPendingModificationUnlock(Request $request, int $formId): void
    {
        $request->session()->forget($this->modificationUnlockSessionKey($formId));
    }

    private function modificationUnlockSessionKey(int $formId): string
    {
        return "schedule_form_modification_unlock.{$formId}";
    }

    private function syncFormStatus(int $formId): void
    {
        $aggregate = Schedule::query()
            ->where('schedule_form_id', $formId)
            ->selectRaw("
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
            ")
            ->first();

        $submittedCount = (int) ($aggregate->submitted_count ?? 0);
        $approvedCount = (int) ($aggregate->approved_count ?? 0);
        $rejectedCount = (int) ($aggregate->rejected_count ?? 0);

        $status = 'approved';
        if ($submittedCount > 0) {
            $status = 'submitted';
        } elseif ($approvedCount > 0 && $rejectedCount > 0) {
            $status = 'partially_approved';
        } elseif ($approvedCount === 0 && $rejectedCount > 0) {
            $status = 'rejected';
        }

        ScheduleForm::query()
            ->where('id', $formId)
            ->update(['status' => $status]);
    }

    private function defaultAccessibleLocationId(User $user, Collection $locations): int
    {
        $preferredLocationId = (int) ($user->location_id ?? 0);

        if ($preferredLocationId > 0 && $locations->pluck('id')->contains($preferredLocationId)) {
            return $preferredLocationId;
        }

        return (int) ($locations->first()->id ?? 0);
    }

    private function buildScheduleTimelineMarkers(Carbon $chartStart, Carbon $chartEnd, int $totalMinutes): Collection
    {
        $markers = collect();
        $cursor = $chartStart->copy();

        while ($cursor->lte($chartEnd)) {
            $offsetMinutes = $chartStart->diffInMinutes($cursor);

            $markers->push([
                'label' => $cursor->format('g:i A'),
                'offset_percent' => min(100, ($offsetMinutes / $totalMinutes) * 100),
            ]);

            $cursor->addHour();
        }

        return $markers;
    }

    private function buildScheduleTimelineRows(Collection $schedules, Carbon $chartStart, int $totalMinutes): Collection
    {
        $windowEnd = $chartStart->copy()->addMinutes($totalMinutes);

        return $schedules
            ->groupBy('user_id')
            ->map(function (Collection $rows) use ($chartStart, $windowEnd, $totalMinutes): array {
                $orderedRows = $rows->sortBy('starts_at')->values();
                /** @var \App\Models\Schedule $first */
                $first = $orderedRows->first();

                $entries = $orderedRows->map(function (Schedule $schedule) use ($chartStart, $windowEnd, $totalMinutes): array {
                    $visibleStart = $schedule->starts_at->lt($chartStart)
                        ? $chartStart->copy()
                        : $schedule->starts_at->copy();
                    $visibleEnd = $schedule->ends_at->gt($windowEnd)
                        ? $windowEnd->copy()
                        : $schedule->ends_at->copy();
                    $continuesPastWindow = $schedule->ends_at->gt($windowEnd);
                    $startOffsetMinutes = max(0, $chartStart->diffInMinutes($visibleStart));
                    $visibleDurationMinutes = max(1, $visibleStart->diffInMinutes($visibleEnd));
                    $startPercent = min(100, ($startOffsetMinutes / $totalMinutes) * 100);
                    $widthPercent = min(max(0, 100 - $startPercent), max(1.5, ($visibleDurationMinutes / $totalMinutes) * 100));

                    return [
                        'schedule' => $schedule,
                        'status' => $schedule->status,
                        'start_label' => $schedule->starts_at->format('g:i A'),
                        'end_label' => $schedule->ends_at->isSameDay($schedule->starts_at)
                            ? $schedule->ends_at->format('g:i A')
                            : $schedule->ends_at->format('M j g:i A'),
                        'timeline_end_label' => $continuesPastWindow
                            ? $visibleEnd->format('g:i A') . '+'
                            : ($schedule->ends_at->isSameDay($schedule->starts_at)
                                ? $schedule->ends_at->format('g:i A')
                                : $schedule->ends_at->format('M j g:i A')),
                        'duration_label' => $this->formatTimelineDuration($schedule->starts_at->diffInMinutes($schedule->ends_at)),
                        'start_percent' => $startPercent,
                        'width_percent' => $widthPercent,
                    ];
                });

                return [
                    'user' => $first->user,
                    'location' => $first->location,
                    'entries' => $entries,
                    'color' => $this->buildTimelineRowColor($first->user),
                    'total_duration_label' => $this->formatTimelineDuration(
                        $orderedRows->sum(fn (Schedule $schedule): int => $schedule->starts_at->diffInMinutes($schedule->ends_at))
                    ),
                    'sort_key' => ($orderedRows->first()?->starts_at?->format('His') ?? '999999') . '|' . ($first->user?->name ?? 'zzzzzz'),
                ];
            })
            ->sortBy('sort_key')
            ->values();
    }

    private function formatTimelineDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    private function buildTimelineRowColor(?User $user): array
    {
        $seed = (int) sprintf('%u', crc32(($user?->id ?? 0) . '|' . ($user?->name ?? 'staff')));
        $hue = $seed % 360;

        return [
            'bar_background' => "hsl({$hue} 74% 58%)",
            'bar_border' => "hsl({$hue} 70% 36%)",
            'soft_background' => "hsl({$hue} 78% 96%)",
            'soft_border' => "hsl({$hue} 60% 84%)",
            'accent' => "hsl({$hue} 72% 42%)",
        ];
    }

    private function scheduleDurationSeconds(Schedule $schedule): int
    {
        return max(0, $schedule->starts_at->diffInSeconds($schedule->ends_at));
    }

    private function buildScheduleWindow(string $shiftDate, string $clockIn, string $clockOut): ?array
    {
        $startsAt = Carbon::parse("{$shiftDate} {$clockIn}:00");
        $endsAt = Carbon::parse("{$shiftDate} {$clockOut}:00");

        if ($endsAt->equalTo($startsAt)) {
            return null;
        }

        if ($endsAt->lt($startsAt)) {
            $endsAt->addDay();
        }

        return [$startsAt, $endsAt];
    }

    private function selectedRosterEntries(array $roster): Collection
    {
        return collect($roster)
            ->flatMap(function (array $entry, string|int $userId): Collection {
                $userId = (int) $userId;
                $lines = collect($entry['lines'] ?? []);

                if ($lines->isNotEmpty()) {
                    return $lines
                        ->map(function (array $line, int $lineIndex) use ($userId): array {
                            return [
                                'user_id' => $userId,
                                'line_index' => $lineIndex,
                                'selected' => (bool) ($line['selected'] ?? false),
                                'clock_in' => $line['clock_in'] ?? null,
                                'clock_out' => $line['clock_out'] ?? null,
                            ];
                        })
                        ->filter(fn (array $line): bool => $line['selected'])
                        ->values();
                }

                return collect([
                    [
                        'user_id' => $userId,
                        'line_index' => 0,
                        'selected' => (bool) ($entry['selected'] ?? false),
                        'clock_in' => $entry['clock_in'] ?? null,
                        'clock_out' => $entry['clock_out'] ?? null,
                    ],
                ])->filter(fn (array $line): bool => $line['selected']);
            })
            ->values();
    }

    private function findOverlappingSchedules(
        int $userId,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $ignoreScheduleId = null,
    ): Collection {
        return Schedule::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'rejected')
            ->where('change_type', '!=', 'removed_after_approval')
            ->when($ignoreScheduleId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreScheduleId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->orderBy('starts_at')
            ->get(['id', 'starts_at', 'ends_at']);
    }

    private function findOverlappingDraftEntries(
        Collection $validatedEntries,
        int $userId,
        Carbon $startsAt,
        Carbon $endsAt,
    ): Collection {
        return $validatedEntries
            ->filter(function (array $entry) use ($userId, $startsAt, $endsAt): bool {
                return (int) $entry['user']->id === $userId
                    && $entry['starts_at']->lt($endsAt)
                    && $entry['ends_at']->gt($startsAt);
            })
            ->values();
    }

    private function scheduleOverlapErrorMessage(string $staffName, Collection $overlappingSchedules): string
    {
        $timeRanges = $overlappingSchedules
            ->map(function ($entry): string {
                if ($entry instanceof Schedule) {
                    return $this->formatScheduleWindow($entry->starts_at, $entry->ends_at);
                }

                return $this->formatScheduleWindow($entry['starts_at'], $entry['ends_at']);
            })
            ->implode(', ');

        return "{$staffName} already has an overlapping schedule in this time window ({$timeRanges}).";
    }

    private function formatScheduleWindow(Carbon $startsAt, Carbon $endsAt): string
    {
        $startLabel = $startsAt->format('M j H:i');
        $endLabel = $endsAt->isSameDay($startsAt)
            ? $endsAt->format('H:i')
            : $endsAt->format('M j H:i');

        return "{$startLabel}-{$endLabel}";
    }
}
