<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">{{ $isManagerView ? 'Punches' : 'My Punches' }}</h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($canViewCurrentStaff)
                        <a href="{{ route('punches.current') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                            Current Staff
                        </a>
                    @endif
                    @if ($canViewPunchPhotos)
                        <a href="{{ route('punches.photos', request()->only(['date_from', 'date_to', 'user_id'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                            Punch Photos
                        </a>
                    @endif
                    @if ($canViewPunchSummary)
                        <a href="{{ route('punches.summary', request()->only(['date_from', 'date_to'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Staff Time Summary
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Punches</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Punch records returned by the current filter.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Exceptions</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['exceptions'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Punches flagged with violation or manual clock notes.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Open</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['open'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Punches that are still active and have no clock out yet.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Staff</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['staff'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Unique staff members included in the current result set.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('punches.index') }}" class="grid gap-3 lg:grid-cols-[repeat(2,minmax(0,1fr))_minmax(0,1.1fr)_auto_auto_auto] lg:items-end">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>

                    @if ($isManagerView)
                        <div>
                            <label for="user_id" class="block text-sm font-semibold text-slate-700">Staff</label>
                            <select id="user_id" name="user_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                                <option value="">All staff</option>
                                @foreach ($staffOptions as $staffOption)
                                    <option value="{{ $staffOption->id }}" @selected($selectedUserId === $staffOption->id)>
                                        {{ $staffOption->name }} ({{ $staffOption->staff_id ?: 'No Staff ID' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="checkbox" name="exceptions" value="1" @checked($showExceptions) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span class="text-sm font-semibold text-slate-700">Exceptions only</span>
                    </label>

                    @if ($isManagerView)
                        <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <input type="checkbox" name="open_now" value="1" @checked($showOpenNow) class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-semibold text-slate-700">Currently clocked in</span>
                        </label>
                    @endif

                    <div class="flex gap-2">
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
                        <a href="{{ route('punches.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                @if (session('status'))
                    <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->has('clock'))
                    <div class="mx-6 mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        {{ $errors->first('clock') }}
                    </div>
                @endif

                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                @if ($isManagerView)
                                    <th class="w-[14%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Staff</th>
                                @endif
                                <th class="w-[14%] border-b border-slate-700 px-3 py-3.5">Schedule</th>
                                <th class="w-[13%] border-b border-slate-700 px-3 py-3.5">Clock In</th>
                                <th class="w-[13%] border-b border-slate-700 px-3 py-3.5">Clock Out</th>
                                <th class="w-[9%] border-b border-slate-700 px-3 py-3.5">Duration</th>
                                <th class="w-[8%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[17%] border-b border-slate-700 px-3 py-3.5">Schedule Check</th>
                                <th class="w-[6%] border-b border-slate-700 px-3 py-3.5">Source</th>
                                <th class="{{ $isManagerView ? 'w-[14%]' : 'w-[20%]' }} border-b border-slate-700 px-3 py-3.5">Exception</th>
                                @if ($isManagerView)
                                    <th class="w-[12%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Action</th>
                                @else
                                    <th class="rounded-r-2xl border-b border-slate-700 px-3 py-3.5"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($punches as $p)
                                @php
                                    $scheduleKey = $p->user_id . '|' . $p->clock_in_at?->toDateString();
                                    $scheduleText = $p->schedule
                                        ? $p->schedule->starts_at?->timezone(config('app.timezone'))->format('h:i A') . ' - ' . $p->schedule->ends_at?->timezone(config('app.timezone'))->format('h:i A')
                                        : ($scheduleSummaries[$scheduleKey] ?? '-');
                                    $scheduleCheckText = $scheduleMessages[$p->id] ?? '-';
                                    $scheduleCheckClasses = match (true) {
                                        $scheduleCheckText === 'Within scheduled range' => 'border border-emerald-200 bg-emerald-50 text-emerald-700',
                                        $scheduleCheckText === '-' => 'border border-slate-200 bg-slate-100 text-slate-700',
                                        default => 'border border-rose-200 bg-rose-50 text-rose-700',
                                    };
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    @if ($isManagerView)
                                        <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">{{ $p->user?->name ?? '-' }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $p->user?->staff_id ?: 'No Staff ID' }}</p>
                                            </div>
                                        </td>
                                    @endif
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top text-slate-700">{{ $scheduleText }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $p->clock_in_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A') ?? '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $p->clock_out_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A') ?? '-' }}</td>
                                    <td
                                        class="border-b border-slate-200 px-3 py-3.5 align-top font-medium tabular-nums text-slate-700"
                                        style="font-variant-numeric: tabular-nums; white-space: nowrap;"
                                        data-duration
                                        data-clock-in="{{ $p->clock_in_at?->copy()->utc()->toIso8601String() }}"
                                        data-clock-out="{{ $p->clock_out_at?->copy()->utc()->toIso8601String() }}"
                                    >
                                        -
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $p->location?->name ?? '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-2xl px-2.5 py-1 text-[11px] font-semibold {{ $scheduleCheckClasses }}">
                                            {{ $scheduleCheckText }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top uppercase text-slate-700">{{ $p->source }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $p->violation_note ?: '-' }}</td>
                                    @if ($isManagerView)
                                        <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                            @if (! $p->clock_out_at)
                                                <form method="POST" action="{{ route('punches.force-clock-out', $p) }}" class="flex flex-col items-end gap-2">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" class="w-full max-w-36 rounded-2xl border-slate-200 px-3 py-2 text-xs focus:border-amber-400 focus:ring-amber-400" required>
                                                    <button type="submit" class="inline-flex items-center rounded-full bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-700">
                                                        Force Out
                                                    </button>
                                                </form>
                                            @else
                                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                    Closed
                                                </span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="border-b border-slate-200 px-3 py-3.5"></td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isManagerView ? 10 : 9 }}" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No punches found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $punches->links() }}
                </div>
            </section>
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
                .map(value => String(value).padStart(2, '0'))
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
