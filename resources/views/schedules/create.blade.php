<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">{{ __('Schedule Planning') }}</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">{{ __('Create Schedule') }}</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        {{ __('Pick a location, select a date, and submit roster lines for approval.') }}
                    </p>
                </div>

                <a
                    href="{{ route('schedules.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    {{ __('Back to Schedules') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $requestedShiftDate = old('shift_date', request()->query('shift_date'));
        $selectedShiftDate = is_string($requestedShiftDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedShiftDate)
            ? $requestedShiftDate
            : null;
        $selectedWeekday = $selectedShiftDate
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $selectedShiftDate)->translatedFormat('l')
            : null;
        $selectedLocation = $locations->firstWhere('id', (int) $selectedLocationId);
        $weekdayLabel = __('Weekday');
        $emptyWeekday = '-';
        $weekdays = [
            __('Sunday'),
            __('Monday'),
            __('Tuesday'),
            __('Wednesday'),
            __('Thursday'),
            __('Friday'),
            __('Saturday'),
        ];
        $scheduleText = [
            'weekday' => $weekdayLabel,
            'emptyWeekday' => $emptyWeekday,
            'pickDatePrompt' => __('Pick a date to show the weekday.'),
            'weekdays' => $weekdays,
            'use' => __('Use'),
            'clockIn' => __('Clock In'),
            'clockOut' => __('Clock Out'),
            'remove' => __('Remove'),
        ];
        $draftLinesForPerson = function ($personId): array {
            $lineDrafts = old("roster.{$personId}.lines");

            if (is_array($lineDrafts) && count($lineDrafts) > 0) {
                return collect($lineDrafts)
                    ->map(fn ($line) => [
                        'selected' => (bool) data_get($line, 'selected', false),
                        'clock_in' => (string) data_get($line, 'clock_in', ''),
                        'clock_out' => (string) data_get($line, 'clock_out', ''),
                    ])
                    ->values()
                    ->all();
            }

            if (
                old("roster.{$personId}.selected") !== null
                || old("roster.{$personId}.clock_in") !== null
                || old("roster.{$personId}.clock_out") !== null
            ) {
                return [[
                    'selected' => (bool) old("roster.{$personId}.selected", false),
                    'clock_in' => (string) old("roster.{$personId}.clock_in", ''),
                    'clock_out' => (string) old("roster.{$personId}.clock_out", ''),
                ]];
            }

            return [[
                'selected' => false,
                'clock_in' => '',
                'clock_out' => '',
            ]];
        };
    @endphp

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ __('Location') }}</p>
                    <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $selectedLocation?->name ?? __('Select location') }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('The roster below reflects the selected site.') }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ __('Shift Date') }}</p>
                    <p id="shift_date_summary_value" class="mt-4 text-2xl font-semibold text-slate-900">{{ $selectedShiftDate ?: $emptyWeekday }}</p>
                    <p id="shift_date_summary_detail" class="mt-2 text-sm text-slate-600">{{ $selectedWeekday ?: __('Pick a date to show the weekday.') }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ __('Roster Rows') }}</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $staff->count() }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Available staff assigned to this location.') }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-900 px-5 py-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-200">{{ __('Approval Flow') }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        {{ __('Select each roster line you need, then submit the schedule form into the approval queue. Multiple same-day shifts are allowed as long as the times do not overlap.') }}
                    </p>
                </div>
            </div>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">{{ __('Schedule Form') }}</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('Date, staff, and shift times') }}</h3>
                </div>

                <form method="POST" action="{{ route('schedules.store') }}" class="space-y-6 px-6 py-6">
                    @csrf

                    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.58fr)_minmax(0,0.42fr)]">
                        <div>
                            <x-input-label for="location_id" :value="__('Location')" />
                            <select id="location_id" name="location_id" class="mt-1 block w-full rounded-2xl border-gray-300 text-sm shadow-sm">
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="shift_date" :value="__('Date')" />
                            <input id="shift_date" type="date" name="shift_date" value="{{ $selectedShiftDate }}" class="mt-1 block w-full rounded-2xl border-gray-300 text-sm shadow-sm">
                            <p id="shift_weekday" class="mt-2 text-sm text-slate-600">
                                {{ $weekdayLabel }}: <strong>{{ $selectedWeekday ?? $emptyWeekday }}</strong>
                            </p>
                            <x-input-error :messages="$errors->get('shift_date')" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('If clock out is earlier than clock in, the shift will be saved as ending on the next day.') }}
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-separate border-spacing-0 text-sm text-slate-700">
                                <thead>
                                    <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                        <th class="rounded-l-2xl border-b border-slate-700 px-4 py-3">{{ __('Roster') }}</th>
                                        <th class="border-b border-slate-700 px-4 py-3">{{ __('Role') }}</th>
                                        <th class="border-b border-slate-700 px-4 py-3">{{ __('Position') }}</th>
                                        <th class="rounded-r-2xl border-b border-slate-700 px-4 py-3">{{ __('Shift Lines') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($staff as $person)
                                        @php($draftLines = $draftLinesForPerson($person->id))
                                        <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }}">
                                            <td class="border-b border-slate-200 px-4 py-3 align-top">
                                                <p class="font-semibold text-slate-900">{{ $person->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $person->staff_id ?: __('No staff ID') }}</p>
                                            </td>
                                            <td class="border-b border-slate-200 px-4 py-3 align-top text-slate-600">{{ __(ucfirst($person->role)) }}</td>
                                            <td class="border-b border-slate-200 px-4 py-3 align-top text-slate-600">{{ $person->position?->name ?? __('No Position') }}</td>
                                            <td class="border-b border-slate-200 px-4 py-3 align-top">
                                                <div
                                                    class="space-y-3"
                                                    data-staff-shift-editor
                                                    data-user-id="{{ $person->id }}"
                                                >
                                                    <div data-shift-lines data-next-index="{{ count($draftLines) }}" class="space-y-3">
                                                        @foreach ($draftLines as $lineIndex => $draftLine)
                                                            <div data-shift-line class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                                                                <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                                                                    <label class="inline-flex min-w-28 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="roster[{{ $person->id }}][lines][{{ $lineIndex }}][selected]"
                                                                            value="1"
                                                                            @checked($draftLine['selected'])
                                                                            class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500"
                                                                        >
                                                                        <span>{{ __('Use') }}</span>
                                                                    </label>

                                                                    <div class="grid flex-1 gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                                                        <div>
                                                                            <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('Clock In') }}</label>
                                                                            <input
                                                                                type="time"
                                                                                name="roster[{{ $person->id }}][lines][{{ $lineIndex }}][clock_in]"
                                                                                value="{{ $draftLine['clock_in'] }}"
                                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm"
                                                                            >
                                                                        </div>

                                                                        <div>
                                                                            <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ __('Clock Out') }}</label>
                                                                            <input
                                                                                type="time"
                                                                                name="roster[{{ $person->id }}][lines][{{ $lineIndex }}][clock_out]"
                                                                                value="{{ $draftLine['clock_out'] }}"
                                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm"
                                                                            >
                                                                        </div>

                                                                        <button
                                                                            type="button"
                                                                            data-remove-shift
                                                                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                                                                        >
                                                                            {{ __('Remove') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <button
                                                        type="button"
                                                        data-add-shift
                                                        class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-sky-700 transition hover:bg-sky-100"
                                                    >
                                                        {{ __('Add Shift') }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No staff are assigned to this location.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-input-error :messages="$errors->get('roster')" class="px-4 pb-4" />
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-2xl border-gray-300 text-sm shadow-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a
                            href="{{ route('schedules.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Submit For Approval') }}</x-primary-button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        (function () {
            const locationInput = document.getElementById('location_id');
            const dateInput = document.getElementById('shift_date');
            const weekdayEl = document.getElementById('shift_weekday');
            const summaryDateEl = document.getElementById('shift_date_summary_value');
            const summaryDetailEl = document.getElementById('shift_date_summary_detail');
            const scheduleText = @json($scheduleText);
            const createScheduleUrl = @json(route('schedules.create'));

            const renderShiftDate = () => {
                if (!dateInput) {
                    return;
                }

                if (!dateInput.value) {
                    if (weekdayEl) {
                        weekdayEl.innerHTML = `${scheduleText.weekday}: <strong>${scheduleText.emptyWeekday}</strong>`;
                    }

                    if (summaryDateEl) {
                        summaryDateEl.textContent = scheduleText.emptyWeekday;
                    }

                    if (summaryDetailEl) {
                        summaryDetailEl.textContent = scheduleText.pickDatePrompt;
                    }

                    return;
                }

                const [year, month, day] = dateInput.value.split('-').map(Number);
                const date = new Date(year, month - 1, day);
                const weekday = scheduleText.weekdays[date.getDay()] ?? scheduleText.emptyWeekday;

                if (weekdayEl) {
                    weekdayEl.innerHTML = `${scheduleText.weekday}: <strong>${weekday}</strong>`;
                }

                if (summaryDateEl) {
                    summaryDateEl.textContent = dateInput.value;
                }

                if (summaryDetailEl) {
                    summaryDetailEl.textContent = weekday;
                }
            };

            if (locationInput) {
                locationInput.addEventListener('change', () => {
                    const url = new URL(createScheduleUrl, window.location.origin);

                    if (locationInput.value) {
                        url.searchParams.set('location_id', locationInput.value);
                    }

                    if (dateInput?.value) {
                        url.searchParams.set('shift_date', dateInput.value);
                    }

                    window.location.assign(url.toString());
                });
            }

            if (dateInput) {
                dateInput.addEventListener('change', renderShiftDate);
                dateInput.addEventListener('input', renderShiftDate);
                renderShiftDate();
            }

            const createShiftLine = (userId, lineIndex) => {
                const wrapper = document.createElement('div');
                wrapper.setAttribute('data-shift-line', '');
                wrapper.className = 'rounded-2xl border border-slate-200 bg-slate-50/80 p-3';
                wrapper.innerHTML = `
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                        <label class="inline-flex min-w-28 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                            <input
                                type="checkbox"
                                name="roster[${userId}][lines][${lineIndex}][selected]"
                                value="1"
                                class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500"
                            >
                            <span>${scheduleText.use}</span>
                        </label>

                        <div class="grid flex-1 gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">${scheduleText.clockIn}</label>
                                <input
                                    type="time"
                                    name="roster[${userId}][lines][${lineIndex}][clock_in]"
                                    class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm"
                                >
                            </div>

                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">${scheduleText.clockOut}</label>
                                <input
                                    type="time"
                                    name="roster[${userId}][lines][${lineIndex}][clock_out]"
                                    class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm"
                                >
                            </div>

                            <button
                                type="button"
                                data-remove-shift
                                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                            >
                                ${scheduleText.remove}
                            </button>
                        </div>
                    </div>
                `;

                return wrapper;
            };

            document.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-shift]');
                if (addButton) {
                    const editor = addButton.closest('[data-staff-shift-editor]');
                    const linesContainer = editor?.querySelector('[data-shift-lines]');
                    if (!editor || !linesContainer) {
                        return;
                    }

                    const userId = editor.getAttribute('data-user-id');
                    const nextIndex = Number(linesContainer.getAttribute('data-next-index') || '0');
                    linesContainer.appendChild(createShiftLine(userId, nextIndex));
                    linesContainer.setAttribute('data-next-index', String(nextIndex + 1));
                    return;
                }

                const removeButton = event.target.closest('[data-remove-shift]');
                if (!removeButton) {
                    return;
                }

                const shiftLine = removeButton.closest('[data-shift-line]');
                const linesContainer = shiftLine?.parentElement;
                if (!shiftLine || !linesContainer) {
                    return;
                }

                const lineCount = linesContainer.querySelectorAll('[data-shift-line]').length;
                if (lineCount === 1) {
                    const checkbox = shiftLine.querySelector('input[type="checkbox"]');
                    const timeInputs = shiftLine.querySelectorAll('input[type="time"]');
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                    timeInputs.forEach((input) => {
                        input.value = '';
                    });
                    return;
                }

                shiftLine.remove();
            });
        })();
    </script>
</x-app-layout>
