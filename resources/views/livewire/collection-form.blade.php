<div>
    <!-- Success message -->
    @if (session()->has('message'))
        <div class="bg-green-100 text-green-700 p-2 rounded-sm mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="saveCollection">
        <div class="mb-4">
            <x-input-label for="name" class="block text-gray-700">Collection Name</x-input-label>
            <x-text-input type="text" id="name" wire:model="name" class="w-full p-2 border border-gray-300 rounded-sm mt-1" />
            @error('name') <span class="text-red-600">{{ $message }}</span> @enderror
        </div>

{{--        <div class="mb-4">--}}
{{--            <x-input-label for="status" class="block text-gray-700">Status</x-input-label>--}}
{{--            <select id="status" wire:model="status" class="w-full p-2 border border-gray-300 rounded-sm mt-1">--}}
{{--                <option value="">Select status</option>--}}
{{--                <option value="active">Draft</option>--}}
{{--                <option value="inactive">Published</option>--}}
{{--            </select>--}}
{{--            @error('status') <span class="text-red-600">{{ $message }}</span> @enderror--}}
{{--        </div>--}}

        <div class="mb-4">
            <x-input-label for="event_date" class="block text-gray-700">Event Date</x-input-label>
            <x-text-input type="date" id="event_date" wire:model="event_date" class="w-full p-2 border border-gray-300 rounded-sm mt-1" />
            @error('event_date') <span class="text-red-600">{{ $message }}</span> @enderror
        </div>

{{--        <div class="mb-4">--}}
{{--            <x-input-label for="cover_image_id" class="block text-gray-700">Cover Image ID</x-input-label>--}}
{{--            <x-text-input type="number" id="cover_photo_id" wire:model="cover_photo_id" class="w-full p-2 border border-gray-300 rounded-sm mt-1" />--}}
{{--            @error('cover_image_id') <span class="text-red-600">{{ $message }}</span> @enderror--}}
{{--        </div>--}}

        <div class="mb-4">
            <x-input-label for="private" class="block text-gray-700">Private Collection?</x-input-label>
            <x-text-input type="checkbox" id="private" wire:model="private" class="mt-1" />
        </div>

        <div class="mb-4" x-data="{ isPrivate: @entangle('private') }" x-show="isPrivate">
            <x-input-label for="password" class="block text-gray-700">Password</x-input-label>
            <x-text-input type="password" id="password" wire:model="password" class="w-full p-2 border border-gray-300 rounded-sm mt-1" />
            @error('password') <span class="text-red-600">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-sm hover:bg-blue-600">
            Create Collection
        </button>
    </form>
</div>
