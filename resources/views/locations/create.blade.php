<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Create Location</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Set up a restaurant or site, optional network rule, and kiosk branding details.
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
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Restaurant details and kiosk settings</h3>
                </div>

                <form method="POST" action="{{ route('locations.store') }}" class="space-y-6 px-6 py-6" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Location Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name') }}" placeholder="Example: Downtown Restaurant" required />
                        <p class="mt-2 text-sm text-slate-500">Use the site name that managers and staff will recognize.</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Access Rule</p>
                        <div class="mt-4">
                            <x-input-label for="allowed_ip" value="Allowed Machine IP (optional)" />
                            <x-text-input id="allowed_ip" name="allowed_ip" class="mt-1 block w-full" value="{{ old('allowed_ip') }}" placeholder="Example: 192.168.1.25" />
                            <p class="mt-2 text-sm text-slate-500">Leave blank if the location does not need a fixed network rule.</p>
                            <x-input-error :messages="$errors->get('allowed_ip')" class="mt-2" />
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Branding</p>
                        <div class="mt-4">
                            <x-input-label for="logo" value="Location Logo (optional)" />
                            <input id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm">
                            <p class="mt-2 text-sm text-slate-500">JPG, PNG, or WEBP up to 2MB.</p>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        </div>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        <span class="text-sm text-slate-700">Active location</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a
                            href="{{ route('locations.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                        <x-primary-button>Create Location</x-primary-button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">What To Include</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Add the official site name, optional machine IP, and a logo only if the kiosk should use location-specific branding.
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-900 px-5 py-5 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-200">Deployment Note</p>
                    <p class="mt-3 text-sm leading-6 text-slate-200">
                        The allowed IP field is for same-store login and clock rules. Skip it if the location is meant to work from any network.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
