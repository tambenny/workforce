<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Workforce Admin</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">Punch Photos</h2>
                </div>

                <a href="{{ route('punches.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    Back to Punches
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Photo Punches</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['total'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Punch rows with at least one saved image.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Clock In Photos</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['clock_in_photos'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Saved arrival photos in the current filter.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Clock Out Photos</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['clock_out_photos'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Saved departure photos in the current filter.</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Staff</p>
                    <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $summary['staff'] }}</p>
                    <p class="mt-2 text-sm text-slate-600">Team members represented in these photo punches.</p>
                </div>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('punches.photos') }}" class="grid gap-3 lg:grid-cols-[repeat(2,minmax(0,1fr))_minmax(0,1fr)_minmax(0,1.2fr)_auto] lg:items-end">
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-slate-700">From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-slate-700">To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                    <div>
                        <label for="location_id" class="block text-sm font-semibold text-slate-700">Location</label>
                        <select id="location_id" name="location_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" @selected($selectedLocationId === $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user_id" class="block text-sm font-semibold text-slate-700">Staff</label>
                        <select id="user_id" name="user_id" class="mt-1 w-full rounded-2xl border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">All staff</option>
                            @foreach ($staffOptions as $staffOption)
                                <option value="{{ $staffOption->id }}" @selected($selectedUserId === $staffOption->id)>
                                    {{ $staffOption->name }} ({{ $staffOption->staff_id ?: 'No Staff ID' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
                        <a href="{{ route('punches.photos') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="p-5">
                    <div class="grid gap-5 lg:grid-cols-2">
                        @forelse ($punches as $punch)
                            @php
                                $clockInAt = $punch->clock_in_at?->timezone(config('app.timezone'));
                                $clockOutAt = $punch->clock_out_at?->timezone(config('app.timezone'));
                                $durationText = '-';

                                if ($punch->clock_in_at && $punch->clock_out_at) {
                                    $durationSeconds = $punch->clock_in_at->diffInSeconds($punch->clock_out_at);
                                    $hours = intdiv($durationSeconds, 3600);
                                    $minutes = intdiv($durationSeconds % 3600, 60);
                                    $seconds = $durationSeconds % 60;
                                    $durationText = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                } elseif ($punch->clock_in_at) {
                                    $durationText = 'Open';
                                }
                            @endphp

                            <article class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-slate-50">
                                <div class="border-b border-slate-200 bg-white px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">{{ $punch->user?->name ?? 'Unknown Staff' }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">{{ $clockInAt?->format('Y-m-d') ?? 'No Date' }}</p>
                                        </div>
                                        <div class="text-right text-sm text-slate-500">
                                            <p>{{ strtoupper($punch->source) }}</p>
                                            <p>{{ $punch->kiosk?->name ?? 'No Kiosk' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 p-5">
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Staff ID</p>
                                            <p class="mt-1 font-semibold text-slate-900">{{ $punch->user?->staff_id ?: 'No Staff ID' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Location</p>
                                            <p class="mt-1 font-semibold text-slate-900">{{ $punch->location?->name ?? 'No Location' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Duration</p>
                                            <p class="mt-1 font-semibold text-slate-900">{{ $durationText }}</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div class="mb-3 flex items-center justify-between gap-2">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-slate-700">Clock In Photo</h4>
                                                    <p class="text-xs text-slate-500">{{ $clockInAt?->format('h:i:s A') ?? '-' }}</p>
                                                </div>
                                                @if ($punch->clock_in_photo_url)
                                                    <span class="text-xs font-semibold text-blue-600">Click to enlarge</span>
                                                @endif
                                            </div>
                                            @if ($punch->clock_in_photo_url)
                                                <button
                                                    type="button"
                                                    class="photo-trigger block"
                                                    data-photo-url="{{ $punch->clock_in_photo_url }}"
                                                    data-photo-title="Clock In · {{ $punch->user?->name ?? 'Unknown Staff' }} · {{ $clockInAt?->format('Y-m-d h:i:s A') ?? '-' }}"
                                                >
                                                    <img src="{{ $punch->clock_in_photo_url }}" alt="Clock in photo for {{ $punch->user?->name ?? 'staff member' }}" class="h-28 w-full cursor-zoom-in rounded-xl border border-slate-200 object-cover">
                                                </button>
                                            @else
                                                <div class="flex h-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-2 text-center text-xs text-slate-400">
                                                    No clock in photo
                                                </div>
                                            @endif
                                        </section>

                                        <section class="rounded-2xl border border-slate-200 bg-white p-4">
                                            <div class="mb-3 flex items-center justify-between gap-2">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-slate-700">Clock Out Photo</h4>
                                                    <p class="text-xs text-slate-500">{{ $clockOutAt?->format('h:i:s A') ?? 'Still Open' }}</p>
                                                </div>
                                                @if ($punch->clock_out_photo_url)
                                                    <span class="text-xs font-semibold text-blue-600">Click to enlarge</span>
                                                @endif
                                            </div>
                                            @if ($punch->clock_out_photo_url)
                                                <button
                                                    type="button"
                                                    class="photo-trigger block"
                                                    data-photo-url="{{ $punch->clock_out_photo_url }}"
                                                    data-photo-title="Clock Out · {{ $punch->user?->name ?? 'Unknown Staff' }} · {{ $clockOutAt?->format('Y-m-d h:i:s A') ?? 'Still Open' }}"
                                                >
                                                    <img src="{{ $punch->clock_out_photo_url }}" alt="Clock out photo for {{ $punch->user?->name ?? 'staff member' }}" class="h-28 w-full cursor-zoom-in rounded-xl border border-slate-200 object-cover">
                                                </button>
                                            @else
                                                <div class="flex h-28 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-2 text-center text-xs text-slate-400">
                                                    No clock out photo
                                                </div>
                                            @endif
                                        </section>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                                        <span class="font-semibold text-slate-700">Exception:</span>
                                        {{ $punch->violation_note ?: 'None' }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.6rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-slate-500 lg:col-span-2">
                                No photo punches found for the selected filters.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="border-t border-slate-200 px-6 py-5">
                    {{ $punches->links() }}
                </div>
            </section>
        </div>
    </div>

    <div id="photoModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 px-4 py-8">
        <div class="mx-auto flex h-full max-w-5xl items-center justify-center">
            <div class="relative w-full rounded-2xl bg-white p-4 shadow-2xl">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <p id="photoModalTitle" class="text-sm font-semibold text-slate-700"></p>
                    <button id="photoModalClose" type="button" class="rounded bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-300">
                        Close
                    </button>
                </div>
                <img id="photoModalImage" src="" alt="" class="max-h-[80vh] w-full rounded-xl object-contain">
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    (() => {
        const modal = document.getElementById('photoModal');
        const modalImage = document.getElementById('photoModalImage');
        const modalTitle = document.getElementById('photoModalTitle');
        const closeButton = document.getElementById('photoModalClose');

        if (!modal || !modalImage || !modalTitle || !closeButton) {
            return;
        }

        const openModal = (url, title) => {
            modalImage.src = url;
            modalImage.alt = title;
            modalTitle.textContent = title;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modalImage.src = '';
            modalImage.alt = '';
            modalTitle.textContent = '';
            document.body.classList.remove('overflow-hidden');
        };

        document.querySelectorAll('.photo-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                openModal(trigger.dataset.photoUrl || '', trigger.dataset.photoTitle || 'Punch Photo');
            });
        });

        closeButton.addEventListener('click', closeModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
