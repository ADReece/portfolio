<x-app-layout :title="'Client favourites · '.$collection->name">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Client favourites" :description="'Photos clients have hearted in '.$collection->name.'.'"
                          :back="route('collections.edit', $collection->id)" :back-label="$collection->name" />

        <form method="POST" action="{{ route('collections.client-favourites.settings', $collection->id) }}">
            @csrf
            @method('PATCH')
            <x-ui.card title="Settings">
                <div class="space-y-4">
                    <x-ui.checkbox name="favourites_enabled" label="Let clients pick favourites in this collection" :checked="$collection->favourites_enabled"
                                   description="A heart on each photo. Clients send their picks with an optional name, email and note." />
                    <x-ui.checkbox name="notify_favourites" label="Email me when a client sends favourites" :checked="auth()->user()->notify_favourites"
                                   description="For all your collections." />
                </div>
                <div class="mt-5 flex justify-end"><x-ui.button type="submit" size="sm" icon="check">Save</x-ui.button></div>
            </x-ui.card>
        </form>

        <x-ui.card :padding="false">
            @if($lists->isEmpty())
                <x-ui.empty-state icon="heart" title="No favourites yet"
                                  description="When clients heart photos in this collection, their picks appear here." />
            @else
                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($lists as $list)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('collections.client-favourites.show', [$collection->id, $list->id]) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $list->clientLabel() }}</a>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $list->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $list->photos_count) }}
                                    · {{ $list->submitted_at ? 'sent '.$list->submitted_at->diffForHumans() : 'still choosing, not sent yet' }}
                                    @if($list->email && $list->name) · {{ $list->email }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($list->submitted_at)<x-ui.badge tone="green" icon="check">Sent</x-ui.badge>@else<x-ui.badge>Choosing</x-ui.badge>@endif
                                <x-ui.button size="sm" variant="secondary" :href="route('collections.client-favourites.show', [$collection->id, $list->id])">View<span class="sr-only"> {{ $list->clientLabel() }}'s favourites</span></x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
