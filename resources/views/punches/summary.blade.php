<x-app-layout>
    @php
        $formatSeconds = function (int $seconds): string {
            $negative = $seconds < 0;
            $seconds = abs($seconds);
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);

            return ($negative ? '-' : '') . sprintf('%02d:%02d', $hours, $minutes);
        };

        $totalScheduled = (int) $rows->sum('scheduled_seconds');
        $totalPunched = (int) $rows->sum('punched_seconds');
        $totalVariance = $totalPunched - $totalScheduled;
        $varianceCount = $rows->filter(fn (array $row) => $row['variance_seconds'] !== 0)->count();
    @endphp

    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Staff Time Summary</h2>
                </div>

                @if ($canViewPunchLog)
                    <a href="{{ route('punches.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        Back to Punches
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Staff</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $rows->count() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Team members with scheduled or punched hours in the range.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Scheduled</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $formatSeconds($totalScheduled) }}</p>
                    <p class="mt-2 text-sm text-slate-600">Approved schedule time across the selected period.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Punched</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $formatSeconds($totalPunched) }}</p>
                    <p class="mt-2 text-sm text-slate-600">Actual worked time based on time punches.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Variance</p>
                    <p class="mt-4 text-3xl font-semibold {{ $totalVariance === 0 ? 'text-slate-900' : ($totalVariance > 0 ? 'text-emerald-700' : 'text-rose-700') }}">
                        {{ $formatSeconds($totalVariance) }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">{{ $varianceCount }} staff rows are over or under scheduled time.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('punches.summary') }}" class="grid gap-3 lg:grid-cols-[repeat(2,minmax(0,1fr))_minmax(0,1fr)_minmax(0,1.2fr)_auto] lg:items-end">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                    <div>
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected($selectedLocationId === $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" class="block text-sm font-semibold text-slate-700">Staff</label>
                        <select id="user_id" name="user_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All staff</option>
                            @foreach ($staff as $staffOption)
                                <option value="{{ $staffOption->id }}" @selected($selectedUserId === $staffOption->id)>
                                    {{ $staffOption->name }} ({{ $staffOption->staff_id ?: 'No Staff ID' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
                        <a href="{{ route('punches.summary') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[22%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Staff</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Staff ID</th>
                                <th class="w-[16%] border-b border-slate-700 px-3 py-3.5">Total Scheduled</th>
                                <th class="w-[16%] border-b border-slate-700 px-3 py-3.5">Total Punched</th>
                                <th class="w-[16%] border-b border-slate-700 px-3 py-3.5">Difference</th>
                                <th class="w-[18%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php
                                    $punchedClasses = $row['punched_seconds'] > $row['scheduled_seconds']
                                        ? 'border border-rose-200 bg-rose-50 text-rose-700'
                                        : 'border border-emerald-200 bg-emerald-50 text-emerald-700';
                                    $differenceClasses = $row['variance_seconds'] === 0
                                        ? 'border border-slate-200 bg-slate-100 text-slate-700'
                                        : ($row['variance_seconds'] > 0
                                            ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border border-rose-200 bg-rose-50 text-rose-700');
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <p class="text-sm font-semibold text-slate-900">{{ $row['name'] }}</p>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $row['staff_id'] ?: '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top font-medium text-slate-900">{{ $formatSeconds($row['scheduled_seconds']) }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $punchedClasses }}">
                                            {{ $formatSeconds($row['punched_seconds']) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $differenceClasses }}">
                                            {{ $formatSeconds($row['variance_seconds']) }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                        @if ($canViewPunchLog)
                                            <a
                                                href="{{ route('punches.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'user_id' => $row['id']]) }}"
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                                            >
                                                View Detail
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-500">Summary only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No schedule or punch totals found for this filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
