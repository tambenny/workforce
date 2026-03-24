<x-app-layout>
    @php
        $timeZone = config('app.timezone', 'America/Toronto');
        $locationName = $user->location?->name ?? 'No location assigned';
        $scheduleWindow = $todaySchedule
            ? $todaySchedule->starts_at->timezone($timeZone)->format('g:i A') . ' - ' . $todaySchedule->ends_at->timezone($timeZone)->format('g:i A')
            : null;
        $openPunchStartedAt = $openPunch?->clock_in_at?->timezone($timeZone);
        $openPunchSchedule = $openPunch?->schedule;
        $clockInReady = $clockInBlockReason === null;
        $clockOutReady = $clockOutBlockReason === null;
        $heroSummary = $openPunch
            ? 'Your shift is currently open. Keep this page available until you are ready to clock out.'
            : ($clockInReady
                ? 'You are clear to start your shift from this browser.'
                : $clockInBlockReason);
        $networkStatusLabel = $isCurrentIpAllowed
            ? ($isNetworkRestricted ? 'Approved network' : 'No network restriction')
            : 'Network mismatch';
        $networkStatusClasses = $isCurrentIpAllowed
            ? ($isNetworkRestricted ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200')
            : 'bg-rose-50 text-rose-700 ring-rose-200';
        $scheduleStatusLabel = ! $user->requires_schedule_for_clock
            ? 'Schedule optional'
            : ($todaySchedule ? 'Approved schedule matched' : 'No active schedule');
        $scheduleStatusClasses = ! $user->requires_schedule_for_clock
            ? 'bg-slate-100 text-slate-700 ring-slate-200'
            : ($todaySchedule ? 'bg-sky-50 text-sky-700 ring-sky-200' : 'bg-amber-50 text-amber-700 ring-amber-200');
        $clockStatusLabel = $openPunch ? 'On the clock' : 'Off the clock';
        $clockStatusClasses = $openPunch
            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
            : 'bg-slate-100 text-slate-700 ring-slate-200';
        $clockInButtonClasses = $clockInReady
            ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-500'
            : 'cursor-not-allowed bg-slate-200 text-slate-400';
        $clockOutButtonClasses = $clockOutReady
            ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20 hover:bg-orange-400'
            : 'cursor-not-allowed bg-slate-200 text-slate-400';
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">Web Clock</h2>
                <p class="mt-1 text-sm text-slate-500">Clock in or out from your assigned location with live shift status.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.5fr_0.9fr]">
                    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(52,211,153,0.18),_transparent_38%),linear-gradient(135deg,_#020617,_#0f172a_60%,_#1e293b)] px-6 py-7 text-white sm:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200">Workday Station</p>
                        <h3 class="mt-3 text-3xl font-semibold tracking-tight">{{ $clockStatusLabel }}</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">{{ $heroSummary }}</p>

                        <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-white/10 px-3 py-1.5 text-slate-100">{{ $locationName }}</span>
                            <span class="rounded-full px-3 py-1.5 ring-1 {{ $networkStatusClasses }}">{{ $networkStatusLabel }}</span>
                            <span class="rounded-full px-3 py-1.5 ring-1 {{ $scheduleStatusClasses }}">{{ $scheduleStatusLabel }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between gap-4 bg-slate-50 px-6 py-7 sm:px-8">
                        <div class="rounded-3xl bg-slate-900 px-5 py-6 text-white shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Local Time</p>
                            <p data-live-now class="mt-4 text-4xl font-semibold tracking-tight">--:--:--</p>
                            <p data-live-date class="mt-2 text-sm text-slate-300">--</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.22em] text-slate-500">{{ $timeZone }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Current State</p>
                                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $openPunch ? 'Clocked In' : 'Standing By' }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $openPunch ? 'Open punch detected.' : 'No open punch detected.' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Location</p>
                                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $user->location?->name ?? 'Unassigned' }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $user->requires_schedule_for_clock ? 'Schedule check enabled.' : 'No schedule match required.' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Network</p>
                                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $isCurrentIpAllowed ? 'Ready' : 'Blocked' }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $currentIp }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    @if (session('status'))
                        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->has('clock'))
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first('clock') }}
                        </div>
                    @endif

                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Action Console</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Clock Actions</h3>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                                Use the buttons below to start or end your web punch. The page shows whether schedule or network rules are blocking the next action.
                            </p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $clockStatusClasses }}">
                            {{ $clockStatusLabel }}
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <form method="POST" action="{{ route('clock.in') }}" class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            @csrf
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Shift Start</p>
                            <h4 class="mt-3 text-2xl font-semibold text-slate-900">Clock In</h4>
                            <p class="mt-2 min-h-[48px] text-sm leading-6 text-slate-500">
                                {{ $clockInReady ? 'Ready to start your shift from this browser.' : $clockInBlockReason }}
                            </p>
                            <button
                                type="submit"
                                @disabled(! $clockInReady)
                                class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-2xl px-5 text-base font-semibold transition {{ $clockInButtonClasses }}"
                            >
                                Clock In
                            </button>
                        </form>

                        <form method="POST" action="{{ route('clock.out') }}" class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            @csrf
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Shift End</p>
                            <h4 class="mt-3 text-2xl font-semibold text-slate-900">Clock Out</h4>
                            <p class="mt-2 min-h-[48px] text-sm leading-6 text-slate-500">
                                {{ $clockOutReady ? 'Ready to close your current punch.' : $clockOutBlockReason }}
                            </p>
                            <button
                                type="submit"
                                @disabled(! $clockOutReady)
                                class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-2xl px-5 text-base font-semibold transition {{ $clockOutButtonClasses }}"
                            >
                                Clock Out
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Current IP</p>
                            <p class="mt-3 text-lg font-semibold text-slate-900">{{ $currentIp }}</p>
                            <p class="mt-2 text-sm text-slate-500">Browser network currently detected.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Allowed IP</p>
                            <p class="mt-3 text-lg font-semibold text-slate-900">{{ $allowedIp ?? 'Not configured' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $isNetworkRestricted ? 'Store network restriction is active.' : 'This location has no network lock.' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Policy</p>
                            <p class="mt-3 text-lg font-semibold text-slate-900">{{ $user->requires_schedule_for_clock ? 'Schedule required' : 'Flexible access' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $user->requires_schedule_for_clock ? 'Approved schedule must be active to clock in.' : 'You can clock without schedule matching.' }}</p>
                        </div>
                    </div>
                </section>

                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Schedule Window</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">
                                    {{ $todaySchedule ? 'Approved schedule found' : ($user->requires_schedule_for_clock ? 'No active schedule' : 'Schedule check not required') }}
                                </h3>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $scheduleStatusClasses }}">
                                {{ $scheduleStatusLabel }}
                            </span>
                        </div>

                        @if ($todaySchedule)
                            <div class="mt-5 rounded-2xl bg-sky-50 p-4">
                                <p class="text-3xl font-semibold text-slate-900">{{ $scheduleWindow }}</p>
                                <p class="mt-2 text-sm text-slate-600">{{ $todaySchedule->shift_date->format('l, F j') }}</p>
                                <p class="mt-3 text-sm text-slate-600">{{ $todaySchedule->notes ?: 'No schedule notes for this shift.' }}</p>
                            </div>
                        @elseif ($user->requires_schedule_for_clock)
                            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                No approved schedule is active right now, so clock in is blocked until a valid shift window is available.
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                This account can clock in and out without matching an approved schedule.
                            </div>
                        @endif
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Live Punch</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $openPunch ? 'Punch currently open' : 'No open punch' }}</h3>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $clockStatusClasses }}">
                                {{ $clockStatusLabel }}
                            </span>
                        </div>

                        @if ($openPunch)
                            <div class="mt-5 space-y-4">
                                <div class="rounded-2xl bg-emerald-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Started</p>
                                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $openPunchStartedAt?->format('M j, g:i A') }}</p>
                                    <p class="mt-2 text-sm text-slate-600">Your punch remains active until you clock out.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Live Duration</p>
                                        <p data-open-duration data-open-start="{{ $openPunch->clock_in_at?->copy()->utc()->toIso8601String() }}" class="mt-3 text-2xl font-semibold text-slate-900">--:--:--</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Source</p>
                                        <p class="mt-3 text-2xl font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline($openPunch->source ?? 'web') }}</p>
                                    </div>
                                </div>
                                @if ($openPunchSchedule)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                        Scheduled window for this punch:
                                        <strong class="text-slate-900">
                                            {{ $openPunchSchedule->starts_at->timezone($timeZone)->format('g:i A') }} - {{ $openPunchSchedule->ends_at->timezone($timeZone)->format('g:i A') }}
                                        </strong>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                No punch is currently open. When you start work, use Clock In to begin the record for this shift.
                            </div>
                        @endif
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">How It Works</p>
                        <div class="mt-5 grid gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 1</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Check your network and schedule</p>
                                <p class="mt-2 text-sm text-slate-600">The page shows whether the current browser can start or end a punch right now.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 2</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Use the action buttons</p>
                                <p class="mt-2 text-sm text-slate-600">Buttons are enabled only when the next action is valid for your current state.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 3</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Watch the live punch panel</p>
                                <p class="mt-2 text-sm text-slate-600">If you are clocked in, the page keeps showing the start time and live duration until clock out.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timeZone = @js($timeZone);
            const liveNow = document.querySelector('[data-live-now]');
            const liveDate = document.querySelector('[data-live-date]');
            const openDuration = document.querySelector('[data-open-duration]');
            const openStart = openDuration?.dataset.openStart ? new Date(openDuration.dataset.openStart) : null;

            const pad = (value) => String(value).padStart(2, '0');

            const updateClock = () => {
                const now = new Date();

                if (liveNow) {
                    liveNow.textContent = new Intl.DateTimeFormat([], {
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                        timeZone,
                    }).format(now);
                }

                if (liveDate) {
                    liveDate.textContent = new Intl.DateTimeFormat([], {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                        timeZone,
                    }).format(now);
                }

                if (openDuration && openStart instanceof Date && ! Number.isNaN(openStart.getTime())) {
                    const diffSeconds = Math.max(0, Math.floor((Date.now() - openStart.getTime()) / 1000));
                    const hours = Math.floor(diffSeconds / 3600);
                    const minutes = Math.floor((diffSeconds % 3600) / 60);
                    const seconds = diffSeconds % 60;

                    openDuration.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                }
            };

            updateClock();
            window.setInterval(updateClock, 1000);
        });
    </script>
</x-app-layout>
