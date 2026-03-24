<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Create Position</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Add a new role title so staff records and schedules can use a consistent position list.
                    </p>
                </div>

                <a
                    href="{{ route('positions.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to Positions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto grid max-w-[92rem] gap-6 sm:px-5 lg:grid-cols-[minmax(0,0.72fr)_minmax(280px,0.28fr)] lg:px-6">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Position Setup</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Title and description</h3>
                </div>

                <form method="POST" action="{{ route('positions.store') }}" class="space-y-6 px-6 py-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Position Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name') }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-2xl border-gray-300 text-sm shadow-sm">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        <span class="text-sm text-slate-700">Active</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                        <a
                            href="{{ route('positions.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>
                        <x-primary-button>Create Position</x-primary-button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Position Naming</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Use a clear job title that managers will recognize when assigning staff or reviewing schedules.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
