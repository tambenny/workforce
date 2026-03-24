<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $managedRoles = $this->manageableRolesFor($request->user());

        $staffQuery = User::query()
            ->whereIn('role', $managedRoles);

        $staff = (clone $staffQuery)
            ->with(['location', 'position'])
            ->latest()
            ->paginate(20);

        $summary = [
            'total' => (clone $staffQuery)->count(),
            'admins' => (clone $staffQuery)->where('role', 'admin')->count(),
            'managers' => (clone $staffQuery)->where('role', 'manager')->count(),
            'active' => (clone $staffQuery)->where('is_active', true)->count(),
            'schedule_required' => (clone $staffQuery)->where('requires_schedule_for_clock', true)->count(),
        ];

        return view('staff.index', [
            'staff' => $staff,
            'summary' => $summary,
            'canManageAdminUsers' => $this->canManageAdminUsers($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('staff.create', [
            'locations' => Location::orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'availableRoles' => $this->manageableRolesFor($request->user()),
            'canManageAdminUsers' => $this->canManageAdminUsers($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = $this->manageableRolesFor($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'staff_id' => [
                Rule::requiredIf(fn (): bool => $request->input('role') !== 'admin'),
                'nullable',
                'digits_between:4,12',
                'unique:users,staff_id',
            ],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($allowedRoles)],
            'can_create_schedules' => ['nullable', 'boolean'],
            'can_approve_schedules' => ['nullable', 'boolean'],
            'can_view_schedules' => ['nullable', 'boolean'],
            'can_view_schedule_summary' => ['nullable', 'boolean'],
            'can_view_current_staff' => ['nullable', 'boolean'],
            'can_view_punch_photos' => ['nullable', 'boolean'],
            'can_view_security_warnings' => ['nullable', 'boolean'],
            'can_view_dashboard' => ['nullable', 'boolean'],
            'can_use_web_clock' => ['nullable', 'boolean'],
            'can_view_my_punches' => ['nullable', 'boolean'],
            'can_view_punch_summary' => ['nullable', 'boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
            'requires_schedule_for_clock' => ['nullable', 'boolean'],
            'pin' => [
                Rule::requiredIf(fn (): bool => $request->input('role') !== 'admin'),
                'nullable',
                'digits_between:4,6',
            ],
        ]);

        User::create(array_merge(
            $this->userAttributesFromData($data),
            [
                'password' => Hash::make($data['password']),
                'pin_enabled' => filled($data['pin'] ?? null),
                'pin_hash' => filled($data['pin'] ?? null) ? Hash::make($data['pin']) : null,
            ]
        ));

        return redirect()->route('staff.index')->with('status', 'User created.');
    }

    public function edit(Request $request, User $staff): View
    {
        $this->abortUnlessManageableUser($request->user(), $staff);

        return view('staff.edit', [
            'staff' => $staff,
            'locations' => Location::orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'availableRoles' => $this->manageableRolesFor($request->user()),
            'canManageAdminUsers' => $this->canManageAdminUsers($request->user()),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->abortUnlessManageableUser($request->user(), $staff);

        $allowedRoles = $this->manageableRolesFor($request->user());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'staff_id' => [
                Rule::requiredIf(fn (): bool => $request->input('role') !== 'admin'),
                'nullable',
                'digits_between:4,12',
                Rule::unique('users', 'staff_id')->ignore($staff->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'pin' => [
                Rule::requiredIf(fn (): bool => $request->input('role') !== 'admin' && ! $staff->pin_enabled),
                'nullable',
                'digits_between:4,6',
            ],
            'role' => ['required', Rule::in($allowedRoles)],
            'can_create_schedules' => ['nullable', 'boolean'],
            'can_approve_schedules' => ['nullable', 'boolean'],
            'can_view_schedules' => ['nullable', 'boolean'],
            'can_view_schedule_summary' => ['nullable', 'boolean'],
            'can_view_current_staff' => ['nullable', 'boolean'],
            'can_view_punch_photos' => ['nullable', 'boolean'],
            'can_view_security_warnings' => ['nullable', 'boolean'],
            'can_view_dashboard' => ['nullable', 'boolean'],
            'can_use_web_clock' => ['nullable', 'boolean'],
            'can_view_my_punches' => ['nullable', 'boolean'],
            'can_view_punch_summary' => ['nullable', 'boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
            'requires_schedule_for_clock' => ['nullable', 'boolean'],
        ]);

        $staff->fill($this->userAttributesFromData($data));

        if (! empty($data['password'])) {
            $staff->password = Hash::make($data['password']);
        }

        if (! empty($data['pin'])) {
            $staff->pin_enabled = true;
            $staff->pin_hash = Hash::make($data['pin']);
        } elseif ($data['role'] === 'admin') {
            $staff->pin_enabled = false;
            $staff->pin_hash = null;
        }

        $staff->save();

        return redirect()->route('staff.index')->with('status', 'User updated.');
    }

    public function resetPin(Request $request, User $staff): RedirectResponse
    {
        $this->abortUnlessManageableUser($request->user(), $staff);

        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $staff->forceFill([
            'pin_enabled' => true,
            'pin_hash' => Hash::make($data['pin']),
        ])->save();

        return redirect()->route('staff.edit', $staff)->with('status', 'PIN reset successfully.');
    }

    private function manageableRolesFor(User $actor): array
    {
        return $this->canManageAdminUsers($actor)
            ? ['admin', 'hr', 'manager', 'staff']
            : ['manager', 'staff'];
    }

    private function canManageAdminUsers(User $actor): bool
    {
        return $actor->role === 'admin';
    }

    private function abortUnlessManageableUser(User $actor, User $staff): void
    {
        abort_unless(in_array($staff->role, $this->manageableRolesFor($actor), true), 404);
    }

    private function userAttributesFromData(array $data): array
    {
        $isAdmin = $data['role'] === 'admin';
        $isManagerLike = in_array($data['role'], ['manager', 'hr'], true);

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'staff_id' => $data['staff_id'] ?? null,
            'role' => $data['role'],
            'can_create_schedules' => $isAdmin || ($isManagerLike && (bool) ($data['can_create_schedules'] ?? false)),
            'can_approve_schedules' => $isAdmin || ($isManagerLike && (bool) ($data['can_approve_schedules'] ?? false)),
            'can_view_schedules' => $isAdmin || ($isManagerLike && (bool) ($data['can_view_schedules'] ?? false)),
            'can_view_schedule_summary' => $isAdmin || ($isManagerLike && (bool) ($data['can_view_schedule_summary'] ?? false)),
            'can_view_current_staff' => $isAdmin || ($isManagerLike && (bool) ($data['can_view_current_staff'] ?? false)),
            'can_view_punch_photos' => $isAdmin || ($isManagerLike && (bool) ($data['can_view_punch_photos'] ?? false)),
            'can_view_security_warnings' => $isAdmin || ($isManagerLike && (bool) ($data['can_view_security_warnings'] ?? false)),
            'can_view_dashboard' => $isAdmin || (bool) ($data['can_view_dashboard'] ?? false),
            'can_use_web_clock' => $isAdmin || (bool) ($data['can_use_web_clock'] ?? false),
            'can_view_my_punches' => $isAdmin || (bool) ($data['can_view_my_punches'] ?? false),
            'can_view_punch_summary' => $isAdmin || (bool) ($data['can_view_punch_summary'] ?? false),
            'location_id' => $data['location_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'requires_schedule_for_clock' => $isAdmin ? false : (bool) ($data['requires_schedule_for_clock'] ?? true),
        ];
    }
}
