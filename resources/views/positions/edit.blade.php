<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Position</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('positions.update', $position) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Position Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $position->name) }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" class="mt-1 block w-full rounded border-gray-300">{{ old('description', $position->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $position->is_active ? '1' : '0') === '1')>
                        <span>Active</span>
                    </label>

                    <div>
                        <x-primary-button>Save</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
