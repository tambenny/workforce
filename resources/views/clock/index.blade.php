<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Web Clock</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif
                @if ($errors->has('clock'))
                    <p class="mb-4 text-sm text-red-700">{{ $errors->first('clock') }}</p>
                @endif

                <p class="text-sm text-gray-600">Current IP: <strong>{{ request()->ip() }}</strong></p>
                <p class="text-sm text-gray-600">Allowed Location IP: <strong>{{ $user->location?->allowed_ip ?? 'Not configured' }}</strong></p>

                @if ($user->requires_schedule_for_clock && $todaySchedule)
                    <div class="mt-4 rounded border p-3">
                        <p><strong>Current Schedule:</strong> {{ $todaySchedule->starts_at }} to {{ $todaySchedule->ends_at }}</p>
                        <p class="text-sm text-slate-600">
                            Weekday: <strong>{{ $todaySchedule->shift_date->format('l') }}</strong>
                        </p>
                    </div>
                @elseif ($user->requires_schedule_for_clock)
                    <p class="mt-4 text-amber-700">No active approved schedule for right now.</p>
                @else
                    <p class="mt-4 text-slate-600">This staff account does not require schedule matching for clock in/out.</p>
                @endif

                <div class="mt-6 flex gap-3">
                    <form method="POST" action="{{ route('clock.in') }}">
                        @csrf
                        <button class="rounded bg-green-600 px-4 py-2 font-semibold text-white">Clock In</button>
                    </form>
                    <form method="POST" action="{{ route('clock.out') }}">
                        @csrf
                        <button class="rounded bg-orange-600 px-4 py-2 font-semibold text-white">Clock Out</button>
                    </form>
                </div>

                <p class="mt-4 text-sm text-gray-700">
                    Open punch:
                    <strong>{{ $openPunch ? 'Yes (started ' . $openPunch->clock_in_at . ')' : 'No' }}</strong>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
