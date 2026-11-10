<div>
    @if($setId)
        @if(!is_null($photoLimit))
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="text-gray-600 dark:text-gray-300">
                    {{ number_format($photosUsed) }} of {{ number_format($photoLimit) }} photos used on your plan
                </span>
                @if($photosRemaining <= max(10, (int) ceil($photoLimit * 0.1)))
                    <a href="{{ route('pricing') }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        {{ $photosRemaining === 0 ? 'Upgrade to upload more' : 'Need more space? Upgrade' }}
                    </a>
                @endif
            </div>
            <div class="mb-4 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden" role="progressbar" aria-label="Photo storage used" aria-valuemin="0" aria-valuemax="{{ $photoLimit }}" aria-valuenow="{{ $photosUsed }}">
                <div class="h-full {{ $photosRemaining === 0 ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ $photoLimit ? min(100, round($photosUsed / $photoLimit * 100)) : 0 }}%"></div>
            </div>
        @endif

        <div wire:ignore>
            <div class="border-dashed border-2 border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center bg-white dark:bg-gray-800" id="file-upload-wrapper-{{ $setId }}">
                <input type="file" id="file-upload-{{ $setId }}" name="file" multiple accept="image/*">
            </div>
        </div>

        @if($uploadCount > 0)
            <div class="mt-3 text-sm text-green-700 dark:text-green-400" role="status">
                {{ $uploadCount }} {{ Str::plural('file', $uploadCount) }} uploaded successfully
            </div>
        @endif

        <script>
            (function () {
                const inputId = 'file-upload-{{ $setId }}';

                function initializeUploader() {
                    const inputElement = document.getElementById(inputId);

                    if (!inputElement) {
                        console.error('FilePond input not found:', inputId);
                        return;
                    }

                    if (inputElement.dataset.filepondInitialized === 'true') {
                        return;
                    }

                    if (typeof window.FilePond === 'undefined') {
                        console.error('FilePond library not loaded');
                        return;
                    }

                    inputElement.dataset.filepondInitialized = 'true';

                    const pond = window.FilePond.create(inputElement, {
                        allowMultiple: true,
                        allowReplace: false,
                        acceptedFileTypes: ['image/*'],
                        maxFiles: 100,
                        maxFileSize: '100MB',
                        maxParallelUploads: 1,
                        credits: false,
                        labelIdle: 'Drag photos here or <span class="filepond--label-action">choose files</span>',
                        server: {
                            process: {
                                url: '{{ route("upload-files") }}',
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                ondata: (formData) => {
                                    formData.append('set_id', '{{ $setId }}');
                                    return formData;
                                },
                                onload: (responseText) => {
                                    try {
                                        const response = JSON.parse(responseText);
                                        if (typeof @this !== 'undefined') {
                                            @this.call('handleFileUploaded');
                                        }
                                        return response.fileName || responseText;
                                    } catch (error) {
                                        return responseText;
                                    }
                                },
                                onerror: (responseText) => {
                                    let message = "That photo didn't upload. Try again, or try a smaller file.";

                                    try {
                                        const response = JSON.parse(responseText);
                                        message = response.message || response.error || message;
                                        if (response.upgrade_url) {
                                            // Plan limit reached: stop the rest of the queue too.
                                            pond.removeFiles();
                                        }
                                    } catch (error) {
                                        // Non-JSON (e.g. an HTML error page): keep the friendly default.
                                    }

                                    window.showErrorToast(message);
                                    return message;
                                },
                            },
                        },
                    });

                    pond.on('processfile', (error, file) => {
                        if (error) {
                            console.error('Error uploading file:', error);
                            return;
                        }

                    });

                    pond.on('processfiles', () => {
                        if (typeof @this !== 'undefined') {
                            @this.$refresh();
                        }
                    });
                }

                // FilePond is loaded as an ES module (deferred), so window.FilePond may
                // not be set yet when this inline script runs. If it is already set, init
                // immediately; otherwise wait for the custom event dispatched by app.js.
                if (typeof window.FilePond !== 'undefined') {
                    initializeUploader();
                } else {
                    window.addEventListener('filepond:ready', initializeUploader, { once: true });
                }

            })();
        </script>
    @else
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
            <p class="text-gray-500">Please create a set first before uploading photos.</p>
        </div>
    @endif
</div>
