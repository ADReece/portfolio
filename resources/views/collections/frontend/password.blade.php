<x-guest-layout :user="$user ?? null">
    <div class="space-y-5 py-2">
        <div class="text-center space-y-1">
            @isset($user)
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->name ?? $user->username }}</p>
            @endisset
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $collection->name }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('This gallery is private. Enter the password your photographer gave you to view it.') }}
            </p>
        </div>

        <form method="POST"
              action="{{ route('profile.collection.unlock', ['username' => $user->username, 'collection_id' => $collection->id]) }}"
              class="space-y-4">
            @csrf
            <div>
                <x-input-label for="password" :value="__('Gallery password')" />
                <x-text-input id="password" class="block mt-1 w-full"
                              type="password"
                              name="password"
                              required autofocus
                              autocomplete="off"
                              aria-describedby="password-error" />
                <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" />
            </div>

            <x-primary-button class="w-full justify-center">{{ __('View gallery') }}</x-primary-button>
        </form>
    </div>
</x-guest-layout>
