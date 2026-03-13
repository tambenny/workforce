<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Location</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('locations.store') }}" class="space-y-4" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Location Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name') }}" placeholder="Example: 北京门店 / 上海仓库" required />
                        <p class="mt-1 text-xs text-slate-500">Chinese names are supported (UTF-8), e.g. 北京门店.</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="allowed_ip" value="Allowed Machine IP (optional)" />
                        <x-text-input id="allowed_ip" name="allowed_ip" class="mt-1 block w-full" value="{{ old('allowed_ip') }}" />
                        <p class="mt-1 text-xs text-slate-500">Example: 192.168.1.25</p>
                        <x-input-error :messages="$errors->get('allowed_ip')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="logo" value="Location Logo (optional)" />
                        <input id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded border-gray-300">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, or WEBP. Max 2MB.</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        <span>Active location</span>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-md px-4 py-2" style="background:#2563eb !important;border:1px solid #1d4ed8;color:#ffffff !important;-webkit-text-fill-color:#ffffff;font-size:14px;font-weight:600;line-height:20px;opacity:1;">Create</button>
                        <a href="{{ route('locations.index') }}" class="rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
