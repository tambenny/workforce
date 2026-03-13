<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $staff = User::with(['location', 'position'])
            ->whereIn('role', ['manager', 'staff'])
            ->latest()
            ->paginate(20);

        return view('staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('staff.create', [
            'locations' => Location::orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'staff_id' => ['required', 'digits_between:4,12', 'unique:users,staff_id'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:manager,staff'],
            'can_create_schedules' => ['nullable', 'boolean'],
            'can_approve_schedules' => ['nullable', 'boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
            'requires_schedule_for_clock' => ['nullable', 'boolean'],
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $isManager = $data['role'] === 'manager';

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'staff_id' => $data['staff_id'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'can_create_schedules' => $isManager && (bool) ($data['can_create_schedules'] ?? false),
            'can_approve_schedules' => $isManager && (bool) ($data['can_approve_schedules'] ?? false),
            'location_id' => $data['location_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'pin_enabled' => true,
            'pin_hash' => Hash::make($data['pin']),
            'requires_schedule_for_clock' => (bool) ($data['requires_schedule_for_clock'] ?? true),
        ]);

        return redirect()->route('staff.index')->with('status', 'Staff member created.');
    }

    public function edit(User $staff): View
    {
        abort_unless(in_array($staff->role, ['manager', 'staff'], true), 404);

        return view('staff.edit', [
            'staff' => $staff,
            'locations' => Location::orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless(in_array($staff->role, ['manager', 'staff'], true), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $staff->id],
            'staff_id' => ['required', 'digits_between:4,12', 'unique:users,staff_id,' . $staff->id],
            'password' => ['nullable', 'string', 'min:8'],
            'pin' => ['nullable', 'digits_between:4,6'],
            'role' => ['required', 'in:manager,staff'],
            'can_create_schedules' => ['nullable', 'boolean'],
            'can_approve_schedules' => ['nullable', 'boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'is_active' => ['nullable', 'boolean'],
            'requires_schedule_for_clock' => ['nullable', 'boolean'],
        ]);

        $isManager = $data['role'] === 'manager';

        $staff->name = $data['name'];
        $staff->email = $data['email'];
        $staff->staff_id = $data['staff_id'];
        $staff->role = $data['role'];
        $staff->can_create_schedules = $isManager && (bool) ($data['can_create_schedules'] ?? false);
        $staff->can_approve_schedules = $isManager && (bool) ($data['can_approve_schedules'] ?? false);
        $staff->location_id = $data['location_id'] ?? null;
        $staff->position_id = $data['position_id'] ?? null;
        $staff->is_active = (bool) ($data['is_active'] ?? false);
        $staff->requires_schedule_for_clock = (bool) ($data['requires_schedule_for_clock'] ?? false);

        if (! empty($data['password'])) {
            $staff->password = Hash::make($data['password']);
        }

        if (! empty($data['pin'])) {
            $staff->pin_enabled = true;
            $staff->pin_hash = Hash::make($data['pin']);
        }

        $staff->save();

        return redirect()->route('staff.index')->with('status', 'Staff member updated.');
    }

    public function resetPin(Request $request, User $staff): RedirectResponse
    {
        abort_unless(in_array($staff->role, ['manager', 'staff'], true), 404);

        $data = $request->validate([
            'pin' => ['required', 'digits_between:4,6'],
        ]);

        $staff->forceFill([
            'pin_enabled' => true,
            'pin_hash' => Hash::make($data['pin']),
        ])->save();

        return redirect()->route('staff.edit', $staff)->with('status', 'PIN reset successfully.');
    }
}
