<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">Schedule Summary</h2>
                <p class="text-sm text-slate-500">
                    {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('M j, Y') }} to {{ \Illuminate\Support\Carbon::parse($dateTo)->format('M j, Y') }}
                    @if ($selectedLocation)
                        | {{ $selectedLocation->name }}
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $formatHours = fn (int $seconds) => number_format($seconds / 3600, 2) . ' h';
        $statusClasses = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'editing' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
            'partially_approved' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
            default => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.6fr_1fr]">
                    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.18),_transparent_38%),linear-gradient(135deg,_#020617,_#0f172a_60%,_#1e293b)] px-6 py-7 text-white sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Restaurant Coverage</p>
                        <h3 class="mt-3 text-3xl font-semibold tracking-tight">
                            {{ $selectedLocation?->name ?? 'Schedule Range Overview' }}
                        </h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                            Review one restaurant's schedule hours, submitted forms, and every staff line for any selected date range.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-white/10 px-3 py-1.5 text-slate-100">{{ \Illuminate\Support\Carbon::parse($dateFrom)->format('D, M j') }}</span>
                            <span class="rounded-full bg-white/10 px-3 py-1.5 text-slate-100">to {{ \Illuminate\Support\Carbon::parse($dateTo)->format('D, M j') }}</span>
                            <span class="rounded-full bg-sky-400/15 px-3 py-1.5 text-sky-100">{{ $totals['staff_count'] }} staff scheduled</span>
                        </div>
                    </div>
                    <div class="flex flex-col justify-between gap-4 bg-slate-50 px-6 py-7 sm:px-8">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Planned Hours</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $formatHours($totals['planned_seconds']) }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $totals['line_count'] }} schedule lines across {{ $totals['form_count'] }} forms</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Approved</p>
                                <p class="mt-3 text-2xl font-semibold text-emerald-900">{{ $formatHours($totals['approved_seconds']) }}</p>
                            </div>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">Pending Review</p>
                                <p class="mt-3 text-2xl font-semibold text-amber-900">{{ $formatHours($totals['submitted_seconds']) }}</p>
                            </div>
                        </div>
                        @if ($totals['rejected_seconds'] > 0)
                            <p class="text-sm font-medium text-rose-700">Rejected lines in this range: {{ $formatHours($totals['rejected_seconds']) }}</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('schedules.summary') }}" class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">Start Date</label>
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $dateFrom }}"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">End Date</label>
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $dateTo }}"
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
                            Load Range
                        </button>
                        <a
                            href="{{ route('schedules.summary') }}"
                            class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Planned Hours</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $formatHours($totals['planned_seconds']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">All schedule lines for the selected date range.</p>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Approved Hours</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-900">{{ $formatHours($totals['approved_seconds']) }}</p>
                    <p class="mt-2 text-sm text-emerald-700">Hours already cleared through approval.</p>
                </div>
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Pending Hours</p>
                    <p class="mt-3 text-3xl font-semibold text-amber-900">{{ $formatHours($totals['submitted_seconds']) }}</p>
                    <p class="mt-2 text-sm text-amber-700">Submitted lines still waiting for review.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Coverage</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $totals['staff_count'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $totals['line_count'] }} shifts for {{ $totals['form_count'] }} submitted forms.</p>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Daily Schedule Rollup</h3>
                        <p class="text-sm text-slate-500">See approved, pending, and total scheduled hours for each day in the selected date range.</p>
                    </div>
                </div>
                <div class="mb-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Approved Scheduled Hours</p>
                        <p class="mt-3 text-2xl font-semibold text-emerald-900">{{ $formatHours($totals['approved_seconds']) }}</p>
                        <p class="mt-2 text-sm text-emerald-700">Hours already approved in this date range.</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-700">Pending Hours</p>
                        <p class="mt-3 text-2xl font-semibold text-amber-900">{{ $formatHours($totals['submitted_seconds']) }}</p>
                        <p class="mt-2 text-sm text-amber-700">Submitted hours still waiting for review.</p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Scheduled Hours</p>
                        <p class="mt-3 text-2xl font-semibold text-sky-900">{{ $formatHours($totals['scheduled_seconds']) }}</p>
                        <p class="mt-2 text-sm text-sky-700">Approved + pending hours included in the active schedule.</p>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold">Scheduled Hours</th>
                                    <th class="px-4 py-3 text-left font-semibold">Approved Hours</th>
                                    <th class="px-4 py-3 text-left font-semibold">Pending Hours</th>
                                    <th class="px-4 py-3 text-left font-semibold">Rejected Hours</th>
                                    <th class="px-4 py-3 text-left font-semibold">Lines</th>
                                    <th class="px-4 py-3 text-left font-semibold">Staff</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($dailySummaries as $day)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900">{{ $day['date']->format('Y-m-d') }}</div>
                                            <div class="text-xs text-slate-500">{{ $day['date']->format('l') }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $formatHours($day['scheduled_seconds']) }}</td>
                                        <td class="px-4 py-3 text-emerald-700">{{ $formatHours($day['approved_seconds']) }}</td>
                                        <td class="px-4 py-3 text-amber-700">{{ $formatHours($day['submitted_seconds']) }}</td>
                                        <td class="px-4 py-3 text-rose-700">{{ $formatHours($day['rejected_seconds']) }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $day['line_count'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $day['staff_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            @if ($scheduleRows->isEmpty())
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">No schedules found for this range</h3>
                    <p class="mt-2 text-sm text-slate-500">Try another date range, location, or staff search to load schedule details.</p>
                </section>
            @else
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Submitted Forms In Range</h3>
                        <p class="text-sm text-slate-500">Daily forms that make up the selected restaurant's schedule for this date range.</p>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold">Location</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold">Submitted By</th>
                                        <th class="px-4 py-3 text-left font-semibold">Submitted At</th>
                                        <th class="px-4 py-3 text-left font-semibold">Hours</th>
                                        <th class="px-4 py-3 text-left font-semibold">Lines</th>
                                        <th class="px-4 py-3 text-left font-semibold">Range</th>
                                        <th class="px-4 py-3 text-left font-semibold">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach ($formSummaries as $formSummary)
                                        <tr class="align-top">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $formSummary['shift_date']?->format('Y-m-d') ?? '-' }}</div>
                                                <div class="text-xs text-slate-500">{{ $formSummary['shift_date']?->format('l') ?? '' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">{{ $formSummary['location_name'] }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($formSummary['form_status']) }}">
                                                    {{ \Illuminate\Support\Str::of($formSummary['form_status'])->replace('_', ' ')->title() }}
                                                </span>
                                                <div class="mt-2 space-y-1 text-xs text-slate-500">
                                                    <div>Approved: {{ $formSummary['approved_count'] }}</div>
                                                    <div>Submitted: {{ $formSummary['submitted_count'] }}</div>
                                                    <div>Rejected: {{ $formSummary['rejected_count'] }}</div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $formSummary['creator_name'] }}
                                                @if ($formSummary['approver_name'])
                                                    <div class="mt-1 text-xs text-slate-500">Approved by {{ $formSummary['approver_name'] }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $formSummary['submitted_at']?->format('Y-m-d H:i') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $formatHours($formSummary['planned_seconds']) }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $formSummary['lines_count'] }}</td>
                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $formSummary['starts_at_min']?->format('H:i') ?? '-' }} to {{ $formSummary['ends_at_max']?->format('H:i') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if ($formSummary['form_id'] > 0)
                                                    <a
                                                        href="{{ route('schedules.form', ['form_id' => $formSummary['form_id']]) }}"
                                                        class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-800 transition hover:bg-slate-200"
                                                    >
                                                        View Detail
                                                    </a>
                                                @else
                                                    <span class="text-xs text-slate-500">Unavailable</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Hours By Staff</h3>
                        <p class="text-sm text-slate-500">Range totals for each staff member included in this restaurant schedule.</p>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Staff</th>
                                        <th class="px-4 py-3 text-left font-semibold">Staff ID</th>
                                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                                        <th class="px-4 py-3 text-left font-semibold">Shifts</th>
                                        <th class="px-4 py-3 text-left font-semibold">Planned</th>
                                        <th class="px-4 py-3 text-left font-semibold">Approved</th>
                                        <th class="px-4 py-3 text-left font-semibold">Pending</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach ($staffSummaries as $summary)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $summary['user']->name }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $summary['user']->staff_id ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $summary['user']->position?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $summary['line_count'] }}</td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $formatHours($summary['planned_seconds']) }}</td>
                                            <td class="px-4 py-3 text-emerald-700">{{ $formatHours($summary['approved_seconds']) }}</td>
                                            <td class="px-4 py-3 text-amber-700">{{ $formatHours($summary['submitted_seconds']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Detailed Schedule Lines</h3>
                        <p class="text-sm text-slate-500">Every line submitted for the selected date range, with hours and form detail access.</p>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold">Staff</th>
                                        <th class="px-4 py-3 text-left font-semibold">Position</th>
                                        <th class="px-4 py-3 text-left font-semibold">Form</th>
                                        <th class="px-4 py-3 text-left font-semibold">Clock In</th>
                                        <th class="px-4 py-3 text-left font-semibold">Clock Out</th>
                                        <th class="px-4 py-3 text-left font-semibold">Hours</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold">Approver</th>
                                        <th class="px-4 py-3 text-left font-semibold">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @foreach ($scheduleRows as $schedule)
                                        <tr class="align-top">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $schedule->shift_date->format('Y-m-d') }}</div>
                                                <div class="text-xs text-slate-500">{{ $schedule->shift_date->format('l') }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $schedule->user->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $schedule->user->staff_id ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">{{ $schedule->user->position?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">
                                                @if ($schedule->schedule_form_id)
                                                    <a href="{{ route('schedules.form', ['form_id' => $schedule->schedule_form_id]) }}" class="font-semibold text-sky-700 hover:text-sky-800">
                                                        Form #{{ $schedule->schedule_form_id }}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                                <div class="mt-1 text-xs text-slate-500">{{ $schedule->location?->name ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">{{ $schedule->starts_at->format('H:i') }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $schedule->ends_at->format('H:i') }}</td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $formatHours((int) $schedule->duration_seconds) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses($schedule->status) }}">
                                                    {{ \Illuminate\Support\Str::of($schedule->status)->replace('_', ' ')->title() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-700">{{ $schedule->approver?->name ?? $schedule->form?->approver?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $schedule->notes ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
