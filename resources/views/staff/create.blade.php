<x-app-layout>
    @php
        $roleLabel = fn (string $role): string => match ($role) {
            'admin' => 'System Admin',
            'hr' => 'HR',
            default => ucfirst($role),
        };
        $createDescription = $canManageAdminUsers
            ? 'Add a system admin, HR, manager, or staff account and set the access it should receive.'
            : 'Add a manager or staff account and set the access it should receive.';
    @endphp

    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Create User</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        {{ $createDescription }}
                    </p>
                </div>

                <a
                    href="{{ route('staff.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to Users
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto grid max-w-[92rem] gap-6 sm:px-5 lg:grid-cols-[minmax(0,0.72fr)_minmax(280px,0.28fr)] lg:px-6">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">New User Profile</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Account, assignment, and menu access</h3>
                </div>

                <form method="POST" action="{{ route('staff.store') }}" class="space-y-6 px-6 py-6" autocomplete="off">
                    @csrf

                    <div class="pointer-events-none absolute -left-[9999px] top-auto h-px w-px overflow-hidden opacity-0" aria-hidden="true">
                        <input type="text" name="fake_username" autocomplete="username" tabindex="-1">
                        <input type="password" name="fake_password" autocomplete="current-password" tabindex="-1">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="name" value="Name" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name') }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" class="mt-1 block w-full" value="{{ old('email') }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="staff_id" value="Staff ID" />
                            <x-text-input id="staff_id" name="staff_id" class="mt-1 block w-full" value="{{ old('staff_id') }}" />
                            <p class="mt-2 text-sm text-slate-500">Required for HR, manager, and staff accounts. System admin accounts can leave it blank.</p>
                            <x-input-error :messages="$errors->get('staff_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pin" value="PIN (4-6 digits)" />
                            <x-text-input id="pin" name="pin" class="mt-1 block w-full" value="{{ old('pin') }}" autocomplete="new-password" inputmode="numeric" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" readonly onfocus="this.removeAttribute('readonly');" />
                            <p class="mt-2 text-sm text-slate-500">Required for HR, manager, and staff accounts. System admin accounts can leave it blank.</p>
                            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" readonly onfocus="this.removeAttribute('readonly');" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Assignment</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <x-input-label for="role" value="Role" />
                                <select id="role" name="role" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm">
                                    @foreach ($availableRoles as $roleOption)
                                        <option value="{{ $roleOption }}" @selected(old('role', 'manager') === $roleOption)>{{ $roleLabel($roleOption) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="location_id" value="Location" />
                                <select id="location_id" name="location_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm">
                                    <option value="">Select</option>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((string) old('location_id') === (string) $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="position_id" value="Position" />
                                <select id="position_id" name="position_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm">
                                    <option value="">Select</option>
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id }}" @selected((string) old('position_id') === (string) $position->id)>{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="admin-access-note" class="rounded-3xl border border-blue-200 bg-blue-50 p-5 text-blue-900">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">System Admin Access</p>
                        <p class="mt-2 text-sm leading-6">
                            System admin accounts automatically receive the HR and Admin menus plus full schedule and management access. No extra menu checkboxes are required.
                        </p>
                    </div>

                    <div id="standard-permissions" class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Menu Permissions</p>
                        <p class="mt-2 text-sm text-slate-600">Choose the standard sections available to this user.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Main Navigation</p>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_dashboard" value="1" @checked(old('can_view_dashboard', '1') === '1')>
                                    <span class="text-sm text-slate-700">Dashboard</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_use_web_clock" value="1" @checked(old('can_use_web_clock', '1') === '1')>
                                    <span class="text-sm text-slate-700">Web Clock</span>
                                </label>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Punch Records</p>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_my_punches" value="1" @checked(old('can_view_my_punches', '1') === '1')>
                                    <span class="text-sm text-slate-700">My Punches</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_punch_summary" value="1" @checked(old('can_view_punch_summary', '1') === '1')>
                                    <span class="text-sm text-slate-700">Punch Summary</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="manager-permissions" class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Extended Menu Permissions</p>
                        <p class="mt-2 text-sm text-slate-600">HR and manager accounts share the same extended schedule and management permissions.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div id="schedule-permissions-card" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Schedules</p>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_schedules" value="1" @checked(old('can_view_schedules', '1') === '1')>
                                    <span class="text-sm text-slate-700">View location schedules</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_create_schedules" value="1" @checked(old('can_create_schedules') === '1')>
                                    <span class="text-sm text-slate-700">Create schedules</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_approve_schedules" value="1" @checked(old('can_approve_schedules') === '1')>
                                    <span class="text-sm text-slate-700">Approve schedules</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_schedule_summary" value="1" @checked(old('can_view_schedule_summary') === '1')>
                                    <span class="text-sm text-slate-700">View schedule summary</span>
                                </label>
                            </div>

                            <div id="management-permissions-card" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Management</p>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_current_staff" value="1" @checked(old('can_view_current_staff') === '1')>
                                    <span class="text-sm text-slate-700">View current staff and team punch log</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_punch_photos" value="1" @checked(old('can_view_punch_photos') === '1')>
                                    <span class="text-sm text-slate-700">View punch photos</span>
                                </label>
                                <label class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                    <input type="checkbox" name="can_view_security_warnings" value="1" @checked(old('can_view_security_warnings') === '1')>
                                    <span class="text-sm text-slate-700">View security warnings</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                            <span class="text-sm text-slate-700">Active</span>
                        </label>

                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                            <input type="checkbox" name="requires_schedule_for_clock" value="1" @checked(old('requires_schedule_for_clock', '1') === '1')>
                            <span class="text-sm text-slate-700">Require approved schedule for clock in/out</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a
                            href="{{ route('staff.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                        <x-primary-button>Create User</x-primary-button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Available Locations</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $locations->count() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Assign the new account to the correct restaurant or site when needed.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Positions</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $positions->count() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Choose a position now or leave it open and assign it later.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-900 px-5 py-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-200">Access Setup</p>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        System admin accounts get the full HR and Admin menus automatically. HR accounts can use the same operational permissions as managers without becoming system admins.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const standardPermissions = document.getElementById('standard-permissions');
        const managerPermissions = document.getElementById('manager-permissions');
        const adminAccessNote = document.getElementById('admin-access-note');
        const schedulePermissionsCard = document.getElementById('schedule-permissions-card');
        const managementPermissionsCard = document.getElementById('management-permissions-card');
        const staffIdInput = document.getElementById('staff_id');
        const pinInput = document.getElementById('pin');

        function syncRoleForm() {
            const isAdmin = roleSelect.value === 'admin';
            const isManager = roleSelect.value === 'manager';
            const isHr = roleSelect.value === 'hr';
            const showsExtendedPermissions = isManager || isHr;

            standardPermissions.style.display = isAdmin ? 'none' : 'block';
            managerPermissions.style.display = showsExtendedPermissions ? 'block' : 'none';
            adminAccessNote.style.display = isAdmin ? 'block' : 'none';
            managementPermissionsCard.style.display = showsExtendedPermissions ? 'block' : 'none';
            schedulePermissionsCard.classList.remove('sm:col-span-2');
            staffIdInput.required = !isAdmin;
            pinInput.required = !isAdmin;
        }

        roleSelect.addEventListener('change', syncRoleForm);
        syncRoleForm();
    </script>
</x-app-layout>
