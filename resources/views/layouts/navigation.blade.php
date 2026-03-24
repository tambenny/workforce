@php
    $user = Auth::user();
    $navLocation = $user->location;
    $isAdmin = $user->role === 'admin';
    $isAdminOrHr = in_array($user->role, ['admin', 'hr'], true);
    $canViewDashboard = $user->canViewDashboard();
    $canUseWebClock = $user->canUseWebClock();
    $canViewPunchLog = $user->canViewPunchLog();
    $canViewPunchSummary = $user->canViewPunchSummary();
    $canViewSchedules = $user->canViewSchedules();
    $canViewScheduleSummary = $user->canViewScheduleSummary();
    $canViewScheduleTimeline = $user->canViewScheduleDetails();
    $canCreateSchedules = $user->hasSchedulePermission('create');
    $canApproveSchedules = $user->hasSchedulePermission('approve');
    $canViewCurrentStaff = $user->canViewCurrentStaffReport();
    $canViewPunchPhotos = $user->canViewPunchPhotos();
    $canViewWarnings = $user->canViewSecurityWarnings();
    $punchLogLabel = $canViewPunchLog && ! $user->canViewOwnPunches() && $canViewCurrentStaff ? 'Punch Log' : 'My Punches';

    $showSchedulesMenu = $canViewSchedules || $canViewScheduleSummary || $canCreateSchedules || $canApproveSchedules;
    $showManagementMenu = $canViewPunchLog || $canViewPunchSummary || $canViewCurrentStaff || $canViewPunchPhotos || $canViewWarnings;
    $showHrMenu = $isAdminOrHr;
    $showAdminMenu = $isAdmin;
    $currentLocale = app()->getLocale();

    $isSchedulesActive = request()->routeIs('schedules.*');
    $isManagementActive = request()->routeIs('punches.*') || request()->routeIs('reports.security-warnings*');
    $isHrActive = request()->routeIs('staff.*') || request()->routeIs('positions.*');
    $isAdminActive = request()->routeIs('locations.*');

    $desktopMenuBase = 'inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
    $desktopMenuTriggerClasses = fn (bool $active) => $active
        ? $desktopMenuBase . ' bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
        : $desktopMenuBase . ' text-slate-600 hover:bg-white hover:text-slate-900';
    $desktopLinkClasses = fn (bool $active) => $active
        ? $desktopMenuBase . ' bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
        : $desktopMenuBase . ' text-slate-600 hover:bg-white hover:text-slate-900';
    $dropdownLinkClasses = fn (bool $active) => $active
        ? 'block rounded-md bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700'
        : 'block rounded-md px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';
    $desktopLocaleButtonClasses = fn (bool $active) => $active
        ? 'rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white shadow-sm'
        : 'rounded-full px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900';
    $mobileLocaleButtonClasses = fn (bool $active) => $active
        ? 'rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm'
        : 'rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900';
@endphp

