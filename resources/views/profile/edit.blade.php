<x-app-layout>
    <x-slot name="header">
        <div class="relative left-1/2 w-screen max-w-[92rem] -translate-x-1/2 px-4 sm:px-5 lg:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600">Account Settings</p>
                    <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900">{{ __('Profile') }}</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Update your account details, password, and account controls from one page.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="bg-[linear-gradient(180deg,#f8fafc_0%,#eef6ff_38%,#f8fafc_100%)] py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 sm:px-5 lg:px-6">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                <div class="border-b border-slate-200 px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Profile Information</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Account identity</h3>
                </div>
                <div class="px-6 py-6">
                    <div class="max-w-2xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                    <div class="border-b border-slate-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Security</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Password</h3>
                    </div>
                    <div class="px-6 py-6">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[2rem] border border-rose-200 bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]">
                    <div class="border-b border-rose-200 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-rose-600">Account Control</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Delete Account</h3>
                    </div>
                    <div class="px-6 py-6">
                        <div class="max-w-2xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
