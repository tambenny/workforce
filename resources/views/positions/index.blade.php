<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Positions</h2>
                </div>

                <a
                    href="{{ route('positions.create') }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500"
                >
                    Add Position
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Total Positions</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Roles currently listed in the workforce directory.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Active</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['active'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Positions available for staff assignment right now.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Assigned</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['assigned'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Positions that already have at least one team member assigned.</p>
                </div>
            </div>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                @if (session('status'))
                    <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="overflow-x-auto px-4 pb-5 pt-5">
                    <table class="w-full table-fixed border-separate border-spacing-0 text-[13px] text-slate-700">
                        <thead>
                            <tr class="bg-slate-900/95 text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
                                <th class="w-[28%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Position</th>
                                <th class="w-[42%] border-b border-slate-700 px-3 py-3.5">Description</th>
                                <th class="w-[14%] border-b border-slate-700 px-3 py-3.5">Assigned Staff</th>
                                <th class="w-[10%] border-b border-slate-700 px-3 py-3.5">Status</th>
                                <th class="w-[6%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($positions as $position)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $position->name }}</p>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <p class="leading-6 text-slate-700">{{ $position->description ?: '-' }}</p>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <div class="inline-flex rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                            <div>
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Team Members</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $position->users_count }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $position->is_active ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-slate-200 bg-slate-100 text-slate-700' }}">
                                            {{ $position->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                        <a
                                            href="{{ route('positions.edit', $position) }}"
                                            class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[13px] font-semibold text-blue-700 transition hover:bg-blue-100"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No positions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $positions->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
