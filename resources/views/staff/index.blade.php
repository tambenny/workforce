<x-app-layout>
    @php
        $roleLabel = function (string $role): string {
            return match ($role) {
                'admin' => 'System Admin',
                'hr' => 'HR',
                default => ucfirst($role),
            };
        };

        $buildAccessLabels = function ($row): array {
            $labels = [];

            if ($row->canViewDashboard()) {
                $labels[] = 'Dashboard';
            }

            if ($row->canUseWebClock()) {
                $labels[] = 'Clock';
            }

            if ($row->canViewOwnPunches()) {
                $labels[] = 'My Punches';
            }

            if ($row->canViewPunchSummary()) {
                $labels[] = 'Punch Summary';
            }

            if ($row->canViewSchedules()) {
                $labels[] = 'View Schedules';
            }

            if ($row->hasSchedulePermission('create')) {
                $labels[] = 'Create Schedule';
            }

            if ($row->hasSchedulePermission('approve')) {
                $labels[] = 'Approve Schedule';
            }

            if ($row->canViewScheduleSummary()) {
                $labels[] = 'Schedule Summary';
            }

            if ($row->canViewCurrentStaffReport()) {
                $labels[] = 'Current Staff';
            }

            if ($row->canViewPunchPhotos()) {
                $labels[] = 'Punch Photos';
            }

            if ($row->canViewSecurityWarnings()) {
                $labels[] = 'Warnings';
            }

            if (in_array($row->role, ['admin', 'hr'], true)) {
                $labels[] = 'Staff';
                $labels[] = 'Positions';
            }

            if ($row->role === 'admin') {
                $labels[] = 'Locations';
            }

            return $labels;
        };
    @endphp

    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">User Management</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Review system admin, HR, manager, and staff access, clock rules, and location assignments in one roster view.
                    </p>
                </div>

                <a
                    href="{{ route('staff.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500"
                >
                    Add User
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Total People</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $canManageAdminUsers ? 'System admin, HR, manager, and staff accounts currently in the directory.' : 'Managers and staff currently in the directory.' }}
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">
                        {{ $canManageAdminUsers ? 'System Admins' : 'Managers' }}
                    </p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">
                        {{ $canManageAdminUsers ? $summary['admins'] : $summary['managers'] }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $canManageAdminUsers ? 'Accounts with full system, HR, and location access.' : 'Leads with restaurant-level schedule permissions.' }}
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Active</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['active'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">People who can sign in and use assigned menu access.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Schedule Locked</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['schedule_required'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Profiles that require an approved schedule before clocking.</p>
                </div>
            </div>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                @if (session('status'))
                    <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[18%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">User</th>
                                <th class="w-[11%] border-b border-slate-700 px-3 py-3.5">Role</th>
                                <th class="w-[30%] border-b border-slate-700 px-3 py-3.5">Menu Access</th>
                                <th class="w-[18%] border-b border-slate-700 px-3 py-3.5">Assignment</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Clock Rule</th>
                                <th class="w-[7%] border-b border-slate-700 px-3 py-3.5">Status</th>
                                <th class="w-[6%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $row)
                                @php
                                    $labels = $buildAccessLabels($row);
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $row->name }}</p>
                                            <p class="mt-1 text-xs text-slate-600">{{ $row->email }}</p>
                                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                                Staff ID {{ $row->staff_id ?? '-' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-700">
                                            {{ $roleLabel($row->role) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($labels as $label)
                                                <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                                                    {{ $label }}
                                                </span>
                                            @empty
                                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                                    Limited Access
                                                </span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Position</p>
                                                <p class="mt-0.5 font-medium text-slate-800">{{ $row->position?->name ?? '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Location</p>
                                                <p class="mt-0.5 font-medium text-slate-800">{{ $row->location?->name ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $row->requires_schedule_for_clock ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                                            {{ $row->requires_schedule_for_clock ? 'Schedule Required' : 'No Schedule Check' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $row->is_active ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-rose-200 bg-rose-50 text-rose-700' }}">
                                            {{ $row->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                        <a
                                            href="{{ route('staff.edit', $row) }}"
                                            class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[13px] font-semibold text-blue-700 transition hover:bg-blue-100"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No user records are available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $staff->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
