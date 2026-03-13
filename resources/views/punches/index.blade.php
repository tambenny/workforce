<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $isManagerView ? 'Punches' : 'My Punches' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div></div>
                    <div class="flex items-center gap-3">
                        @if ($isManagerView)
                            <a href="{{ route('punches.photos', request()->only(['date_from', 'date_to', 'user_id'])) }}" class="rounded bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                                Punch Photos
                            </a>
                        @endif
                        <a href="{{ route('punches.summary', request()->only(['date_from', 'date_to'])) }}" class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Staff Time Summary
                        </a>
                    </div>
                </div>

                @if (session('status'))
                    <p class="mb-4 text-sm text-emerald-700">{{ session('status') }}</p>
                @endif
                @if ($errors->has('clock'))
                    <p class="mb-4 text-sm text-red-700">{{ $errors->first('clock') }}</p>
                @endif

                <form method="GET" action="{{ route('punches.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-1 rounded border-gray-300 text-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-1 rounded border-gray-300 text-sm">
                    </div>
                    @if ($isManagerView)
                        <div>
                            <label for="user_id" class="block text-sm font-semibold text-slate-700">Staff</label>
                            <select id="user_id" name="user_id" class="mt-1 rounded border-gray-300 text-sm">
                                <option value="">All staff</option>
                                @foreach ($staffOptions as $staffOption)
                                    <option value="{{ $staffOption->id }}" @selected($selectedUserId === $staffOption->id)>
                                        {{ $staffOption->name }} ({{ $staffOption->staff_id ?: 'No Staff ID' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <label class="inline-flex items-center gap-2 pb-1">
                        <input type="checkbox" name="exceptions" value="1" @checked($showExceptions)>
                        <span class="text-sm font-semibold text-slate-700">Exceptions only</span>
                    </label>
                    @if ($isManagerView)
                        <label class="inline-flex items-center gap-2 pb-1">
                            <input type="checkbox" name="open_now" value="1" @checked($showOpenNow)>
                            <span class="text-sm font-semibold text-slate-700">Currently clocked in</span>
                        </label>
                    @endif
                    <button class="rounded bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Filter</button>
                </form>

                @if ($isManagerView && $showOpenNow)
                    <p class="mb-4 text-sm text-slate-600">
                        Open punches right now: <strong>{{ $punches->total() }}</strong>
                    </p>
                @endif

                <p class="mb-4 text-sm text-slate-500">
                    Display timezone: <strong>{{ config('app.timezone') }}</strong>
                </p>

                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b">
                            @if ($isManagerView)
                                <th class="border border-slate-200 px-3 py-2 text-left">Staff</th>
                            @endif
                            <th class="border border-slate-200 px-3 py-2 text-left">Schedule</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Clock In</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Clock Out</th>
                            <th class="w-28 border border-slate-200 px-3 py-2 text-left">Duration</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Location</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Schedule Check</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Source</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Exception</th>
                            @if ($isManagerView)
                                <th class="border border-slate-200 px-3 py-2 text-left">Action</th>
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
                                $scheduleCheckStyle = match (true) {
                                    $scheduleCheckText === 'Within scheduled range' => 'color:#166534;',
                                    $scheduleCheckText === '-' => 'color:#475569;',
                                    default => 'color:#991b1b;',
                                };
                            @endphp
                            <tr>
                                @if ($isManagerView)
                                    <td class="border border-slate-200 px-3 py-2">{{ $p->user?->name ?? '-' }}</td>
                                @endif
                                <td class="border border-slate-200 px-3 py-2 text-slate-700">{{ $scheduleText }}</td>
                                <td class="border border-slate-200 px-3 py-2">{{ $p->clock_in_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A') ?? '-' }}</td>
                                <td class="border border-slate-200 px-3 py-2">{{ $p->clock_out_at?->timezone(config('app.timezone'))->format('Y-m-d h:i:s A') ?? '-' }}</td>
                                <td
                                    class="w-28 border border-slate-200 px-3 py-2 font-medium tabular-nums text-slate-700"
                                    style="font-variant-numeric: tabular-nums; white-space: nowrap;"
                                    data-duration
                                    data-clock-in="{{ $p->clock_in_at?->copy()->utc()->toIso8601String() }}"
                                    data-clock-out="{{ $p->clock_out_at?->copy()->utc()->toIso8601String() }}"
                                >
                                    -
                                </td>
                                <td class="border border-slate-200 px-3 py-2">{{ $p->location?->name }}</td>
                                <td class="border border-slate-200 px-3 py-2">
                                    <span class="text-xs font-semibold" style="{{ $scheduleCheckStyle }}">
                                        {{ $scheduleCheckText }}
                                    </span>
                                </td>
                                <td class="border border-slate-200 px-3 py-2">{{ $p->source }}</td>
                                <td class="border border-slate-200 px-3 py-2">{{ $p->violation_note ?: '-' }}</td>
                                @if ($isManagerView)
                                    <td class="border border-slate-200 px-3 py-2">
                                        @if (! $p->clock_out_at)
                                            <form method="POST" action="{{ route('punches.force-clock-out', $p) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input type="text" name="reason" placeholder="Reason" class="w-36 rounded border-gray-300 text-xs" required>
                                                <button type="submit" class="rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">
                                                    Force Clock Out
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-500">Closed</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isManagerView ? 10 : 8 }}" class="border border-slate-200 px-3 py-4 text-center text-gray-500">No punches found.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $punches->links() }}</div>
            </div>
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
