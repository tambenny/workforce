<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Security Warnings</h2>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Warnings</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Security events in the current visible scope.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Open</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['open'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Warnings that still need review or resolution.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Resolved</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['resolved'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Warnings that have already been marked resolved.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Locations</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['locations'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Sites represented in the current warning log.</p>
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
                                <th class="w-[14%] rounded-l-2xl border-b border-slate-700 px-3 py-3.5">Time</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Type</th>
                                <th class="w-[14%] border-b border-slate-700 px-3 py-3.5">User</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">Location</th>
                                <th class="w-[12%] border-b border-slate-700 px-3 py-3.5">IP</th>
                                <th class="w-[22%] border-b border-slate-700 px-3 py-3.5">Message</th>
                                <th class="w-[8%] border-b border-slate-700 px-3 py-3.5">Status</th>
                                <th class="w-[6%] rounded-r-2xl border-b border-slate-700 px-3 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($warnings as $warning)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50/70' }} transition hover:bg-sky-50/70">
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $warning->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ \Illuminate\Support\Str::of($warning->warning_type)->replace('_', ' ')->title() }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $warning->user?->name ?? '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $warning->location?->name ?? '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $warning->ip_address ?? '-' }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">{{ $warning->message }}</td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $warning->resolved_at ? 'border border-slate-200 bg-slate-100 text-slate-700' : 'border border-rose-200 bg-rose-50 text-rose-700' }}">
                                            {{ $warning->resolved_at ? 'Resolved' : 'Open' }}
                                        </span>
                                    </td>
                                    <td class="border-b border-slate-200 px-3 py-3.5 text-right align-top">
                                        @if (! $warning->resolved_at)
                                            <form method="POST" action="{{ route('reports.security-warnings.resolve', $warning) }}">
                                                @csrf
                                                <button class="inline-flex items-center rounded-full bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800">
                                                    Resolve
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="border-b border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                        No warnings found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $warnings->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
