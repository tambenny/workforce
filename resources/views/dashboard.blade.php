<x-app-layout>
    @php
        $user = auth()->user();
        $dashboardLocation = $dashboardLocation ?? $user->location;
        $roleLabel = match ($role) {
            'admin' => 'System Admin',
            'hr' => 'HR',
            default => \Illuminate\Support\Str::headline($role),
        };
        $timeZone = config('app.timezone', 'America/Toronto');
        $canViewDashboard = $user->canViewDashboard();
        $canUseWebClock = $user->canUseWebClock();
        $canViewPunchLog = $user->canViewPunchLog();
        $canViewPunchSummary = $user->canViewPunchSummary();
        $canViewSchedules = $user->canViewSchedules();
        $canViewScheduleSummary = $user->canViewScheduleSummary();
        $canCreateSchedules = $user->hasSchedulePermission('create');
        $canApproveSchedules = $user->hasSchedulePermission('approve');
        $canSeeScheduleHub = $canViewSchedules || $canViewScheduleSummary || $canCreateSchedules || $canApproveSchedules;
        $canViewCurrentStaff = $user->canViewCurrentStaffReport();
        $canViewPunchPhotos = $user->canViewPunchPhotos();
        $canManagePunches = $canViewCurrentStaff;
        $canManageHr = in_array($role, ['admin', 'hr'], true);
        $canManageLocations = $role === 'admin';
        $canSeeWarnings = $user->canViewSecurityWarnings();
        $displayName = trim((string) $user->name) !== '' ? $user->name : 'Team member';
        $heroSummary = match ($role) {
            'admin' => 'Monitor live staffing, approvals, and system activity from one place.',
            'manager' => 'Track floor coverage, schedule actions, and attendance for your team.',
            'hr' => 'Review staffing records, attendance detail, and schedule activity in one workspace.',
            default => 'Clock in, review your punches, and stay on top of your workday.',
        };

        $stats = isset($openPunches)
            ? collect([
                $canManagePunches ? [
                    'eyebrow' => 'Live attendance',
                    'value' => number_format($openPunches),
                    'label' => 'Open punches',
                    'note' => 'Staff currently clocked in.',
                    'card' => 'bg-slate-950 text-white ring-slate-900/80',
                    'valueClass' => 'text-white',
                    'noteClass' => 'text-slate-300',
                ] : null,
                $canManagePunches ? [
                    'eyebrow' => 'Coverage',
                    'value' => number_format($openLocations ?? 0),
                    'label' => 'Active locations',
                    'note' => 'Sites with at least one open punch.',
                    'card' => 'bg-white text-slate-900 ring-slate-200',
                    'valueClass' => 'text-slate-900',
                    'noteClass' => 'text-slate-500',
                ] : null,
                $canSeeScheduleHub ? [
                    'eyebrow' => 'Scheduling',
                    'value' => number_format($pendingSchedules ?? 0),
                    'label' => 'Pending approvals',
                    'note' => 'Submitted schedules awaiting review.',
                    'card' => 'bg-amber-50 text-slate-900 ring-amber-200/80',
                    'valueClass' => 'text-amber-900',
                    'noteClass' => 'text-amber-800/80',
                ] : null,
                $canSeeWarnings ? [
                    'eyebrow' => 'Alerts',
                    'value' => number_format($unresolvedWarnings ?? 0),
                    'label' => 'Security warnings',
                    'note' => 'Open exceptions that still need attention.',
                    'card' => 'bg-rose-50 text-slate-900 ring-rose-200/80',
                    'valueClass' => 'text-rose-900',
                    'noteClass' => 'text-rose-800/80',
                ] : null,
            ])->filter()->values()->all()
            : [
                [
                    'eyebrow' => 'Today',
                    'value' => $myOpenPunch ? 'On the clock' : 'Off the clock',
                    'label' => 'Current status',
                    'note' => $myOpenPunch
                        ? 'Clocked in at ' . $myOpenPunch->clock_in_at->timezone($timeZone)->format('M j, g:i A')
                        : 'No open punch right now.',
                    'card' => 'bg-emerald-50 text-slate-900 ring-emerald-200/80',
                    'valueClass' => 'text-emerald-900',
                    'noteClass' => 'text-emerald-800/80',
                ],
                [
                    'eyebrow' => 'Location',
                    'value' => $dashboardLocation?->name ?? 'Not assigned',
                    'label' => 'Assigned site',
                    'note' => $dashboardLocation
                        ? 'Use web clock or kiosk for this location.'
                        : 'Ask an administrator to assign a location.',
                    'card' => 'bg-white text-slate-900 ring-slate-200',
                    'valueClass' => 'text-slate-900',
                    'noteClass' => 'text-slate-500',
                ],
                [
                    'eyebrow' => 'Access',
                    'value' => $roleLabel,
                    'label' => 'Current role',
                    'note' => 'Your tools and reports are based on this access level.',
                    'card' => 'bg-sky-50 text-slate-900 ring-sky-200/80',
                    'valueClass' => 'text-sky-900',
                    'noteClass' => 'text-sky-800/80',
                ],
            ];

        $workdayActions = [];

        if ($canUseWebClock) {
            $workdayActions[] = [
                'label' => 'Web Clock',
                'description' => 'Clock in or out from the browser.',
                'href' => route('clock.index'),
                'accent' => 'bg-slate-900',
            ];
        }

        if ($canViewPunchLog) {
            $workdayActions[] = [
                'label' => $user->canViewOwnPunches() ? 'My Punches' : 'Punch Log',
                'description' => 'Review punch history and attendance records.',
                'href' => route('punches.index'),
                'accent' => 'bg-sky-600',
            ];
        }

        if ($canViewPunchSummary) {
            $workdayActions[] = [
                'label' => 'Punch Summary',
                'description' => 'See hours worked versus scheduled time.',
                'href' => route('punches.summary'),
                'accent' => 'bg-emerald-600',
            ];
        }

        $actionSections = [];

        if ($workdayActions !== []) {
            $actionSections[] = [
                'title' => 'Workday',
                'description' => 'Clock actions and your own attendance records.',
                'badge' => 'Daily tools',
                'actions' => $workdayActions,
            ];
        }

        if ($canSeeScheduleHub) {
            $scheduleActions = [];

            if ($canViewSchedules) {
                $scheduleActions[] = [
                    'label' => 'All Schedules',
                    'description' => 'Review scheduled shifts and updates.',
                    'href' => route('schedules.index'),
                    'accent' => 'bg-blue-600',
                ];
            }

            if ($canViewScheduleSummary) {
                $scheduleActions[] = [
                    'label' => 'Schedule Summary',
                    'description' => 'Review total scheduled hours for a location and date range.',
                    'href' => route('schedules.summary'),
                    'accent' => 'bg-violet-600',
                ];
            }

            if ($canCreateSchedules) {
                $scheduleActions[] = [
                    'label' => 'Create Schedule',
                    'description' => 'Build or edit shifts for upcoming days.',
                    'href' => route('schedules.create'),
                    'accent' => 'bg-cyan-600',
                ];
            }

            if ($canApproveSchedules) {
                $scheduleActions[] = [
                    'label' => 'Approvals',
                    'description' => 'Process submitted schedules awaiting approval.',
                    'href' => route('schedules.approvals'),
                    'accent' => 'bg-amber-500',
                ];
            }

            $actionSections[] = [
                'title' => 'Schedules',
                'description' => 'Shift planning, review, and approval workflow.',
                'badge' => $canApproveSchedules ? 'Approval queue' : 'Schedule hub',
                'actions' => $scheduleActions,
            ];
        }

        if ($canManagePunches || $canSeeWarnings) {
            $managementActions = [];

            if ($canManagePunches) {
                $managementActions[] = [
                    'label' => 'Current Staff',
                    'description' => 'See who is on the clock by location right now.',
                    'href' => route('punches.current'),
                    'accent' => 'bg-emerald-600',
                ];
            }

            if ($canViewPunchPhotos) {
                $managementActions[] = [
                    'label' => 'Punch Photos',
                    'description' => 'Review photo records attached to punches.',
                    'href' => route('punches.photos'),
                    'accent' => 'bg-fuchsia-600',
                ];
            }

            if ($canSeeWarnings) {
                $managementActions[] = [
                    'label' => 'Security Warnings',
                    'description' => 'Resolve open attendance and security exceptions.',
                    'href' => route('reports.security-warnings'),
                    'accent' => 'bg-rose-600',
                ];
            }

            if ($managementActions !== []) {
                $actionSections[] = [
                    'title' => 'Management',
                    'description' => 'Operational views for staffing, exceptions, and evidence.',
                    'badge' => auth()->user()?->role === 'manager' ? 'Location scoped' : 'Oversight',
                    'actions' => $managementActions,
                ];
            }
        }

        if ($canManageHr) {
            $hrActions = [];

            $hrActions[] = [
                'label' => 'Staff Management',
                'description' => 'Add staff, edit profiles, and reset PIN access.',
                'href' => route('staff.index'),
                'accent' => 'bg-indigo-600',
            ];
            $hrActions[] = [
                'label' => 'Positions',
                'description' => 'Maintain job titles used across the workforce.',
                'href' => route('positions.index'),
                'accent' => 'bg-orange-500',
            ];

            $actionSections[] = [
                'title' => 'HR',
                'description' => 'People records, role setup, and workforce structure.',
                'badge' => 'People ops',
                'actions' => $hrActions,
            ];
        }

        if ($canManageLocations) {
            $adminActions = [
                [
                    'label' => 'Locations',
                    'description' => 'Manage sites, kiosk tokens, and location settings.',
                    'href' => route('locations.index'),
                    'accent' => 'bg-teal-600',
                ],
            ];

            if ($adminActions !== []) {
                $actionSections[] = [
                    'title' => 'Administration',
                    'description' => 'Core site configuration for the platform.',
                    'badge' => 'Configuration',
                    'actions' => $adminActions,
                ];
            }
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-500">Workforce Clock</p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight text-slate-900">Dashboard</h2>
                <p class="mt-1 text-sm text-slate-500">A cleaner view of live staffing, schedules, and daily actions.</p>
            </div>
            <div class="inline-flex items-center gap-2 self-start rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <span>{{ $dashboardLocation?->name ?? 'Company-wide view' }}</span>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_46%,#f8fafc_100%)] py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-8 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 sm:px-8 lg:grid lg:grid-cols-[1.35fr_0.85fr] lg:gap-8">
                <div class="absolute -right-8 top-0 h-48 w-48 rounded-full bg-cyan-400/15 blur-3xl"></div>
                <div class="absolute left-1/3 top-1/2 h-40 w-40 rounded-full bg-amber-300/10 blur-3xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-200/70">Operations overview</p>
                    <h1 class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                        {{ $displayName }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
                        {{ $heroSummary }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <div class="rounded-full bg-white/10 px-3 py-2 text-sm font-medium text-slate-100 ring-1 ring-white/10">
                            {{ $roleLabel }}
                        </div>
                        <div class="rounded-full bg-white/10 px-3 py-2 text-sm font-medium text-slate-100 ring-1 ring-white/10">
                            {{ $dashboardLocation?->name ?? 'All locations' }}
                        </div>
                        <div class="rounded-full bg-white/10 px-3 py-2 text-sm font-medium text-slate-100 ring-1 ring-white/10">
                            {{ $canManagePunches ? 'Operations tools enabled' : 'Employee tools enabled' }}
                        </div>
                    </div>
                </div>

                <div class="relative mt-8 lg:mt-0">
                    <div class="h-full rounded-[1.75rem] bg-white/10 p-5 ring-1 ring-white/10 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-300">Local time</p>
                        <div class="mt-4">
                            <p id="dashboard-local-time" class="text-4xl font-semibold tracking-tight text-white">
                                {{ now()->timezone($timeZone)->format('g:i:s A') }}
                            </p>
                            <p id="dashboard-local-date" class="mt-2 text-sm text-slate-300">
                                {{ now()->timezone($timeZone)->format('l, F j, Y') }}
                            </p>
                            <p class="mt-1 text-xs uppercase tracking-[0.24em] text-slate-400">
                                {{ str_replace('_', ' ', $timeZone) }}
                            </p>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-2xl bg-white/8 px-4 py-3 ring-1 ring-white/10">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Scope</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    {{ auth()->user()?->role === 'manager' ? 'Focused on your assigned location.' : 'Using your current dashboard access level.' }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/8 px-4 py-3 ring-1 ring-white/10">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Next step</p>
                                <p class="mt-2 text-sm font-medium text-white">
                                    {{ isset($openPunches) ? 'Review the live totals below and jump into the action panels.' : 'Use the workday tools below to clock or review your records.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:[grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))]">
                @foreach ($stats as $stat)
                    <article class="rounded-[1.6rem] px-5 py-5 shadow-sm ring-1 {{ $stat['card'] }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] {{ str_contains($stat['card'], 'text-white') ? 'text-slate-300' : 'text-slate-400' }}">
                            {{ $stat['eyebrow'] }}
                        </p>
                        <p class="mt-4 text-3xl font-semibold tracking-tight {{ $stat['valueClass'] }}">{{ $stat['value'] }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-700 {{ str_contains($stat['card'], 'text-white') ? '!text-slate-200' : '' }}">
                            {{ $stat['label'] }}
                        </p>
                        <p class="mt-3 text-sm leading-6 {{ $stat['noteClass'] }}">{{ $stat['note'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-5 xl:[grid-template-columns:repeat(auto-fit,minmax(22rem,1fr))]">
                @foreach ($actionSections as $section)
                    <article class="rounded-[1.75rem] border border-slate-200/80 bg-white/90 p-6 shadow-sm shadow-slate-200/50 backdrop-blur">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400">{{ $section['title'] }}</p>
                                <h3 class="mt-3 text-xl font-semibold tracking-tight text-slate-900">{{ $section['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $section['description'] }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                                {{ $section['badge'] }}
                            </span>
                        </div>

                        <div class="mt-6 grid gap-3">
                            @foreach ($section['actions'] as $action)
                                <a
                                    href="{{ $action['href'] }}"
                                    class="group rounded-[1.35rem] border border-slate-200 bg-slate-50/90 p-4 transition duration-150 hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-lg hover:shadow-slate-200/60"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="inline-flex h-2.5 w-11 rounded-full {{ $action['accent'] }}"></span>
                                        <span class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400 transition group-hover:text-slate-500">
                                            Open
                                        </span>
                                    </div>
                                    <h4 class="mt-4 text-base font-semibold text-slate-900">{{ $action['label'] }}</h4>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $action['description'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timeElement = document.getElementById('dashboard-local-time');
            const dateElement = document.getElementById('dashboard-local-date');

            if (!timeElement || !dateElement) {
                return;
            }

            const timeZone = @json($timeZone);

            const renderClock = function () {
                const now = new Date();
                timeElement.textContent = new Intl.DateTimeFormat([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                    timeZone,
                }).format(now);
                dateElement.textContent = new Intl.DateTimeFormat([], {
                    weekday: 'long',
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                    timeZone,
                }).format(now);
            };

            renderClock();
            window.setInterval(renderClock, 1000);
        });
    </script>
</x-app-layout>
