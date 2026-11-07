<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Collection: {{ $collection->name }}
            </h2>
            <a href="{{ route('collections.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Collections
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('collections.update', $collection->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', $collection->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <!-- Event Date -->
                            <div>
                                <label for="event_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event Date</label>
                                <input type="date" name="event_date" id="event_date" value="{{ old('event_date', $collection->event_date ? (is_string($collection->event_date) ? $collection->event_date : $collection->event_date->format('Y-m-d')) : '') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select name="status" id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Draft" {{ old('status', $collection->status) === 'Draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="Published" {{ old('status', $collection->status) === 'Published' ? 'selected' : '' }}>Published</option>
                                    <option value="Archived" {{ old('status', $collection->status) === 'Archived' ? 'selected' : '' }}>Archived</option>
                                </select>
                            </div>

                            <!-- Cover Photo -->
                            <div>
                                <label for="cover_photo_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Photo</label>
                                @php($photos = $collection->sets()->with('photos')->get()->pluck('photos')->flatten())
                                @if($photos->count() > 0)
                                    <select name="cover_photo_id" id="cover_photo_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">No cover photo</option>
                                        @foreach($photos as $photo)
                                            <option value="{{ $photo->id }}" {{ old('cover_photo_id', $collection->cover_photo_id) == $photo->id ? 'selected' : '' }}>
                                                {{ $photo->caption ?: 'Photo #' . $photo->id }} ({{ $photo->set->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select a photo to use as the cover.</p>
                                @else
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No photos available yet. Upload photos to a set first.</p>
                                @endif
                            </div>

                            <!-- Cover Photo Focus -->
                            <div>
                                <label for="cover_photo_object_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Photo Focus</label>
                                <select name="cover_photo_object_position" id="cover_photo_object_position" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @php($positions = [
                                        'center' => 'Center',
                                        'top' => 'Top',
                                        'bottom' => 'Bottom',
                                        'top-right' => 'Top Right',
                                        'bottom-right' => 'Bottom Right',
                                    ])
                                    @foreach($positions as $value => $label)
                                        <option value="{{ $value }}" {{ old('cover_photo_object_position', $collection->cover_photo_object_position ?? 'center center') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose which part of the image stays visible when cropped.</p>
                            </div>

                            <!-- Private Toggle -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibility</label>
                                <div class="flex items-center gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="hidden" name="private" value="0">
                                        <input type="checkbox" name="private" id="private" value="1" {{ old('private', $collection->private) ? 'checked' : '' }}
                                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Private Collection</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="hidden" name="hide_from_portfolio" value="0">
                                        <input type="checkbox" name="hide_from_portfolio" id="hide_from_portfolio" value="1" {{ old('hide_from_portfolio', $collection->hide_from_portfolio) ? 'checked' : '' }}
                                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Hide from Portfolio</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="hidden" name="watermarked" value="0">
                                        <input type="checkbox" name="watermarked" value="1" {{ old('watermarked', $collection->watermarked) ? 'checked' : '' }}
                                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Watermarked</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Password (only if private) -->
                            <div id="password-field" @if(!old('private', $collection->private)) style="display: none" @endif>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gallery password</label>
                                <input type="text" name="password" id="password"
                                       placeholder="{{ $collection->private ? 'Change password (optional)' : 'Set a password for private collections' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $collection->password ? 'Leave blank to keep the current password.' : 'Clients enter this to open the gallery. Clients you email get a link that opens it without the password.' }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <a href="{{ route('collections.index') }}" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                Update Collection
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($collection->status !== 'Published')
            <div class="mt-8 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/40 p-5 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                <div>
                    <h3 class="font-semibold text-indigo-900 dark:text-indigo-100">Clients can't see this collection yet</h3>
                    <p class="text-sm text-indigo-800 dark:text-indigo-200">Publish it to get a link you can share with clients. Any changes above are saved too.</p>
                </div>
                <button type="button" onclick="const s = document.getElementById('status'); s.value = 'Published'; s.form.requestSubmit();"
                        class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                    Publish now
                </button>
            </div>
            @endif

            <!-- Shareable Link Section -->
            @if($collection->status === 'Published')
            <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Share Collection</h3>

                    <div class="space-y-4">
                        <!-- Public/Private Collection Link -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $collection->private ? 'Private Collection Link' : 'Public Collection Link' }}
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       id="collection-link"
                                       value="{{ route('profile.collection', ['username' => auth()->user()->username, 'collection_id' => $collection->id]) }}"
                                       readonly
                                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="button"
                                        onclick="copyLink(event)"
                                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Copy Link
                                </button>
                            </div>
                        </div>

                        @if($collection->private)
                        <!-- Password Display for Private Collections -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-yellow-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-yellow-800">Private Collection</h4>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        Visitors need the gallery password to open this link. Use the email button below to send your client a personal link that opens the gallery without a password for 30 days.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Email Client Button -->
                        <div>
                            <button type="button"
                                    onclick="showEmailModal()"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Email gallery link to client
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Manage Sets Section -->
            <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Manage Sets</h3>
                    @livewire('manage-sets', ['collectionId' => $collection->id])
                </div>
            </div>

            <!-- Danger zone -->
            <div class="mt-8 rounded-lg border border-red-200 dark:border-red-900 bg-white dark:bg-gray-800 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Delete collection</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Permanently deletes this collection with all of its sets and photos. This can't be undone.</p>
                </div>
                <button type="button" onclick="deleteCollection()"
                        class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-md border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                    Delete collection
                </button>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    @if($collection->private && $collection->status === 'Published')
    <div id="email-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Email Collection to Client</h3>
                <form id="email-client-form" onsubmit="emailClient(event)">
                    <div class="space-y-4">
                        <div>
                            <label for="client-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client Email</label>
                            <input type="email"
                                   id="client-email"
                                   required
                                   placeholder="client@example.com"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="client-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client Name (optional)</label>
                            <input type="text"
                                   id="client-name"
                                   placeholder="John Doe"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="custom-message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Message (optional)</label>
                            <textarea id="custom-message"
                                      rows="3"
                                      placeholder="Add a personal message..."
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <strong>Preview:</strong> Your client gets a personal link that opens the gallery without a password for 30 days, plus your message if you add one.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-end mt-6">
                        <button type="button"
                                onclick="closeEmailModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script>
        // Toggle password field visibility and auto-check hide_from_portfolio
        document.getElementById('private').addEventListener('change', function() {
            document.getElementById('password-field').style.display = this.checked ? 'block' : 'none';

            // Auto-check "hide from portfolio" when making collection private
            const hideFromPortfolio = document.getElementById('hide_from_portfolio');
            if (this.checked && !hideFromPortfolio.checked) {
                hideFromPortfolio.checked = true;
            }
        });

        function deleteCollection() {
            if (confirm('Are you sure you want to delete this collection? This will permanently delete all sets and photos within it.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("collections.destroy", $collection) }}';
                form.innerHTML = '@csrf @method("DELETE")';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function copyLink(event) {
            const linkInput = document.getElementById('collection-link');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // For mobile devices

            navigator.clipboard.writeText(linkInput.value).then(function() {
                if (typeof window.showSuccessToast === 'function') {
                    window.showSuccessToast('Link copied to clipboard!');
                } else {
                    console.log('Link copied to clipboard!');
                }
            }, function(err) {
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Failed to copy link: ' + err);
                } else {
                    console.error('Failed to copy link:', err);
                }
            });
        }

        @if($collection->private && $collection->status === 'Published')
        function showEmailModal() {
            document.getElementById('email-modal').classList.remove('hidden');
        }

        function closeEmailModal() {
            document.getElementById('email-modal').classList.add('hidden');
            document.getElementById('email-client-form').reset();
        }

        function emailClient(event) {
            event.preventDefault();

            const email = document.getElementById('client-email').value;
            const name = document.getElementById('client-name').value;
            const message = document.getElementById('custom-message').value;

            // Disable submit button
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            fetch('{{ route("collections.email-client", $collection) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    email: email,
                    name: name,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Email sent successfully to ' + email);
                    }
                    closeEmailModal();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast('Error: ' + (data.message || 'Failed to send email'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('An error occurred while sending the email');
                }
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }

        // Close modal when clicking outside
        document.getElementById('email-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmailModal();
            }
        });
        @endif
    </script>
</x-app-layout>
