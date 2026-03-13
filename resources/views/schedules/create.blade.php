<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Schedule</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('schedules.store') }}" class="space-y-4">
                    @csrf
                    @php
                        $selectedShiftDate = old('shift_date');
                        $selectedWeekday = $selectedShiftDate ? \Illuminate\Support\Carbon::parse($selectedShiftDate)->format('l') : null;
                    @endphp

                    <div>
                        <x-input-label for="location_id" value="Location" />
                        <select id="location_id" name="location_id" class="mt-1 block w-full rounded border-gray-300" onchange="window.location='{{ route('schedules.create') }}?location_id=' + this.value">
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected((int) $selectedLocationId === (int) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="shift_date" value="Date" />
                        <input id="shift_date" type="date" name="shift_date" value="{{ old('shift_date') }}" class="mt-1 block w-full rounded border-gray-300">
                        <p id="shift_weekday" class="mt-1 text-sm text-slate-600">
                            Weekday: <strong>{{ $selectedWeekday ?? '-' }}</strong>
                        </p>
                        <x-input-error :messages="$errors->get('shift_date')" class="mt-2" />
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="py-2 pr-4">Use</th>
                                    <th class="py-2 pr-4">Roster</th>
                                    <th class="py-2 pr-4">Role</th>
                                    <th class="py-2 pr-4">Position</th>
                                    <th class="py-2 pr-4">Clock In</th>
                                    <th class="py-2">Clock Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($staff as $person)
                                    <tr class="border-b align-top">
                                        <td class="py-2 pr-4">
                                            <input
                                                type="checkbox"
                                                name="roster[{{ $person->id }}][selected]"
                                                value="1"
                                                @checked(old("roster.{$person->id}.selected"))
                                            >
                                        </td>
                                        <td class="py-2 pr-4">{{ $person->name }}</td>
                                        <td class="py-2 pr-4">{{ ucfirst($person->role) }}</td>
                                        <td class="py-2 pr-4">{{ $person->position?->name ?? 'No Position' }}</td>
                                        <td class="py-2 pr-4">
                                            <input
                                                type="time"
                                                name="roster[{{ $person->id }}][clock_in]"
                                                value="{{ old("roster.{$person->id}.clock_in") }}"
                                                class="block w-36 rounded border-gray-300"
                                            >
                                        </td>
                                        <td class="py-2">
                                            <input
                                                type="time"
                                                name="roster[{{ $person->id }}][clock_out]"
                                                value="{{ old("roster.{$person->id}.clock_out") }}"
                                                class="block w-36 rounded border-gray-300"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-input-error :messages="$errors->get('roster')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" class="mt-1 block w-full rounded border-gray-300">{{ old('notes') }}</textarea>
                    </div>

                    <x-primary-button>Submit For Approval</x-primary-button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const dateInput = document.getElementById('shift_date');
            const weekdayEl = document.getElementById('shift_weekday');
            if (!dateInput || !weekdayEl) return;

            const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            const renderWeekday = () => {
                if (!dateInput.value) {
                    weekdayEl.innerHTML = 'Weekday: <strong>-</strong>';
                    return;
                }

                const date = new Date(dateInput.value + 'T00:00:00');
                weekdayEl.innerHTML = 'Weekday: <strong>' + weekdays[date.getDay()] + '</strong>';
            };

            dateInput.addEventListener('change', renderWeekday);
            renderWeekday();

        })();
    </script>
</x-app-layout>
