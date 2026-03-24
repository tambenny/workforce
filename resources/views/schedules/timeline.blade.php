<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Schedule Timeline</h2>
                <p class="text-sm text-slate-500">Filter by date and location to see each staff shift from clock in to clock out on a visual time chart.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->canViewScheduleSummary())
                    <a
                        href="{{ route('schedules.summary', ['date_from' => $selectedDate, 'date_to' => $selectedDate, 'location_id' => $selectedLocationId ?: null]) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Open Summary
                    </a>
                @endif
                <a
                    href="{{ route('schedules.index') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    All Schedules
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $chartRangeEndLabel = $chartEnd->equalTo($chartStart->copy()->addDay()->startOfDay())
            ? $chartEnd->format('g:i A')
            : ($chartEnd->isSameDay($chartStart) ? $chartEnd->format('g:i A') : $chartEnd->format('M j g:i A'));
        $chartRangeLabel = $chartStart->format('g:i A') . ' - ' . $chartRangeEndLabel;
        $statusIndicatorClasses = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-400',
            'rejected' => 'bg-rose-400',
            'editing' => 'bg-sky-400',
            'partially_approved' => 'bg-indigo-400',
            default => 'bg-amber-400',
        };
        $badgeClasses = fn (string $status) => match ($status) {
            'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
            'editing' => 'border-sky-200 bg-sky-50 text-sky-700',
            'partially_approved' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('schedules.timeline') }}" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                    <div>
                        <label for="shift_date" class="block text-sm font-semibold text-slate-700">Date</label>
                        <input
                            id="shift_date"
                            name="shift_date"
                            type="date"
                            value="{{ $selectedDate }}"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                    </div>

                    @if ($locations->count() > 1)
                        <div>
                            <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                            <select id="location_id" name="location_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">
                                <option value="">All locations</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif ($selectedLocation)
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Location</label>
                            <div class="mt-1 flex h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-700">
                                {{ $selectedLocation->name }}
                            </div>
                            <input type="hidden" name="location_id" value="{{ $selectedLocationId }}">
                        </div>
                    @endif

                    <div>
                        <label for="staff_name" class="block text-sm font-semibold text-slate-700">Staff Search</label>
                        <input
                            id="staff_name"
                            name="staff_name"
                            type="search"
                            value="{{ $staffName }}"
                            placeholder="Search staff name..."
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Load Timeline
                        </button>
                        <a
                            href="{{ route('schedules.timeline') }}"
                            class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            @if ($timelineRows->isEmpty())
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">No shifts found for this date</h3>
                    <p class="mt-2 text-sm text-slate-500">Try another date, location, or staff search to load the shift timeline.</p>
                </section>
            @else
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Shift Timeline</h3>
                            <p class="text-sm text-slate-500">Each staff member stays on one timeline lane, even with multiple shifts in the same day.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-slate-700">Bar color = staff</span>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-700">Green dot = approved</span>
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-amber-700">Amber dot = submitted</span>
                            <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-rose-700">Red dot = rejected</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[1080px] w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="sticky left-0 z-20 w-72 border-b border-r border-slate-200 bg-slate-50 px-4 py-4 text-left align-top">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Y Axis</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">Staff Name</p>
                                            <p class="mt-1 text-xs text-slate-500">Position, ID, location, and total scheduled time.</p>
                                        </div>
                                    </th>
                                    <th class="border-b border-slate-200 bg-slate-50 px-4 py-4 align-top">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Timeline</p>
                                                <p class="text-sm font-semibold text-slate-900">{{ $chartRangeLabel }}</p>
                                            </div>
                                            <p class="text-xs text-slate-500">{{ $summary['shift_count'] }} shift blocks displayed</p>
                                        </div>
                                        <div class="relative h-14 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            @foreach ($timelineMarkers as $marker)
                                                <div class="absolute inset-y-0" style="left: {{ $marker['offset_percent'] }}%;">
                                                    <div class="h-full border-l border-slate-300"></div>
                                                    <span class="absolute left-1 top-3 whitespace-nowrap text-[11px] font-semibold text-slate-500">{{ $marker['label'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timelineRows as $row)
                                    @php
                                        $rowHeight = 64;
                                    @endphp
                                    <tr>
                                        <td class="sticky left-0 z-10 w-72 border-b border-r border-slate-200 bg-white px-4 py-4 align-top">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="inline-block h-3 w-3 rounded-full border"
                                                            style="background-color: {{ $row['color']['bar_background'] }}; border-color: {{ $row['color']['bar_border'] }};"
                                                        ></span>
                                                        <h4 class="text-base font-semibold text-slate-900">{{ $row['user']?->name ?? 'Unknown Staff' }}</h4>
                                                    </div>
                                                    <p class="text-sm text-slate-500">
                                                        {{ $row['user']?->position?->name ?? 'No position assigned' }}
                                                        @if ($row['user']?->staff_id)
                                                            | ID {{ $row['user']->staff_id }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">{{ $row['total_duration_label'] }}</span>
                                            </div>
                                            <p class="mt-3 text-sm text-slate-500">{{ $row['location']?->name ?? ($selectedLocation?->name ?? 'Location not set') }}</p>
                                        </td>
                                        <td class="border-b border-slate-200 px-4 py-4 align-top">
                                            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50" style="height: {{ $rowHeight }}px;">
                                                <div class="absolute left-0 right-0 top-1/2 z-0 -translate-y-1/2 border-t border-slate-200"></div>
                                                @foreach ($timelineMarkers as $marker)
                                                    <div class="absolute inset-y-0 z-0" style="left: {{ $marker['offset_percent'] }}%;">
                                                        <div class="h-full border-l border-dashed border-slate-300"></div>
                                                    </div>
                                                @endforeach

                                                @foreach ($row['entries'] as $entry)
                                                    <div
                                                        class="absolute flex h-9 items-center rounded-xl px-3 text-xs font-semibold text-white shadow-sm ring-1"
                                                        style="left: {{ $entry['start_percent'] }}%; width: {{ $entry['width_percent'] }}%; top: 13px; z-index: {{ 10 + $loop->index }}; background-color: {{ $row['color']['bar_background'] }}; border-color: {{ $row['color']['bar_border'] }}; box-shadow: 0 10px 18px -14px {{ $row['color']['bar_border'] }};"
                                                        title="{{ $entry['start_label'] }} - {{ $entry['end_label'] }} ({{ ucwords(str_replace('_', ' ', $entry['status'])) }})"
                                                    >
                                                        <span class="mr-2 inline-block h-2.5 w-2.5 shrink-0 rounded-full {{ $statusIndicatorClasses($entry['status']) }}"></span>
                                                        <span class="truncate">{{ $entry['start_label'] }} - {{ $entry['timeline_end_label'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($row['entries'] as $entry)
                                                    <span
                                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold {{ $badgeClasses($entry['status']) }}"
                                                        style="border-left-width: 4px; border-left-color: {{ $row['color']['accent'] }};"
                                                    >
                                                        <span
                                                            class="inline-block h-2.5 w-2.5 rounded-full {{ $statusIndicatorClasses($entry['status']) }}"
                                                        ></span>
                                                        <span>{{ $entry['start_label'] }} - {{ $entry['end_label'] }}</span>
                                                        <span>{{ $entry['duration_label'] }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
