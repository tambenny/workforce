<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Schedule Approvals</h2>
                </div>

                <a
                    href="{{ route('schedules.approvals', array_filter(['location_id' => $selectedLocationId, 'history' => $showHistory ? null : 1])) }}"
                    class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-semibold transition {{ $showHistory ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' }}"
                >
                    {{ $showHistory ? 'Current Only' : 'All Submitted' }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Forms</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $forms->total() }}</p>
                    <p class="mt-2 text-sm text-slate-600">Submitted forms available in the current approval mode.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Location</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $locations->firstWhere('id', $selectedLocationId)?->name ?? 'All visible locations' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Approval queue filtered to the selected site.</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Mode</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $showHistory ? 'All Submitted' : 'Current Only' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Current/future submitted forms or the full submitted list.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('schedules.approvals') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                    <div class="min-w-[16rem]">
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 block w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Load Forms</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                @if (session('status'))
                    <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->has('reason'))
                    <div class="mx-6 mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        {{ $errors->first('reason') }}
                    </div>
                @endif

                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[5%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">#</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Shift Date</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[13%] border-b border-slate-700 px-3 py-3.5">Submitted By</th>
                                <th class="w-[13%] border-b border-slate-700 px-3 py-3.5">Submitted At</th>
                                <th class="w-[14%] border-b border-slate-700 px-3 py-3.5">Range</th>
                                <th class="w-[8%] border-b border-slate-700 px-3 py-3.5">Lines</th>
                                <th class="w-[15%] border-b border-slate-700 px-3 py-3.5">Mode</th>
                                <th class="w-[12%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($forms as $i => $form)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 font-semibold text-slate-700">{{ ($forms->firstItem() ?? 1) + $i }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('Y-m-d') }}<br><span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($form->shift_date)->format('l') }}</span></td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->location_name }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->creator_name ?? 'System' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ \Illuminate\Support\Carbon::parse($form->starts_at_min)->format('H:i') }} to {{ \Illuminate\Support\Carbon::parse($form->ends_at_max)->format('H:i') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">{{ $form->lines_count }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5">
                                        <span class="font-semibold">{{ $showHistory ? 'All Submitted' : 'Current Queue' }}</span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right">
                                        <a
                                            href="{{ route('schedules.form', ['form_id' => $form->form_id, 'approval' => 1]) }}"
                                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                                        >
                                            Review Form
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        {{ $showHistory ? 'No submitted forms for this location.' : 'No current or future submitted forms for this location.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">{{ $forms->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
