<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Staff</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('staff.store') }}" class="space-y-4">
                    @csrf

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
                        <x-text-input id="staff_id" name="staff_id" class="mt-1 block w-full" value="{{ old('staff_id') }}" required />
                        <x-input-error :messages="$errors->get('staff_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="pin" value="PIN (4-6 digits)" />
                        <x-text-input id="pin" name="pin" class="mt-1 block w-full" value="{{ old('pin') }}" required />
                        <x-input-error :messages="$errors->get('pin')" class="mt-2" />
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="requires_schedule_for_clock" value="1" @checked(old('requires_schedule_for_clock', '1') === '1')>
                        <span>Require approved schedule for clock in/out</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="role" value="Role" />
                            <select id="role" name="role" class="mt-1 block w-full rounded border-gray-300">
                                <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                                <option value="staff" @selected(old('role') === 'staff')>Staff</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="location_id" value="Location" />
                            <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300">
                                <option value="">Select</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" @selected((string) old('location_id') === (string) $loc->id)>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="position_id" value="Position" />
                            <select id="position_id" name="position_id" class="mt-1 block w-full rounded border-gray-300">
                                <option value="">Select</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" @selected((string) old('position_id') === (string) $position->id)>{{ $position->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="manager-permissions" class="rounded border border-slate-200 p-3">
                        <p class="text-sm font-semibold text-slate-700">Manager Schedule Permissions</p>
                        <label class="mt-2 inline-flex items-center gap-2">
                            <input type="checkbox" name="can_create_schedules" value="1" @checked(old('can_create_schedules') === '1')>
                            <span>Can create schedules</span>
                        </label>
                        <label class="mt-2 inline-flex items-center gap-2">
                            <input type="checkbox" name="can_approve_schedules" value="1" @checked(old('can_approve_schedules') === '1')>
                            <span>Can approve schedules</span>
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        <span>Active</span>
                    </label>

                    <div>
                        <x-primary-button>Create</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const roleSelect = document.getElementById('role');
        const managerPermissions = document.getElementById('manager-permissions');
        function toggleManagerPermissions() {
            managerPermissions.style.display = roleSelect.value === 'manager' ? 'block' : 'none';
        }
        roleSelect.addEventListener('change', toggleManagerPermissions);
        toggleManagerPermissions();
    </script>
</x-app-layout>
