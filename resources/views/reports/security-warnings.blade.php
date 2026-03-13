<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Security Warnings</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Time</th>
                            <th class="py-2">Type</th>
                            <th class="py-2">User</th>
                            <th class="py-2">Location</th>
                            <th class="py-2">IP</th>
                            <th class="py-2">Message</th>
                            <th class="py-2">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($warnings as $warning)
                            <tr class="border-b">
                                <td class="py-2">{{ $warning->created_at }}</td>
                                <td class="py-2">{{ $warning->warning_type }}</td>
                                <td class="py-2">{{ $warning->user?->name ?? '-' }}</td>
                                <td class="py-2">{{ $warning->location?->name ?? '-' }}</td>
                                <td class="py-2">{{ $warning->ip_address ?? '-' }}</td>
                                <td class="py-2">{{ $warning->message }}</td>
                                <td class="py-2">{{ $warning->resolved_at ? 'Resolved' : 'Open' }}</td>
                                <td class="py-2">
                                    @if (! $warning->resolved_at)
                                        <form method="POST" action="{{ route('reports.security-warnings.resolve', $warning) }}">
                                            @csrf
                                            <button class="rounded bg-slate-700 px-3 py-1 text-white">Resolve</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $warnings->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
