<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Positions</h2>
            <a href="{{ route('positions.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add Position</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                @if (session('status'))
                    <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
                @endif

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Name</th>
                            <th class="py-2">Description</th>
                            <th class="py-2">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($positions as $position)
                            <tr class="border-b">
                                <td class="py-2">{{ $position->name }}</td>
                                <td class="py-2">{{ $position->description ?? '-' }}</td>
                                <td class="py-2">{{ $position->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="py-2">
                                    <a href="{{ route('positions.edit', $position) }}" class="text-blue-600">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $positions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
