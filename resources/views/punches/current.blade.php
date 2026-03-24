<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Current Staff</h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($canViewPunchLog)
                        <a href="{{ route('punches.index', ['open_now' => 1]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Open Punch Log
                        </a>
                    @endif
                    @if ($canViewPunchSummary)
                        <a href="{{ route('punches.summary') }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                            Punch Summary
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Currently Working</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $totalOpenPunches }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $totalOpenPunches }} staff currently clocked in.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Active Locations</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $activeLocationCount }}</p>
                    <p class="mt-2 text-sm text-slate-600">Locations with at least one staff member clocked in.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Scope</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $selectedLocation?->name ?? 'All accessible locations' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Live open punches grouped by location.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Location Headcount</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Live site coverage</h3>
                    </div>

                    @if ($locations->count() > 1)
                        <form method="GET" action="{{ route('punches.current') }}" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                                <select id="location_id" name="location_id" class="mt-1 rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                                    <option value="">All locations</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}" @selected($selectedLocationId === $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Filter
                            </button>
                            @if ($selectedLocationId > 0)
                                <a href="{{ route('punches.current') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                    Clear
                                </a>
                            @endif
                        </form>
                    @elseif ($selectedLocation)
                        <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">
                            {{ $selectedLocation->name }}
                        </div>
                    @endif
                </div>

                @if ($locationSummaries->isNotEmpty())
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($locationSummaries as $summary)
                            @php
                                $location = $summary['location'];
                                $isSelected = $selectedLocationId === $location->id;
                            @endphp
                            <a
                                href="{{ route('punches.current', ['location_id' => $location->id]) }}"
                                class="rounded-[1.4rem] border px-4 py-4 transition {{ $isSelected ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white' }}"
                            >
                                <p class="text-sm font-semibold text-slate-800">{{ $location->name }}</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['open_count'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">Clocked in now</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            @forelse ($locationGroups as $group)
                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Location</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $group['location']->name }}</h3>
                        </div>

                        <div class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">
                            {{ $group['open_count'] }} open
                        </div>
                    </div>

                    @if ($group['punches']->isEmpty())
                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No staff are currently clocked in for this location.
                        </div>
                    @else
                        <div class="overflow-x-auto px-4 pb-5 pt-5">
                            <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                                <thead>
                                    <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                        <th class="w-[15%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Staff</th>
                                        <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Staff ID</th>
                                        <th class="w-[14%] border-b border-slate-700 px-3 py-3.5">Clock In</th>
                                        <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Open For</th>
                                        <th class="w-[13%] border-b border-slate-700 px-3 py-3.5">Schedule</th>
                                        <th class="w-[15%] border-b border-slate-700 px-3 py-3.5">Schedule Check</th>
                                        <th class="w-[8%] border-b border-slate-700 px-3 py-3.5">Source</th>
                                        <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Kiosk</th>
                                        <th class="w-[15%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5">Exception</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['punches'] as $punch)
                                        @php
                                            $scheduleKey = $punch->user_id . '|' . $punch->clock_in_at?->toDateString();
                                            $scheduleText = $punch->schedule
                                                ? $punch->schedule->starts_at?->timezone(config('app.timezone'))->format('h:i A') . ' - ' . $punch->schedule->ends_at?->timezone(config('app.timezone'))->format('h:i A')
                                                : ($scheduleSummaries[$scheduleKey] ?? '-');
                                            $scheduleCheckText = $scheduleMessages[$punch->id] ?? '-';
                                            $scheduleCheckClasses = match (true) {
                                                $scheduleCheckText === 'Within scheduled range' => 'border border-emerald-200 bg-emerald-50 text-emerald-700',
                                                $scheduleCheckText === '-' => 'border border-slate-200 bg-slate-100 text-slate-700',
                                                default => 'border border-rose-200 bg-rose-50 text-rose-700',
                                            };
                                        @endphp
                                        <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top font-semibold text-slate-900">{{ $punch->user?->name ?? '-' }}</td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $punch->user?->staff_id ?: '-' }}</td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $punch->clock_in_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A') ?? '-' }}</td>
                                            <td
                                                class="border-b border-slate-200 px-3 py-3.5 align-top font-medium tabular-nums text-slate-700"
                                                style="font-variant-numeric: tabular-nums; white-space: nowrap;"
                                                data-duration
                                                data-clock-in="{{ $punch->clock_in_at?->copy()->utc()->toIso8601String() }}"
                                                data-clock-out="{{ $punch->clock_out_at?->copy()->utc()->toIso8601String() }}"
                                            >
                                                -
                                            </td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $scheduleText }}</td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                                <span class="inline-flex rounded-2xl px-2.5 py-1 text-[11px] font-semibold {{ $scheduleCheckClasses }}">
                                                    {{ $scheduleCheckText }}
                                                </span>
                                            </td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ \Illuminate\Support\Str::of($punch->source)->replace('_', ' ')->title() }}</td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $punch->kiosk?->name ?? '-' }}</td>
                                            <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $punch->violation_note ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @empty
                <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900">No staff currently clocked in</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $selectedLocation ? 'There are no open punches for ' . $selectedLocation->name . ' right now.' : 'There are no open punches across the visible locations right now.' }}
                    </p>
                </section>
            @endforelse
        </div>
    </div>

    <script>
        function formatDuration(totalSeconds) {
            if (!Number.isFinite(totalSeconds) || totalSeconds < 0) {
                return '-';
            }

            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            return [hours, minutes, seconds]
                .map((value) => String(value).padStart(2, '0'))
                .join(':');
        }

        function updateDurations() {
            const now = Date.now();

            document.querySelectorAll('[data-duration]').forEach((cell) => {
                const clockInRaw = cell.dataset.clockIn;
                const clockOutRaw = cell.dataset.clockOut;

                if (!clockInRaw) {
                    cell.textContent = '-';
                    return;
                }

                const clockIn = new Date(clockInRaw).getTime();
                const clockOut = clockOutRaw ? new Date(clockOutRaw).getTime() : now;
                const totalSeconds = Math.max(0, Math.floor((clockOut - clockIn) / 1000));

                cell.textContent = formatDuration(totalSeconds);
            });
        }

        updateDurations();
        setInterval(updateDurations, 1000);
    </script>
</x-app-layout>
