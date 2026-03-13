<x-app-layout>
    @php($dashboardLocation = auth()->user()->location)
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
            @if ($dashboardLocation)
                <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1">
                    @if ($dashboardLocation->logo_path)
                        <img
                            src="{{ asset('storage/' . $dashboardLocation->logo_path) }}"
                            alt="{{ $dashboardLocation->name }} logo"
                            class="h-8 w-8 rounded object-cover"
                        >
                    @endif
                    <span class="max-w-44 truncate text-sm font-medium text-gray-700">{{ $dashboardLocation->name }}</span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-lg">Role: <strong>{{ $role }}</strong></p>

                @isset($openPunches)
                    <p class="mt-2">Open punches right now: <strong>{{ $openPunches }}</strong></p>
                @endisset
                @isset($pendingSchedules)
                    <p class="mt-2">Pending schedule approvals: <strong>{{ $pendingSchedules }}</strong></p>
                @endisset
                @isset($unresolvedWarnings)
                    <p class="mt-2">Open security warnings: <strong>{{ $unresolvedWarnings }}</strong></p>
                @endisset

                @isset($myOpenPunch)
                    <p class="mt-2">
                        My open punch:
                        <strong>{{ $myOpenPunch ? 'Yes (since ' . $myOpenPunch->clock_in_at . ')' : 'No' }}</strong>
                    </p>
                @endisset

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('clock.index') }}" class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Web Clock</a>
                    <a href="{{ route('punches.index') }}" class="rounded bg-slate-600 px-4 py-2 text-sm font-semibold text-white">My Punches</a>
                    @if (in_array($role, ['admin', 'manager', 'hr'], true))
                        <a href="{{ route('punches.photos') }}" class="rounded bg-sky-700 px-4 py-2 text-sm font-semibold text-white">Punch Photos</a>
                    @endif
                    @if (
                        $role === 'admin' ||
                        $role === 'staff' ||
                        auth()->user()->hasSchedulePermission('create') ||
                        auth()->user()->hasSchedulePermission('approve')
                    )
                        <a href="{{ route('schedules.index') }}" class="rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Schedules</a>
                    @endif
                    @if (auth()->user()->hasSchedulePermission('create'))
                        <a href="{{ route('schedules.create') }}" class="rounded bg-blue-500 px-4 py-2 text-sm font-semibold text-white">Create Schedule</a>
                    @endif
                    @if (in_array($role, ['admin', 'hr'], true))
                        <a href="{{ route('staff.index') }}" class="rounded bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Staff Management</a>
                    @endif
                    @if ($role === 'admin')
                        <a href="{{ route('locations.index') }}" class="rounded bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Locations</a>
                        <a href="{{ route('positions.index') }}" class="rounded bg-violet-700 px-4 py-2 text-sm font-semibold text-white">Positions</a>
                    @endif
                    @if (auth()->user()->hasSchedulePermission('approve'))
                        <a href="{{ route('schedules.approvals') }}" class="rounded bg-green-700 px-4 py-2 text-sm font-semibold text-white">Schedule Approvals</a>
                    @endif
                    @if (in_array($role, ['admin', 'manager'], true))
                        <a href="{{ route('reports.security-warnings') }}" class="rounded bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Security Warnings</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
