<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff Management</h2>
            <a href="{{ route('staff.create') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add Staff</a>
        </div>
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
                            <th class="py-2">Name</th>
                            <th class="py-2">Staff ID</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Role</th>
                            <th class="py-2">Manager Level</th>
                            <th class="py-2">Position</th>
                            <th class="py-2">Location</th>
                            <th class="py-2">Clock Rule</th>
                            <th class="py-2">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staff as $row)
                            <tr class="border-b">
                                <td class="py-2">{{ $row->name }}</td>
                                <td class="py-2">{{ $row->staff_id ?? '-' }}</td>
                                <td class="py-2">{{ $row->email }}</td>
                                <td class="py-2">{{ ucfirst($row->role) }}</td>
                                <td class="py-2">
                                    @if ($row->role === 'manager')
                                        @php
                                            $labels = [];
                                            if ($row->can_create_schedules) $labels[] = 'Can Create Schedule';
                                            if ($row->can_approve_schedules) $labels[] = 'Can Approve Schedule';
                                        @endphp
                                        {{ $labels ? implode(', ', $labels) : 'Manager (No Schedule Permission)' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-2">{{ $row->position?->name ?? '-' }}</td>
                                <td class="py-2">{{ $row->location?->name ?? '-' }}</td>
                                <td class="py-2">{{ $row->requires_schedule_for_clock ? 'Schedule required' : 'No schedule check' }}</td>
                                <td class="py-2">{{ $row->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="py-2">
                                    <a href="{{ route('staff.edit', $row) }}" class="text-blue-600">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">{{ $staff->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
