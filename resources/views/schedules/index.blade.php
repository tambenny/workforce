<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schedules</h2>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('schedules.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="history" value="{{ $showHistory ? 1 : 0 }}">
                    <label for="location_id" class="text-sm font-semibold text-slate-700">Location</label>
                    <select id="location_id" name="location_id" class="rounded border-slate-300 text-sm">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <label for="staff_name" class="text-sm font-semibold text-slate-700">Staff</label>
                    <input
                        id="staff_name"
                        name="staff_name"
                        type="search"
                        value="{{ $staffName ?? '' }}"
                        placeholder="Search staff name..."
                        class="w-56 rounded border-slate-300 text-sm"
                        oninput="clearTimeout(this._t); this._t=setTimeout(() => this.form.submit(), 350);"
                    >
                    <button class="rounded bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Filter</button>
                </form>
                <a
                    href="{{ route('schedules.index', array_filter(['location_id' => $selectedLocationId, 'staff_name' => $staffName ?? null, 'history' => $showHistory ? null : 1])) }}"
                    class="rounded px-4 py-2 text-sm font-semibold {{ $showHistory ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' }}"
                >
                    {{ $showHistory ? 'Current Only' : 'History' }}
                </a>
                @if (auth()->user()->role === 'manager')
                    <a href="{{ route('schedules.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Create Schedule</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (!empty($staffName))
                <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <h3 class="mb-3 text-base font-semibold text-slate-800">Staff Schedule Results: "{{ $staffName }}"</h3>
                    <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                        <table class="min-w-full table-fixed border-collapse text-sm">
                            <thead class="bg-slate-200 text-slate-800">
                                <tr>
                                    <th class="w-10 border border-slate-300 px-3 py-2 text-left font-semibold">#</th>
                                    <th class="w-40 border border-slate-300 px-3 py-2 text-left font-semibold">Staff</th>
                                    <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Location</th>
                                    <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Shift Date</th>
                                    <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Weekday</th>
                                    <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Clock In</th>
                                    <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Clock Out</th>
                                    <th class="w-20 border border-slate-300 px-3 py-2 text-left font-semibold">Status</th>
                                    <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Approver</th>
                                    <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted By</th>
                                    <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:nth-child(even)]:bg-slate-50">
                                @forelse ($staffSchedules as $i => $row)
                                    <tr>
                                        <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-700">{{ ($staffSchedules->firstItem() ?? 1) + $i }}</td>
                                        <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-900">{{ $row->staff_name }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ $row->location_name }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($row->shift_date)->format('Y-m-d') }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($row->shift_date)->format('l') }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($row->starts_at)->format('H:i') }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($row->ends_at)->format('H:i') }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Str::of($row->status)->replace('_', ' ')->title() }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ $row->approver_name ?? '-' }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ $row->creator_name ?? 'System' }}</td>
                                        <td class="border border-slate-200 px-3 py-3">{{ $row->notes ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="border border-slate-200 px-3 py-4 text-center text-slate-600">
                                            No staff schedule rows found for this search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $staffSchedules->links() }}</div>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif

                <h3 class="mb-3 text-base font-semibold text-slate-800">Pending Approval</h3>
                <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                    <table class="min-w-full table-fixed border-collapse text-sm">
                        <thead class="bg-slate-200 text-slate-800">
                            <tr>
                                <th class="w-10 border border-slate-300 px-3 py-2 text-left font-semibold">#</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Location</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Shift Date</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Weekday</th>
                                <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted By</th>
                                <th class="w-36 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted At</th>
                                <th class="w-40 border border-slate-300 px-3 py-2 text-left font-semibold">Range</th>
                                <th class="w-16 border border-slate-300 px-3 py-2 text-left font-semibold">Lines</th>
                                <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Status Mix</th>
                                <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:nth-child(even)]:bg-slate-50">
                            @forelse ($pendingForms as $i => $form)
                                <tr>
                                    <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-700">{{ ($pendingForms->firstItem() ?? 1) + $i }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->location_name }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->creator_name ?? 'System' }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->lines_count }}</td>
                                    <td class="border border-slate-200 px-3 py-3">
                                        <span class="font-semibold">Form: {{ \Illuminate\Support\Str::of($form->form_status)->replace('_', ' ')->title() }}</span><br>
                                        <span class="text-amber-700">Submitted: {{ $form->submitted_count }}</span>,
                                        <span class="text-green-700">Approved: {{ $form->approved_count }}</span>,
                                        <span class="text-red-700">Rejected: {{ $form->rejected_count }}</span>
                                    </td>
                                    <td class="border border-slate-200 px-3 py-3">
                                        <a href="{{ route('schedules.form', ['form_id' => $form->form_id]) }}" class="inline-flex h-9 w-24 items-center justify-center rounded-md bg-slate-100 text-xs font-semibold text-slate-800 transition hover:bg-slate-200">
                                            View Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="border border-slate-200 px-3 py-4 text-center text-slate-600">No pending schedule forms.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $pendingForms->links() }}</div>

                <h3 class="mb-3 mt-8 text-base font-semibold text-slate-800">Completed Approval</h3>
                <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                    <table class="min-w-full table-fixed border-collapse text-sm">
                        <thead class="bg-slate-200 text-slate-800">
                            <tr>
                                <th class="w-10 border border-slate-300 px-3 py-2 text-left font-semibold">#</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Location</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Shift Date</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Weekday</th>
                                <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted By</th>
                                <th class="w-36 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted At</th>
                                <th class="w-40 border border-slate-300 px-3 py-2 text-left font-semibold">Range</th>
                                <th class="w-16 border border-slate-300 px-3 py-2 text-left font-semibold">Lines</th>
                                <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Status Mix</th>
                                <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:nth-child(even)]:bg-slate-50">
                            @forelse ($completedForms as $i => $form)
                                <tr>
                                    <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-700">{{ ($completedForms->firstItem() ?? 1) + $i }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->location_name }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->creator_name ?? 'System' }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                    <td class="border border-slate-200 px-3 py-3">{{ $form->lines_count }}</td>
                                    <td class="border border-slate-200 px-3 py-3">
                                        <span class="font-semibold">Form: {{ \Illuminate\Support\Str::of($form->form_status)->replace('_', ' ')->title() }}</span><br>
                                        <span class="text-amber-700">Submitted: {{ $form->submitted_count }}</span>,
                                        <span class="text-green-700">Approved: {{ $form->approved_count }}</span>,
                                        <span class="text-red-700">Rejected: {{ $form->rejected_count }}</span>
                                    </td>
                                    <td class="border border-slate-200 px-3 py-3">
                                        <a href="{{ route('schedules.form', ['form_id' => $form->form_id]) }}" class="inline-flex h-9 w-24 items-center justify-center rounded-md bg-slate-100 text-xs font-semibold text-slate-800 transition hover:bg-slate-200">
                                            View Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="border border-slate-200 px-3 py-4 text-center text-slate-600">No completed schedule forms.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $completedForms->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
