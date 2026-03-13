<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Location Management</h2>
            <a href="{{ route('locations.create') }}" class="rounded-md px-4 py-2 shadow" style="display:inline-block;background:#2563eb !important;border:1px solid #1d4ed8;color:#ffffff !important;-webkit-text-fill-color:#ffffff;font-size:14px;font-weight:600;line-height:20px;">New Location</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 rounded-xl bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('locations.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search location name or allowed IP"
                        class="w-full rounded-md border-gray-300 text-sm"
                    >
                    <div class="flex gap-2">
                        <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Search</button>
                        <a href="{{ route('locations.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
                    </div>
                </form>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm overflow-x-auto">
                @if (session('status'))
                    <p class="mb-4 text-sm text-emerald-700">{{ session('status') }}</p>
                @endif
                @if (session('kiosk_token_plain'))
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800">New kiosk token (shown once)</p>
                        <p class="mt-1 text-sm text-amber-700">Kiosk: {{ session('kiosk_name') }}</p>
                        <p class="mt-1 break-all font-mono text-xs text-amber-900">{{ session('kiosk_token_plain') }}</p>
                        <p class="mt-2 text-sm text-amber-700">Open this URL on the kiosk browser:</p>
                        <p class="mt-1 break-all font-mono text-xs text-amber-900">{{ session('kiosk_url') }}</p>
                    </div>
                @endif
                @if ($errors->has('location'))
                    <p class="mb-4 text-sm text-red-700">{{ $errors->first('location') }}</p>
                @endif

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Logo</th>
                            <th class="py-2">Name</th>
                            <th class="py-2">Allowed Machine IP</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Users</th>
                            <th class="py-2">Kiosks</th>
                            <th class="py-2">Schedules</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr class="border-b">
                                <td class="py-2">
                                    @if ($location->logo_path)
                                        <img
                                            src="{{ asset('storage/' . $location->logo_path) }}"
                                            alt="{{ $location->name }} logo"
                                            class="h-10 w-10 rounded object-cover"
                                        >
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2 font-medium">{{ $location->name }}</td>
                                <td class="py-2">{{ $location->allowed_ip ?? '-' }}</td>
                                <td class="py-2">
                                    @if ($location->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-2">{{ $location->users_count }}</td>
                                <td class="py-2">{{ $location->kiosks_count }}</td>
                                <td class="py-2">{{ $location->schedules_count }}</td>
                                <td class="py-2">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('locations.edit', $location) }}" class="rounded bg-blue-50 px-3 py-1 text-blue-700">Edit</a>
                                        <form method="POST" action="{{ route('locations.kiosk.rotate', $location) }}" onsubmit="return confirm('Rotate kiosk token for this location? Current kiosk devices will need the new token.');">
                                            @csrf
                                            <button class="rounded bg-amber-50 px-3 py-1 text-amber-700">Rotate Kiosk Token</button>
                                        </form>
                                        <form method="POST" action="{{ route('locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-red-50 px-3 py-1 text-red-700">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-500">No locations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $locations->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
