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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $canAccess =
            in_array($request->user()->role, ['admin', 'manager', 'staff'], true) ||
            $request->user()->hasSchedulePermission('create') ||
            $request->user()->hasSchedulePermission('approve');

        abort_unless($canAccess, 403, 'Insufficient role.');

        $locations = Location::query()
            ->where('is_active', true)
            ->when(in_array($request->user()->role, ['manager', 'staff'], true), function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) $request->query('location_id', ($locations->first()->id ?? 0));
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
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                if (! $request->user()->hasSchedulePermission('approve')) {
                    $query->where('f.created_by', $request->user()->id);
                }
            })
            ->when($request->user()->role === 'staff', function ($query) use ($request): void {
                $query->whereExists(function ($sub) use ($request): void {
                    $sub->select(DB::raw(1))
                        ->from('schedules as sx')
                        ->whereColumn('sx.schedule_form_id', 'f.id')
                        ->where('sx.user_id', $request->user()->id);
                });
            })
            ->when(
                ! in_array($request->user()->role, ['admin', 'manager', 'staff'], true) && $request->user()->hasSchedulePermission('create'),
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
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                if (! $request->user()->hasSchedulePermission('approve')) {
                    $query->where('s.created_by', $request->user()->id);
                }
            })
            ->when($request->user()->role === 'staff', fn ($query) => $query->where('s.user_id', $request->user()->id))
            ->when(
                ! in_array($request->user()->role, ['admin', 'manager', 'staff'], true) && $request->user()->hasSchedulePermission('create'),
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

    public function form(Request $request): View
    {
        $data = $request->validate([
            'form_id' => ['required', 'integer', 'exists:schedule_forms,id'],
        ]);

        $form = ScheduleForm::with(['location', 'creator', 'approver'])->findOrFail((int) $data['form_id']);

        if ($request->user()->role === 'manager' && $request->user()->hasSchedulePermission('approve')) {
            abort_if((int) $form->location_id !== (int) $request->user()->location_id, 403, 'You cannot access this form.');
        }

        $schedules = $this->formQuery((int) $data['form_id'])
            ->with(['user.position', 'location', 'creator', 'approver', 'rejector'])
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                if (! $request->user()->hasSchedulePermission('approve')) {
                    $query->where('created_by', $request->user()->id);
                }
            })
            ->when($request->user()->role === 'staff', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('starts_at')
            ->get();

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
            $scheduledUserIdsOnDate = Schedule::query()
                ->whereDate('shift_date', $form->shift_date->toDateString())
                ->pluck('user_id');

            $addableStaff = $this->staffScopeForScheduler($request->user())
                ->where('is_active', true)
                ->where('location_id', $form->location_id)
                ->when($scheduledUserIdsOnDate->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $scheduledUserIdsOnDate))
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
            abort(403, 'Manager must be assigned to a location before creating schedules.');
        }

        $locations = Location::query()
            ->where('is_active', true)
            ->when($request->user()->role === 'manager', function ($query) use ($request): void {
                $query->where('id', $request->user()->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedLocationId = (int) old('location_id', $request->query('location_id', $locations->first()->id ?? 0));
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
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $locationId = (int) $data['location_id'];
        if ($request->user()->role === 'manager' && $locationId !== (int) $request->user()->location_id) {
            return back()->withErrors(['roster' => 'You can only create schedules for your own location.'])->withInput();
        }

        $selected = collect($data['roster'])
            ->filter(fn (array $entry): bool => (bool) ($entry['selected'] ?? false));

        if ($selected->isEmpty()) {
            return back()->withErrors(['roster' => 'Select at least one roster entry.'])->withInput();
        }

        $staff = $this->staffScopeForScheduler($request->user())
            ->with('location')
            ->where('is_active', true)
            ->where('location_id', $locationId)
            ->whereIn('id', $selected->keys()->map(static fn ($id): int => (int) $id))
            ->get()
            ->keyBy('id');

        $shiftDate = Carbon::parse($data['shift_date'])->toDateString();
        $form = ScheduleForm::create([
            'location_id' => $locationId,
            'shift_date' => $shiftDate,
            'created_by' => $request->user()->id,
            'status' => 'submitted',
        ]);
        $createdCount = 0;

        foreach ($selected as $userId => $entry) {
            $user = $staff->get((int) $userId);

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

            $startsAt = Carbon::parse("{$shiftDate} {$clockIn}:00");
            $endsAt = Carbon::parse("{$shiftDate} {$clockOut}:00");
            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                return back()->withErrors(['roster' => "Clock out must be after clock in for {$user->name}."])->withInput();
            }

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

            $createdCount++;
        }

        if ($createdCount === 0) {
            $form->delete();
            return back()->withErrors(['roster' => 'No schedules were created.'])->withInput();
        }

        return redirect()->route('schedules.index')->with('status', "{$createdCount} schedule(s) submitted for approval.");
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

        $selectedLocationId = (int) $request->query('location_id', ($locations->first()->id ?? 0));
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
        $startsAt = Carbon::parse("{$shiftDate} {$data['clock_in']}:00");
        $endsAt = Carbon::parse("{$shiftDate} {$data['clock_out']}:00");

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return back()->withErrors(['schedule' => 'Clock out must be after clock in.']);
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
        $alreadyScheduled = Schedule::query()
            ->where('user_id', (int) $data['user_id'])
            ->whereDate('shift_date', $shiftDate)
            ->exists();
        if ($alreadyScheduled) {
            return back()->withErrors(['schedule' => 'Selected staff already has a schedule on this date.']);
        }

        $startsAt = Carbon::parse("{$shiftDate} {$data['clock_in']}:00");
        $endsAt = Carbon::parse("{$shiftDate} {$data['clock_out']}:00");
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return back()->withErrors(['schedule' => 'Clock out must be after clock in.']);
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
}
