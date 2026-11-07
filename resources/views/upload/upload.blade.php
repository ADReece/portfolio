<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Upload Photo') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('upload.create') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="albums">
                                Album:
                                <x-text-input list="albums" name="album" autocomplete="off" />
                                <datalist id="albums">
                                    @foreach(auth()->user()->albums as $album)
                                        <option value="{{$album->id}}" label="{{$album->name}}"></option>
                                    @endforeach
                                </datalist>
                            </label>
                            <label for="private">
                                Prviate
                                <input type="checkbox" name="private" value="1" />
                            </label>
                            <label for="password">
                                Album Password:
                                <x-text-input type="text" name="password" />
                            </label>
                        </div>
                        <x-file-select-input type="file" name="media[]" id="media[]" multiple="multiple"/>

                        <x-primary-button type="submit">Upload</x-primary-button>
                    </form>
                </div>
            </div>
        </div>

{{--        <div class="container mx-auto mt-10">--}}
{{--            <div class="bg-white shadow-md rounded-lg p-6">--}}
{{--                <!-- Form for File Upload -->--}}
{{--                <form action="{{ route('upload.create') }}" method="POST" class="dropzone" id="imageDropzone" enctype="multipart/form-data">--}}
{{--                    @csrf--}}

{{--                    <!-- Album Selection Dropdown -->--}}
{{--                    <div class="mb-4">--}}
{{--                        <label for="album" class="block text-sm font-medium text-gray-700">Choose Album (optional)</label>--}}
{{--                        <select name="album_id" id="album" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-xs focus:outline-hidden focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">--}}
{{--                            <option value="">No Album</option>--}}
{{--                            @foreach($collections as $album)--}}
{{--                                <option value="{{ $album->id }}">{{ $album->name }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </div>--}}

{{--                    <!-- Dropzone Section -->--}}
{{--                    <div class="border-4 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 hover:bg-gray-100 cursor-pointer">--}}
{{--                        <div class="dz-message text-center text-gray-500">--}}
{{--                            <i class="fas fa-cloud-upload-alt text-5xl"></i>--}}
{{--                            <p class="mt-2">Drag & drop your files here or click to upload</p>--}}
{{--                            <p class="text-sm">Only images (JPG, PNG) with a max size of 2MB are allowed.</p>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <!-- Upload Button -->--}}
{{--                    <div class="mt-6 text-right">--}}
{{--                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">--}}
{{--                            Upload--}}
{{--                        </button>--}}
{{--                    </div>--}}
{{--                </form>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <script>--}}
{{--            document.addEventListener("DOMContentLoaded", function() {--}}
{{--                Dropzone.options.imageDropzone = {--}}
{{--                    paramName: 'file',--}}
{{--                    maxFilesize: 2, // MB--}}
{{--                    acceptedFiles: 'image/*',--}}
{{--                    addRemoveLinks: true,--}}
{{--                    init: function() {--}}
{{--                        this.on("success", function(file, response) {--}}
{{--                            console.log('File uploaded successfully.');--}}
{{--                        });--}}
{{--                        this.on("error", function(file, response) {--}}
{{--                            console.error('Error during upload.');--}}
{{--                        });--}}
{{--                    }--}}
{{--                };--}}
{{--            });--}}
{{--    </script>--}}
</x-app-layout>
