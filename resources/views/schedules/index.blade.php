<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Schedules</h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('schedules.index', array_filter(['location_id' => $selectedLocationId, 'staff_name' => $staffName ?? null, 'history' => $showHistory ? null : 1])) }}"
                        class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $showHistory ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' }}"
                    >
                        {{ $showHistory ? 'Current Only' : 'All Dates' }}
                    </a>
                    @if (auth()->user()->hasSchedulePermission('create'))
                        <a href="{{ route('schedules.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500">
                            Create Schedule
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
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Pending Forms</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $pendingForms->total() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Schedule forms currently waiting for approval.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Completed Forms</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $completedForms->total() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Approved, rejected, or editing forms in this scope.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Staff Matches</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $staffSchedules->total() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Schedule rows returned for the current staff filter.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Mode</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $showHistory ? 'All Dates' : 'Current Only' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Current filters scoped to {{ $locations->firstWhere('id', $selectedLocationId)?->name ?? 'all visible locations' }}.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('schedules.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_auto_auto] lg:items-end">
                    <input type="hidden" name="history" value="{{ $showHistory ? 1 : 0 }}">
                    <div>
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="staff_name" class="block text-sm font-semibold text-slate-700">Staff</label>
                        <input
                            id="staff_name"
                            name="staff_name"
                            type="search"
                            value="{{ $staffName ?? '' }}"
                            placeholder="Search staff name..."
                            class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        >
                    </div>
                    <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
                    <a href="{{ route('schedules.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                </form>
            </section>

            @if (! empty($staffName))
                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                    <div class="border-b border-slate-200 px-6 py-6">
                        <h3 class="text-2xl font-semibold text-slate-900">Matches for "{{ $staffName }}"</h3>
                    </div>
                    <div class="overflow-x-auto px-4 pb-5 pt-5">
                        <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                            <thead>
                                <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                    <th class="w-[5%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">#</th>
                                    <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Staff</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Shift Date</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Time</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Status</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Approver</th>
                                    <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Submitted By</th>
                                    <th class="w-[23%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($staffSchedules as $i => $row)
                                    <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                        <td class="border-b border-slate-200 px-3 py-3.5 font-semibold text-slate-700">{{ ($staffSchedules->firstItem() ?? 1) + $i }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5 font-semibold text-slate-900">{{ $row->staff_name }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ $row->location_name }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($row->shift_date)->format('Y-m-d') }}<br><span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($row->shift_date)->format('l') }}</span></td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($row->starts_at)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($row->ends_at)->format('H:i') }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Str::of($row->status)->replace('_', ' ')->title() }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ $row->approver_name ?? '-' }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ $row->creator_name ?? 'System' }}</td>
                                        <td class="border-b border-slate-200 px-3 py-3.5">{{ $row->notes ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                            No staff schedule rows found for this search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-6 py-5">{{ $staffSchedules->links() }}</div>
                </section>
            @endif

            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <h3 class="text-2xl font-semibold text-slate-900">Pending approval</h3>
                </div>
                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[5%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">#</th>
                                <th class="w-[9%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Shift Date</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Submitted By</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Submitted At</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Range</th>
                                <th class="w-[7%] border-b border-slate-700 px-3 py-3.5">Lines</th>
                                <th class="w-[23%] border-b border-slate-700 px-3 py-3.5">Status Mix</th>
                                <th class="w-[10%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingForms as $i => $form)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 font-semibold text-slate-700">{{ ($pendingForms->firstItem() ?? 1) + $i }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->location_name }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}<br><span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</span></td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->creator_name ?? 'System' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->lines_count }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">
                                        <span class="font-semibold">Form: {{ \Illuminate\Support\Str::of($form->form_status)->replace('_', ' ')->title() }}</span><br>
                                        <span class="text-amber-700">Submitted: {{ $form->submitted_count }}</span>,
                                        <span class="text-green-700">Approved: {{ $form->approved_count }}</span>,
                                        <span class="text-rose-700">Rejected: {{ $form->rejected_count }}</span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right">
                                        <a href="{{ route('schedules.form', ['form_id' => $form->form_id]) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                            View Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No pending schedule forms.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-6 py-5">{{ $pendingForms->links() }}</div>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <h3 class="text-2xl font-semibold text-slate-900">Completed approval</h3>
                </div>
                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[5%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">#</th>
                                <th class="w-[9%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Shift Date</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Submitted By</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Submitted At</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Range</th>
                                <th class="w-[7%] border-b border-slate-700 px-3 py-3.5">Lines</th>
                                <th class="w-[23%] border-b border-slate-700 px-3 py-3.5">Status Mix</th>
                                <th class="w-[10%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($completedForms as $i => $form)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 font-semibold text-slate-700">{{ ($completedForms->firstItem() ?? 1) + $i }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->location_name }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}<br><span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</span></td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->creator_name ?? 'System' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->lines_count }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">
                                        <span class="font-semibold">Form: {{ \Illuminate\Support\Str::of($form->form_status)->replace('_', ' ')->title() }}</span><br>
                                        <span class="text-amber-700">Submitted: {{ $form->submitted_count }}</span>,
                                        <span class="text-green-700">Approved: {{ $form->approved_count }}</span>,
                                        <span class="text-rose-700">Rejected: {{ $form->rejected_count }}</span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right">
                                        <a href="{{ route('schedules.form', ['form_id' => $form->form_id]) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                            View Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No completed schedule forms.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-6 py-5">{{ $completedForms->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
