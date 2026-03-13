<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schedule Approvals</h2>
            <a
                href="{{ route('schedules.approvals', array_filter(['location_id' => $selectedLocationId, 'history' => $showHistory ? null : 1])) }}"
                class="rounded px-4 py-2 text-sm font-semibold {{ $showHistory ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' }}"
            >
                {{ $showHistory ? 'Current Only' : 'History' }}
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif
                @if ($errors->has('reason'))
                    <p class="mb-4 text-sm text-red-700">{{ $errors->first('reason') }}</p>
                @endif

                <form method="GET" action="{{ route('schedules.approvals') }}" class="mb-4 flex flex-wrap items-end gap-3">
                    <div class="flex flex-col justify-end">
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 block rounded-md border-gray-300 text-sm" style="height:42px;">
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="mt-6 inline-flex items-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700" style="height:42px;">Load Forms</button>
                </form>

                <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                    <table class="min-w-full table-fixed border-collapse text-sm">
                        <thead class="bg-slate-200 text-slate-800">
                            <tr>
                                <th class="w-10 border border-slate-300 px-3 py-2 text-left font-semibold">#</th>
                                <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Shift Date</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Weekday</th>
                                <th class="w-24 border border-slate-300 px-3 py-2 text-left font-semibold">Location</th>
                                <th class="w-32 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted By</th>
                                <th class="w-40 border border-slate-300 px-3 py-2 text-left font-semibold">Submitted At</th>
                                <th class="border border-slate-300 px-3 py-2 text-left font-semibold">Range</th>
                                <th class="w-16 border border-slate-300 px-3 py-2 text-left font-semibold">Lines</th>
                                <th class="w-28 border border-slate-300 px-3 py-2 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:nth-child(even)]:bg-slate-50">
                        @forelse ($forms as $i => $form)
                            <tr>
                                <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-700">
                                    {{ ($forms->firstItem() ?? 1) + $i }}
                                </td>
                                <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ $form->location_name }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ $form->creator_name ?? 'System' }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                <td class="border border-slate-200 px-3 py-3">{{ $form->lines_count }}</td>
                                <td class="border border-slate-200 px-3 py-3">
                                    <a
                                        href="{{ route('schedules.form', ['form_id' => $form->form_id, 'approval' => 1]) }}"
                                        class="inline-flex h-9 w-24 items-center justify-center rounded-md bg-slate-100 text-xs font-semibold text-slate-800 transition hover:bg-slate-200"
                                    >
                                        Review Form
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="border border-slate-200 px-3 py-4 text-center text-slate-600">
                                    {{ $showHistory ? 'No submitted forms for this location.' : 'No current/future submitted forms for this location.' }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $forms->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
