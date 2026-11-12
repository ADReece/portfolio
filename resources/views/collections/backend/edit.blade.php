@php
    $isPublished = $collection->status === 'Published';
    $positions = ['center' => 'Centre', 'top' => 'Top', 'bottom' => 'Bottom', 'top-right' => 'Top right', 'bottom-right' => 'Bottom right'];
    $currentPosition = old('cover_photo_object_position', $collection->cover_photo_object_position ?: 'center');
    $currentPosition = array_key_exists($currentPosition, $positions) ? $currentPosition : 'center';
@endphp

<x-app-layout :title="$collection->name">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$collection->name" :back="route('collections.index')" back-label="Collections">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="heart" :href="route('collections.client-favourites.index', $collection->id)">
                    Client favourites @if($favouriteCount = $collection->favouriteLists()->whereNotNull('submitted_at')->count())<x-ui.badge tone="brand">{{ $favouriteCount }}</x-ui.badge>@endif
                </x-ui.button>
                @if($isPublished)
                    <x-ui.button variant="secondary" icon="eye" :href="$shareUrl" target="_blank" rel="noopener">View as client</x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            @include('collections.partials.status-badge', ['status' => $collection->status])
            @if($collection->private)<x-ui.badge tone="brand" icon="lock-closed">Private</x-ui.badge>@endif
            @if($collection->watermarked)<x-ui.badge tone="blue" icon="shield-check">Watermarked</x-ui.badge>@endif
            <span>{{ $collection->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $collection->photos_count) }} in {{ $collection->sets_count }} {{ \Illuminate\Support\Str::plural('set', $collection->sets_count) }}</span>
            @if($collection->event_date)<span aria-hidden="true">·</span><span>{{ $collection->event_date->format('j F Y') }}</span>@endif
        </div>

        @unless($isPublished)
            <x-ui.alert title="Clients can't see this collection yet">
                @if($collection->photos_count === 0)
                    Add a set and upload photos below, then publish to get a link to share.
                @else
                    Publish it to get a link you can share with your client.
                @endif
                @if($collection->photos_count > 0)
                    <x-slot:actions>
                        <x-ui.button size="sm" icon="paper-airplane"
                                     onclick="const s = document.getElementById('status'); s.value = 'Published'; s.form.requestSubmit();">
                            Publish now
                        </x-ui.button>
                    </x-slot:actions>
                @endif
            </x-ui.alert>
        @endunless

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Sets and photos" description="Group photos into sets, like “Ceremony” and “Reception”. Open a set to upload photos.">
                    @livewire('manage-sets', ['collectionId' => $collection->id])
                </x-ui.card>

                <form method="POST" action="{{ route('collections.update', $collection->id) }}" id="collection-settings"
                      x-data="{ isPrivate: @js((bool) old('private', $collection->private)) }">
                    @csrf
                    @method('PATCH')

                    <x-ui.card title="Collection details">
                        <div class="space-y-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.input name="name" label="Name" :value="old('name', $collection->name)" required />
                                <x-ui.input name="event_date" type="date" label="Date of shoot" optional
                                            :value="old('event_date', optional($collection->event_date)->format('Y-m-d'))" />
                                <x-ui.select name="status" label="Status" :selected="old('status', $collection->status)"
                                             :options="['Draft' => 'Draft (only you can see it)', 'Published' => 'Published (clients can open the link)', 'Archived' => 'Archived (hidden, kept for your records)']" />
                            </div>

                            <fieldset class="space-y-3">
                                <legend class="text-sm font-medium text-gray-800 dark:text-gray-200">Cover photo</legend>
                                @if($coverChoices->isEmpty())
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Upload photos to choose a cover.</p>
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Shown at the top of the gallery and on your portfolio.</p>
                                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                                        @foreach($coverChoices as $photo)
                                            <label class="group relative cursor-pointer">
                                                <input type="radio" name="cover_photo_id" value="{{ $photo->id }}" class="peer sr-only"
                                                       @checked((string) old('cover_photo_id', $collection->cover_photo_id) === (string) $photo->id)>
                                                <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: 'Photo '.$loop->iteration }}" loading="lazy"
                                                     class="aspect-square w-full rounded-md object-cover ring-2 ring-transparent transition peer-checked:ring-brand-500 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 group-hover:opacity-90">
                                                <span class="absolute top-1 right-1 hidden rounded-full bg-brand-600 p-0.5 text-white peer-checked:block">
                                                    <x-ui.icon name="check" variant="m" class="size-3.5" />
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('cover_photo_id')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                                    <div class="max-w-xs">
                                        <x-ui.select name="cover_photo_object_position" label="Keep this part in view when cropped" :options="$positions" :selected="$currentPosition" />
                                    </div>
                                @endif
                            </fieldset>

                            <fieldset class="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                                <legend class="sr-only">Privacy and protection</legend>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Privacy and protection</h3>
                                <x-ui.checkbox name="private" label="Private collection" :checked="old('private', $collection->private)"
                                               description="Clients need the password, or the personal link you email them. Private collections never appear on your portfolio."
                                               x-model="isPrivate" />
                                <div x-show="isPrivate" x-cloak class="ml-7 max-w-sm">
                                    <x-ui.input name="password" type="text" autocomplete="off"
                                                :label="$collection->password ? 'Change password' : 'Password'"
                                                :help="$collection->password ? 'Leave blank to keep the current password.' : 'Clients type this to open the collection.'" />
                                </div>
                                <x-ui.checkbox name="hide_from_portfolio" label="Hide from my portfolio" :checked="old('hide_from_portfolio', $collection->hide_from_portfolio)"
                                               description="The collection still works for anyone with the link." />
                                <x-ui.checkbox name="watermarked" label="Watermark photos" :checked="old('watermarked', $collection->watermarked)"
                                               description="Clients see and download watermarked copies. Set your watermark text in Customize." />
                            </fieldset>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
                        </div>
                    </x-ui.card>
                </form>

                @php
                    $owner = auth()->user();
                    $downloadMinimum = app(\App\Downloads\DigitalPricing::class)->minimumPrice();
                    $sym = \App\Support\Currency::symbol();
                @endphp
                <form method="POST" action="{{ route('collections.digital-sales.update', $collection->id) }}" id="downloads"
                      x-data="{ selling: @js((bool) old('digital_sales', $collection->digital_sales)) }">
                    @csrf
                    @method('PATCH')
                    <x-ui.card title="Sell downloads" description="Clients buy full-resolution, unwatermarked copies and get a download link by email.">
                        <div class="space-y-5">
                            <x-ui.checkbox name="digital_sales" label="Sell downloads from this collection" :checked="old('digital_sales', $collection->digital_sales)" x-model="selling"
                                           description="While this is on, the free “Download” and “Download all” options are replaced by “Buy photos”." />
                            <div x-show="selling" x-cloak class="ml-7 space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <x-ui.input name="digital_photo_price" type="number" step="0.01" min="{{ $downloadMinimum }}" inputmode="decimal" optional
                                                label="Price per photo ({{ $sym }})" :value="old('digital_photo_price', $collection->digital_photo_price)"
                                                :help="$owner->digital_photo_price !== null ? 'Leave empty to use your usual '.\App\Support\Currency::format($owner->digital_photo_price).'.' : 'Leave empty to sell only in bundles or the whole collection.'" />
                                    <x-ui.input name="digital_collection_price" type="number" step="0.01" min="{{ $downloadMinimum }}" inputmode="decimal" optional
                                                label="Whole collection ({{ $sym }})" :value="old('digital_collection_price', $collection->digital_collection_price)"
                                                help="Every photo in the collection, for one price." />
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($owner->digital_bundles)
                                        Your bundles apply too: {{ collect(\App\Downloads\DigitalPricing::normaliseBundles($owner->digital_bundles))->map(fn ($b) => $b['count'].' for '.\App\Support\Currency::format($b['price']))->implode(', ') }}.
                                    @else
                                        Add bundles, like any 5 photos for {{ $sym }}20, in
                                    @endif
                                    <a href="{{ route('profile.digital-sales.edit') }}" class="font-medium text-brand-700 underline underline-offset-4 dark:text-brand-300">{{ $owner->digital_bundles ? 'Change your bundles' : 'Photo downloads' }}</a>.
                                </p>
                                @if(! \App\Billing\Payouts::available() || ! $owner->stripe_connect_enabled)
                                    <p class="text-sm text-amber-800 dark:text-amber-300">Clients can buy once {{ \App\Billing\Payouts::available() ? 'you connect Stripe in Payouts' : 'payouts are ready' }}.</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <x-ui.button type="submit" icon="check">Save</x-ui.button>
                        </div>
                    </x-ui.card>
                </form>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Share with your client" id="share">
                    @if($isPublished)
                        <div class="space-y-4" x-data="{ copied: false }">
                            <div class="space-y-1">
                                <label for="collection-link" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Gallery link</label>
                                <div class="flex gap-2">
                                    <input id="collection-link" type="text" readonly value="{{ $shareUrl }}" x-on:focus="$el.select()"
                                           class="min-w-0 flex-1 rounded-control border-gray-300 bg-gray-50 text-sm shadow-xs dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    <x-ui.button variant="secondary" x-on:click="navigator.clipboard.writeText(document.getElementById('collection-link').value).then(() => { copied = true; setTimeout(() => copied = false, 2000) }).catch(() => document.getElementById('collection-link').select())">
                                        <x-ui.icon name="clipboard-document" class="size-4" />
                                        <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                    </x-ui.button>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400" role="status" aria-live="polite">
                                    @if($collection->private)
                                        Anyone opening this link needs the password.
                                    @else
                                        Anyone with this link can view the collection.
                                    @endif
                                </p>
                            </div>
                            @if($collection->private)
                                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">Or email your client a personal link that opens the collection without the password for 30 days.</p>
                                    <x-ui.button icon="envelope" class="w-full" onclick="showEmailModal()">Email a link to your client</x-ui.button>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">Publish this collection to get a link you can share. You'll be able to copy it here or email it to your client.</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Delete collection">
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Permanently deletes this collection with all of its sets and photos.</p>
                    <x-ui.button variant="danger-outline" icon="trash" onclick="deleteCollection()">Delete collection</x-ui.button>
                </x-ui.card>
            </div>
        </div>
    </div>

    @if($collection->private && $isPublished)
        {{-- Email-a-link dialog --}}
        <div id="email-modal" x-data="{ open: false, sending: false }" x-show="open" x-cloak
             x-on:open-email-modal.window="open = true" x-on:close-email-modal.window="open = false" x-on:keydown.escape.window="open = false"
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-950/50" x-on:click="open = false" aria-hidden="true"></div>
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                <div role="dialog" aria-modal="true" aria-labelledby="email-modal-title" x-trap.noscroll="open"
                     class="relative w-full overflow-hidden rounded-card bg-white shadow-xl sm:max-w-lg dark:bg-gray-800">
                    <form id="email-client-form"
                          x-on:submit.prevent="sending = true; emailClient($el).finally(() => sending = false)">
                        <div class="space-y-4 p-5 sm:p-6">
                            <div>
                                <h2 id="email-modal-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Email a link to your client</h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">They'll get a personal link that opens “{{ $collection->name }}” without the password for 30 days.</p>
                            </div>
                            <x-ui.input id="client-email" type="email" label="Client's email" required autocomplete="off" />
                            <x-ui.input id="client-name" label="Client's name" optional />
                            <x-ui.textarea id="custom-message" label="Personal message" optional rows="3" placeholder="Here are your photos!" />
                        </div>
                        <div class="flex flex-col-reverse gap-2 bg-gray-50 px-5 py-3 sm:flex-row sm:justify-end sm:px-6 dark:bg-gray-900/60">
                            <x-ui.button variant="secondary" x-on:click="open = false">Cancel</x-ui.button>
                            <x-ui.button type="submit" icon="paper-airplane" x-bind:disabled="sending">
                                <span x-text="sending ? 'Sending…' : 'Send email'">Send email</span>
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        function deleteCollection() {
            window.confirmAction({
                title: 'Delete this collection?',
                message: 'This permanently deletes the collection and every set and photo in it. Clients will no longer be able to open its link.',
                confirmText: 'Delete collection',
                isDanger: true,
                onConfirm: () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @js(route('collections.destroy', $collection));
                    form.innerHTML = @js((string) csrf_field().(string) method_field('DELETE'));
                    document.body.appendChild(form);
                    form.submit();
                },
            });
        }

        @if($collection->private && $isPublished)
        function showEmailModal() {
            window.dispatchEvent(new CustomEvent('open-email-modal'));
        }

        function emailClient(form) {
            const email = form.querySelector('#client-email').value;
            return fetch(@js(route('collections.email-client', $collection)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    email,
                    name: form.querySelector('#client-name').value,
                    message: form.querySelector('#custom-message').value,
                }),
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    window.showSuccessToast('Sent to ' + email);
                    form.reset();
                    window.dispatchEvent(new CustomEvent('close-email-modal'));
                } else {
                    const first = data.errors ? Object.values(data.errors)[0][0] : null;
                    window.showErrorToast(first || data.message || "We couldn't send the email. Try again.");
                }
            })
            .catch(() => window.showErrorToast("We couldn't send the email. Check your connection and try again."));
        }
        @endif
    </script>
</x-app-layout>