<nav x-data="{ open: false }" class="relative z-50 border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center rounded-full bg-slate-100 p-1.5 ring-1 ring-slate-200">
                        <x-application-logo class="block h-8 w-auto fill-current text-slate-800" />
                    </a>
                </div>

                <div class="hidden sm:flex sm:min-w-0 sm:items-center sm:gap-2 sm:rounded-full sm:bg-slate-100 sm:p-1 sm:ring-1 sm:ring-slate-200">
                    @if ($canViewDashboard)
                        <a href="{{ route('dashboard') }}" class="{{ $desktopLinkClasses(request()->routeIs('dashboard')) }}">
                            {{ __('Dashboard') }}
                        </a>
                    @endif

                    @if ($canUseWebClock)
                        <a href="{{ route('clock.index') }}" class="{{ $desktopLinkClasses(request()->routeIs('clock.*')) }}">
                            {{ __('Clock') }}
                        </a>
                    @endif

                    @if ($showSchedulesMenu)
                        <x-dropdown align="left" width="w-56" contentClasses="py-2 bg-white">
                            <x-slot name="trigger">
                                <button type="button" class="{{ $desktopMenuTriggerClasses($isSchedulesActive) }}">
                                    <span>{{ __('Schedules') }}</span>
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    {{ __('Schedules') }}
                                </div>

                                @if ($canViewSchedules)
                                    <a href="{{ route('schedules.index') }}" class="{{ $dropdownLinkClasses(request()->routeIs('schedules.index') || request()->routeIs('schedules.form')) }}">
                                        {{ __('All Schedules') }}
                                    </a>
                                @endif

                                @if ($canViewScheduleTimeline)
                                    <a href="{{ route('schedules.timeline') }}" class="{{ $dropdownLinkClasses(request()->routeIs('schedules.timeline')) }}">
                                        {{ __('Schedule Timeline') }}
                                    </a>
                                @endif

                                @if ($canViewScheduleSummary)
                                    <a href="{{ route('schedules.summary') }}" class="{{ $dropdownLinkClasses(request()->routeIs('schedules.summary')) }}">
                                        {{ __('Schedule Summary') }}
                                    </a>
                                @endif

                                @if ($canCreateSchedules)
                                    <a href="{{ route('schedules.create') }}" class="{{ $dropdownLinkClasses(request()->routeIs('schedules.create')) }}">
                                        {{ __('Create Schedule') }}
                                    </a>
                                @endif

                                @if ($canApproveSchedules)
                                    <a href="{{ route('schedules.approvals') }}" class="{{ $dropdownLinkClasses(request()->routeIs('schedules.approvals')) }}">
                                        {{ __('Approvals') }}
                                    </a>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if ($showManagementMenu)
                        <x-dropdown align="left" width="w-60" contentClasses="py-2 bg-white">
                            <x-slot name="trigger">
                                <button type="button" class="{{ $desktopMenuTriggerClasses($isManagementActive) }}">
                                    <span>{{ __('Management') }}</span>
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    {{ __('Management') }}
                                </div>

                                @if ($canViewPunchLog)
                                    <a href="{{ route('punches.index') }}" class="{{ $dropdownLinkClasses(request()->routeIs('punches.index')) }}">
                                        {{ __($punchLogLabel) }}
                                    </a>
                                @endif

                                @if ($canViewCurrentStaff)
                                    <a href="{{ route('punches.current') }}" class="{{ $dropdownLinkClasses(request()->routeIs('punches.current')) }}">
                                        {{ __('Current Staff') }}
                                    </a>
                                @endif

                                @if ($canViewPunchSummary)
                                    <a href="{{ route('punches.summary') }}" class="{{ $dropdownLinkClasses(request()->routeIs('punches.summary')) }}">
                                        {{ __('Punch Summary') }}
                                    </a>
                                @endif

                                @if ($canViewPunchPhotos)
                                    <a href="{{ route('punches.photos') }}" class="{{ $dropdownLinkClasses(request()->routeIs('punches.photos')) }}">
                                        {{ __('Punch Photos') }}
                                    </a>
                                @endif

                                @if ($canViewWarnings)
                                    <div class="my-2 border-t border-gray-100"></div>

                                    <a href="{{ route('reports.security-warnings') }}" class="{{ $dropdownLinkClasses(request()->routeIs('reports.security-warnings*')) }}">
                                        {{ __('Warnings') }}
                                    </a>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if ($showHrMenu)
                        <x-dropdown align="left" width="w-56" contentClasses="py-2 bg-white">
                            <x-slot name="trigger">
                                <button type="button" class="{{ $desktopMenuTriggerClasses($isHrActive) }}">
                                    <span>{{ __('HR') }}</span>
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    {{ __('HR') }}
                                </div>

                                @if ($isAdminOrHr)
                                    <a href="{{ route('staff.index') }}" class="{{ $dropdownLinkClasses(request()->routeIs('staff.*')) }}">
                                        {{ __('Staff') }}
                                    </a>
                                @endif

                                @if ($isAdminOrHr)
                                    <a href="{{ route('positions.index') }}" class="{{ $dropdownLinkClasses(request()->routeIs('positions.*')) }}">
                                        {{ __('Positions') }}
                                    </a>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if ($showAdminMenu)
                        <x-dropdown align="left" width="w-56" contentClasses="py-2 bg-white">
                            <x-slot name="trigger">
                                <button type="button" class="{{ $desktopMenuTriggerClasses($isAdminActive) }}">
                                    <span>{{ __('Admin') }}</span>
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                    {{ __('Admin') }}
                                </div>

                                <a href="{{ route('locations.index') }}" class="{{ $dropdownLinkClasses(request()->routeIs('locations.*')) }}">
                                    {{ __('Locations') }}
                                </a>
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden shrink-0 sm:flex sm:items-center sm:gap-3">
                <form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white p-1 shadow-sm">
                    @csrf
                    <button type="submit" name="locale" value="en" class="{{ $desktopLocaleButtonClasses($currentLocale === 'en') }}">
                        EN
                    </button>
                    <button type="submit" name="locale" value="zh_CN" class="{{ $desktopLocaleButtonClasses($currentLocale === 'zh_CN') }}">
                        中文
                    </button>
                </form>

                @if ($navLocation)
                    <div class="flex max-w-36 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                        @if ($navLocation->logo_path)
                            <img
                                src="{{ asset('storage/' . $navLocation->logo_path) }}"
                                alt="{{ $navLocation->name }} logo"
                                class="h-6 w-6 rounded object-cover"
                            >
                        @endif
                        <span class="truncate text-sm font-medium text-slate-700">{{ $navLocation->name }}</span>
                    </div>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ $user->name }}</div>

                            <div>
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                            >
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if ($canViewDashboard)
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
            @endif

            @if ($canUseWebClock)
                <x-responsive-nav-link :href="route('clock.index')" :active="request()->routeIs('clock.*')">
                    {{ __('Clock') }}
                </x-responsive-nav-link>
            @endif

            @if ($showSchedulesMenu)
                <div class="px-4 pt-4 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ __('Schedules') }}
                </div>

                @if ($canViewSchedules)
                    <x-responsive-nav-link class="ps-6" :href="route('schedules.index')" :active="request()->routeIs('schedules.index') || request()->routeIs('schedules.form')">
                        {{ __('All Schedules') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewScheduleTimeline)
                    <x-responsive-nav-link class="ps-6" :href="route('schedules.timeline')" :active="request()->routeIs('schedules.timeline')">
                        {{ __('Schedule Timeline') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewScheduleSummary)
                    <x-responsive-nav-link class="ps-6" :href="route('schedules.summary')" :active="request()->routeIs('schedules.summary')">
                        {{ __('Schedule Summary') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canCreateSchedules)
                    <x-responsive-nav-link class="ps-6" :href="route('schedules.create')" :active="request()->routeIs('schedules.create')">
                        {{ __('Create Schedule') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canApproveSchedules)
                    <x-responsive-nav-link class="ps-6" :href="route('schedules.approvals')" :active="request()->routeIs('schedules.approvals')">
                        {{ __('Approvals') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($showManagementMenu)
                <div class="px-4 pt-4 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ __('Management') }}
                </div>

                @if ($canViewPunchLog)
                    <x-responsive-nav-link class="ps-6" :href="route('punches.index')" :active="request()->routeIs('punches.index')">
                        {{ __($punchLogLabel) }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewCurrentStaff)
                    <x-responsive-nav-link class="ps-6" :href="route('punches.current')" :active="request()->routeIs('punches.current')">
                        {{ __('Current Staff') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewPunchSummary)
                    <x-responsive-nav-link class="ps-6" :href="route('punches.summary')" :active="request()->routeIs('punches.summary')">
                        {{ __('Punch Summary') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewPunchPhotos)
                    <x-responsive-nav-link class="ps-6" :href="route('punches.photos')" :active="request()->routeIs('punches.photos')">
                        {{ __('Punch Photos') }}
                    </x-responsive-nav-link>
                @endif

                @if ($canViewWarnings)
                    <x-responsive-nav-link class="ps-6" :href="route('reports.security-warnings')" :active="request()->routeIs('reports.security-warnings*')">
                        {{ __('Warnings') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($showHrMenu)
                <div class="px-4 pt-4 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ __('HR') }}
                </div>

                @if ($isAdminOrHr)
                    <x-responsive-nav-link class="ps-6" :href="route('staff.index')" :active="request()->routeIs('staff.*')">
                        {{ __('Staff') }}
                    </x-responsive-nav-link>
                @endif

                @if ($isAdminOrHr)
                    <x-responsive-nav-link class="ps-6" :href="route('positions.index')" :active="request()->routeIs('positions.*')">
                        {{ __('Positions') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if ($showAdminMenu)
                <div class="px-4 pt-4 pb-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ __('Admin') }}
                </div>

                <x-responsive-nav-link class="ps-6" :href="route('locations.index')" :active="request()->routeIs('locations.*')">
                    {{ __('Locations') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="mb-4 flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Language') }}</span>
                    <form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center gap-2">
                        @csrf
                        <button type="submit" name="locale" value="en" class="{{ $mobileLocaleButtonClasses($currentLocale === 'en') }}">
                            EN
                        </button>
                        <button type="submit" name="locale" value="zh_CN" class="{{ $mobileLocaleButtonClasses($currentLocale === 'zh_CN') }}">
                            中文
                        </button>
                    </form>
                </div>

                <div class="font-medium text-base text-gray-800">{{ $user->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $user->email }}</div>
                @if ($navLocation)
                    <div class="mt-3 flex items-center gap-2 rounded-md border border-gray-200 px-2 py-1">
                        @if ($navLocation->logo_path)
                            <img
                                src="{{ asset('storage/' . $navLocation->logo_path) }}"
                                alt="{{ $navLocation->name }} logo"
                                class="h-7 w-7 rounded object-cover"
                            >
                        @endif
                        <span class="text-sm text-gray-700">{{ $navLocation->name }}</span>
                    </div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out"
                    >
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
