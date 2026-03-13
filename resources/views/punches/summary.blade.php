<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff Time Summary</h2>
            <a href="{{ route('punches.index') }}" class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Back to Punches
            </a>
        </div>
    </x-slot>

    @php
        $formatSeconds = function (int $seconds): string {
            $negative = $seconds < 0;
            $seconds = abs($seconds);
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);

            return ($negative ? '-' : '') . sprintf('%02d:%02d', $hours, $minutes);
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route('punches.summary') }}" class="mb-5 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-1 rounded border-gray-300 text-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-1 rounded border-gray-300 text-sm">
                    </div>
                    <div>
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 rounded border-gray-300 text-sm">
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
                        <select id="user_id" name="user_id" class="mt-1 rounded border-gray-300 text-sm">
                            <option value="">All staff</option>
                            @foreach ($staff as $staffOption)
                                <option value="{{ $staffOption->id }}" @selected($selectedUserId === $staffOption->id)>
                                    {{ $staffOption->name }} ({{ $staffOption->staff_id ?: 'No Staff ID' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Filter</button>
                </form>

                <table class="min-w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="border border-slate-200 px-3 py-2 text-left">Staff</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Staff ID</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Total Scheduled</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Total Punched</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Difference</th>
                            <th class="border border-slate-200 px-3 py-2 text-left">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $punchedStyle = $row['punched_seconds'] > $row['scheduled_seconds']
                                    ? 'color:#991b1b;'
                                    : 'color:#166534;';
                                $differenceStyle = $row['variance_seconds'] === 0
                                    ? 'color:#475569;'
                                    : ($row['variance_seconds'] > 0 ? 'color:#166534;' : 'color:#991b1b;');
                            @endphp
                            <tr>
                                <td class="border border-slate-200 px-3 py-2">{{ $row['name'] }}</td>
                                <td class="border border-slate-200 px-3 py-2">{{ $row['staff_id'] ?: '-' }}</td>
                                <td class="border border-slate-200 px-3 py-2 font-medium">{{ $formatSeconds($row['scheduled_seconds']) }}</td>
                                <td class="border border-slate-200 px-3 py-2 font-medium" style="{{ $punchedStyle }}">{{ $formatSeconds($row['punched_seconds']) }}</td>
                                <td class="border border-slate-200 px-3 py-2 font-semibold" style="{{ $differenceStyle }}">
                                    {{ $formatSeconds($row['variance_seconds']) }}
                                </td>
                                <td class="border border-slate-200 px-3 py-2">
                                    <a
                                        href="{{ route('punches.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'user_id' => $row['id']]) }}"
                                        class="inline-flex rounded bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border border-slate-200 px-3 py-4 text-center text-slate-500">
                                    No schedule or punch totals found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
