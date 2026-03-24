<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Edit Location</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Adjust the site name, network rule, active status, and kiosk branding details.
                    </p>
                </div>

                <a
                    href="{{ route('locations.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to Locations
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto grid max-w-[92rem] gap-6 sm:px-5 lg:grid-cols-[minmax(0,0.72fr)_minmax(280px,0.28fr)] lg:px-6">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Location Setup</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $location->name }}</h3>
                </div>

                <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-6 px-6 py-6" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Location Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $location->name) }}" placeholder="Example: Downtown Restaurant" required />
                        <p class="mt-2 text-sm text-slate-500">Keep the name consistent with schedules, kiosks, and reports.</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Access Rule</p>
                        <div class="mt-4">
                            <x-input-label for="allowed_ip" value="Allowed Machine IP (optional)" />
                            <x-text-input id="allowed_ip" name="allowed_ip" class="mt-1 block w-full" value="{{ old('allowed_ip', $location->allowed_ip) }}" />
                            <p class="mt-2 text-sm text-slate-500">Use this to enforce same-store sign-in and clocking.</p>
                            <x-input-error :messages="$errors->get('allowed_ip')" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Branding</p>
                        <div class="mt-4">
                            <x-input-label for="logo" value="Location Logo (optional)" />
                            <input id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm">
                            <p class="mt-2 text-sm text-slate-500">Upload a new file only if you want to replace the current logo.</p>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        </div>

                        @if ($location->logo_path)
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Current Logo</p>
                                <div class="mt-4 flex flex-wrap items-center gap-4">
                                    <img
                                        src="{{ asset('storage/' . $location->logo_path) }}"
                                        alt="{{ $location->name }} logo"
                                        class="h-16 w-16 rounded-2xl border border-slate-200 object-cover"
                                    >
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo') === '1')>
                                        <span class="text-sm text-slate-700">Remove logo</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('remove_logo')" class="mt-2" />
                            </div>
                        @endif
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $location->is_active ? '1' : '0') === '1')>
                        <span class="text-sm text-slate-700">Active location</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a
                            href="{{ route('locations.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                        <x-primary-button>Save Location</x-primary-button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="rounded-3xl border {{ $location->is_active ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] {{ $location->is_active ? 'text-emerald-700' : 'text-rose-700' }}">Status</p>
                    <p class="mt-4 text-3xl font-semibold {{ $location->is_active ? 'text-emerald-900' : 'text-rose-900' }}">{{ $location->is_active ? 'Active' : 'Inactive' }}</p>
                    <p class="mt-2 text-sm {{ $location->is_active ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $location->allowed_ip ? 'A network rule is currently set for this site.' : 'No network rule is currently enforced.' }}
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Current Rule</p>
                    <p class="mt-4 text-lg font-semibold text-slate-900">{{ $location->allowed_ip ?: 'No allowed IP set' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Update this only if the kiosk or same-store controls should change.</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
