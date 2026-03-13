<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Location</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('locations.update', $location) }}" class="space-y-4" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Location Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $location->name) }}" placeholder="Example: 北京门店 / 上海仓库" required />
                        <p class="mt-1 text-xs text-slate-500">Chinese names are supported (UTF-8), e.g. 广州餐厅1号.</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="allowed_ip" value="Allowed Machine IP (optional)" />
                        <x-text-input id="allowed_ip" name="allowed_ip" class="mt-1 block w-full" value="{{ old('allowed_ip', $location->allowed_ip) }}" />
                        <p class="mt-1 text-xs text-slate-500">Set this to enforce same-store login/clocking.</p>
                        <x-input-error :messages="$errors->get('allowed_ip')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="logo" value="Location Logo (optional)" />
                        <input id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded border-gray-300">
                        <p class="mt-1 text-xs text-slate-500">Upload a new file to replace current logo. Max 2MB.</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    @if ($location->logo_path)
                        <div class="space-y-2">
                            <p class="text-sm text-slate-600">Current logo:</p>
                            <img
                                src="{{ asset('storage/' . $location->logo_path) }}"
                                alt="{{ $location->name }} logo"
                                class="h-16 w-16 rounded border border-slate-200 object-cover"
                            >
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo') === '1')>
                                <span>Remove logo</span>
                            </label>
                            <x-input-error :messages="$errors->get('remove_logo')" class="mt-2" />
                        </div>
                    @endif

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', $location->is_active ? '1' : '0') === '1')>
                        <span>Active location</span>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-md px-4 py-2" style="background:#2563eb !important;border:1px solid #1d4ed8;color:#ffffff !important;-webkit-text-fill-color:#ffffff;font-size:14px;font-weight:600;line-height:20px;opacity:1;">Save</button>
                        <a href="{{ route('locations.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
