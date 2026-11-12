@php
    $filenames = $photos->map(fn ($p) => $p->displayFilename());
@endphp
<x-app-layout :title="$list->clientLabel().' · favourites'">
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$list->clientLabel().'\'s favourites'"
                          :description="$photos->count().' '.\Illuminate\Support\Str::plural('photo', $photos->count()).' from '.$collection->name.($list->submitted_at ? ', sent '.$list->submitted_at->format('j F Y, H:i') : ', not sent yet')"
                          :back="route('collections.client-favourites.index', $collection->id)" back-label="Client favourites">
            <x-slot:actions>
                <x-ui.button icon="arrow-down-tray" :href="route('collections.client-favourites.export', [$collection->id, $list->id])">Download filenames</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if($list->email || $list->note)
            <x-ui.card>
                <dl class="grid gap-3 text-sm sm:grid-cols-[8rem_1fr]">
                    @if($list->email)<dt class="font-medium text-gray-600 dark:text-gray-400">Email</dt><dd><a href="mailto:{{ $list->email }}" class="underline underline-offset-4">{{ $list->email }}</a></dd>@endif
                    @if($list->note)<dt class="font-medium text-gray-600 dark:text-gray-400">Note</dt><dd class="whitespace-pre-line">{{ $list->note }}</dd>@endif
                </dl>
            </x-ui.card>
        @endif

        <x-ui.card title="For Lightroom" description="In the Library, choose Filter → Text → Filename → Contains, and paste the list below. Lightroom shows the matching photos; select them all and add a flag or put them in a collection.">
            <label for="lightroom-names" class="sr-only">Filenames, separated by commas</label>
            <textarea id="lightroom-names" readonly rows="3" class="w-full rounded-control border-gray-300 font-mono text-xs dark:border-gray-600 dark:bg-gray-900" onfocus="this.select()">{{ $filenames->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))->implode(', ') }}</textarea>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">“Download filenames” gives the full names, one per line. Photos uploaded before favourites existed use the name they were stored under.</p>
        </x-ui.card>

        <ul role="list" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach($photos as $photo)
                <li>
                    <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: $photo->displayFilename() }}" loading="lazy" class="aspect-square w-full rounded-md bg-gray-100 object-cover dark:bg-gray-800">
                    <p class="mt-1 truncate font-mono text-xs text-gray-600 dark:text-gray-400">{{ $photo->displayFilename() }}</p>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('collections.client-favourites.destroy', [$collection->id, $list->id]) }}"
              data-confirm="The client's picks are removed. If they heart photos again, a new list starts." data-confirm-title="Delete these favourites?" data-confirm-button="Delete" data-confirm-danger>
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="ghost">Delete these favourites</x-ui.button>
        </form>
    </div>
</x-app-layout>
