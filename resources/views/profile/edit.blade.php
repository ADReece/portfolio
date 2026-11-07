<x-app-layout title="Settings">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Settings" description="Your profile, sign-in details and account." />

        @unless($user->hasFeature('upload_logo') || $user->isFeatureOverrideActive())
            <x-ui.alert title="Make your portfolio look like your brand">
                Upgrade to add your logo, choose colours and fonts, and watermark your photos.
                <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
            </x-ui.alert>
        @endunless

        <form method="post" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')
            <x-ui.card title="Profile" description="Shown on your public portfolio.">
                <div class="space-y-5">
                    <x-ui.input name="name" label="Name" :value="old('name', $user->name)" required autocomplete="name" />
                    <x-ui.field id="portfolio-address" label="Portfolio address" help="Your portfolio address can't be changed.">
                        <div class="flex flex-wrap items-center gap-2">
                            <code id="portfolio-address" class="rounded-control bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ route('profile.view', $user->username) }}</code>
                            <x-ui.button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="route('profile.view', $user->username)" target="_blank" rel="noopener">Open</x-ui.button>
                        </div>
                    </x-ui.field>
                    <div class="space-y-1">
                        <span id="bio-label" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Bio <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span></span>
                        <div class="rich-text">
                            <div data-rich-text data-input="bio" aria-labelledby="bio-label" data-placeholder="A few lines about you and your photography" class="min-h-40"></div>
                        </div>
                        <input type="hidden" name="bio" id="bio" value="{{ old('bio', $user->bioHtml()) }}">
                        @error('bio')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" role="status" class="text-sm text-green-700 dark:text-green-400">Saved</p>
                    @endif
                    <x-ui.button type="submit">Save profile</x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.card title="Email" description="Used to sign in and for account emails.">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->email }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">To change your email, contact support.</p>
            </div>
        </x-ui.card>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')
            <x-ui.card title="Password" description="Use a long password you don't use anywhere else.">
                <div class="max-w-md space-y-5">
                    <x-ui.input id="update_password_current_password" name="current_password" type="password" label="Current password" bag="updatePassword" autocomplete="current-password" />
                    <x-ui.input id="update_password_password" name="password" type="password" label="New password" bag="updatePassword" autocomplete="new-password" />
                    <x-ui.input id="update_password_password_confirmation" name="password_confirmation" type="password" label="Confirm new password" bag="updatePassword" autocomplete="new-password" />
                </div>
                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                    @if (session('status') === 'password-updated')
                        <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" role="status" class="text-sm text-green-700 dark:text-green-400">Password updated</p>
                    @endif
                    <x-ui.button type="submit">Update password</x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.card title="Delete account">
            <p class="text-sm text-gray-600 dark:text-gray-400">Permanently deletes your account, portfolio, collections and photos. Clients will no longer be able to open your links. Download anything you want to keep first.</p>
            <form method="post" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4"
                  x-data="{ confirming: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                @csrf
                @method('delete')
                <x-ui.button variant="danger-outline" icon="trash" x-show="!confirming" x-on:click="confirming = true; $nextTick(() => $refs.deletePassword.focus())">Delete my account</x-ui.button>
                <div x-show="confirming" x-cloak class="max-w-md space-y-4 rounded-card bg-red-50 p-4 ring-1 ring-red-200 dark:bg-red-950/50 dark:ring-red-900">
                    <x-ui.input id="delete_account_password" name="password" type="password" label="Enter your password to confirm" bag="userDeletion" autocomplete="current-password" x-ref="deletePassword" />
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit" variant="danger">Permanently delete account</x-ui.button>
                        <x-ui.button variant="secondary" x-on:click="confirming = false">Cancel</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
