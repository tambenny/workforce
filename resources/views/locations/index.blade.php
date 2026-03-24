<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Location Management</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Review active sites, network restrictions, and kiosk coverage in one cleaner grid.
                    </p>
                </div>

                <a
                    href="{{ route('locations.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500"
                >
                    New Location
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Total Locations</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Sites currently listed in the location directory.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Active</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['active'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Locations that can be used for staffing and kiosk activity.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Kiosk Enabled</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['with_kiosk'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Sites with at least one kiosk token/device assigned.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('locations.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex-1">
                        <label for="location-search" class="sr-only">Search locations</label>
                        <input
                            id="location-search"
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Search location name or allowed IP"
                            class="w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        >
                    </div>

                    <div class="flex gap-2">
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Search</button>
                        <a href="{{ route('locations.index') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                @if (session('status'))
                    <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('kiosk_token_plain'))
                    <div class="mx-6 mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                        <p class="text-sm font-semibold text-amber-900">New kiosk token shown once</p>
                        <p class="mt-2 text-sm text-amber-800">Kiosk: {{ session('kiosk_name') }}</p>
                        <p class="mt-2 break-all rounded-xl bg-white/80 px-3 py-2 font-mono text-xs text-amber-900 ring-1 ring-amber-200">
                            {{ session('kiosk_token_plain') }}
                        </p>
                        <p class="mt-3 text-sm text-amber-800">Open this URL on the kiosk browser:</p>
                        <p class="mt-2 break-all rounded-xl bg-white/80 px-3 py-2 font-mono text-xs text-amber-900 ring-1 ring-amber-200">
                            {{ session('kiosk_url') }}
                        </p>
                    </div>
                @endif

                @if ($errors->has('location'))
                    <div class="mx-6 mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        {{ $errors->first('location') }}
                    </div>
                @endif

                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[24%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[18%] border-b border-slate-700 px-3 py-3.5">Network Access</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Status</th>
                                <th class="w-[18%] border-b border-slate-700 px-3 py-3.5">Activity</th>
                                <th class="w-[30%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locations as $location)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div class="flex items-start gap-3">
                                            @if ($location->logo_path)
                                                <img
                                                    src="{{ asset('storage/' . $location->logo_path) }}"
                                                    alt="{{ $location->name }} logo"
                                                    class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-200"
                                                >
                                            @else
                                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-500 ring-1 ring-slate-200">
                                                    {{ strtoupper(mb_substr($location->name, 0, 1)) }}
                                                </div>
                                            @endif

                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-900">{{ $location->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ $location->logo_path ? 'Custom logo uploaded' : 'No custom logo uploaded' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Allowed Machine IP</p>
                                        <p class="mt-1 font-medium text-slate-800">{{ $location->allowed_ip ?: 'No IP restriction' }}</p>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $location->is_active ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Users</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $location->users_count }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Kiosks</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $location->kiosks_count }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route('locations.edit', $location) }}"
                                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[13px] font-semibold text-blue-700 transition hover:bg-blue-100"
                                            >
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('locations.kiosk.rotate', $location) }}" onsubmit="return confirm('Rotate kiosk token for this location? Current kiosk devices will need the new token.');">
                                                @csrf
                                                <button class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[13px] font-semibold text-amber-700 transition hover:bg-amber-100">
                                                    Rotate Kiosk Token
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-[13px] font-semibold text-rose-700 transition hover:bg-rose-100">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No locations found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $locations->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
