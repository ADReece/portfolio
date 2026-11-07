<div class="space-y-4">
    @if(session('logo-status'))
        <x-ui.alert tone="green" id="logo-flash" role="status">{{ session('logo-status') }}</x-ui.alert>
    @endif

    @if(!$canUpload)
        <x-ui.alert title="Custom logos aren't included in your plan">
            Upgrade to show your own logo on your portfolio and galleries.
            <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
        </x-ui.alert>
    @else
        <div class="flex flex-wrap items-center gap-5">
            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-control bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
                @if($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="max-h-full max-w-full object-contain" />
                @elseif($currentLogoUrl)
                    <img src="{{ $user->logoUrl() }}" alt="Your current logo" class="max-h-full max-w-full object-contain" />
                @else
                    <x-ui.icon name="photo" class="size-8 text-gray-400" />
                @endif
            </div>
            <div class="space-y-2">
                <label for="logo-file" class="block text-sm font-medium text-gray-800 dark:text-gray-200">{{ $currentLogoUrl ? 'Replace logo' : 'Upload a logo' }}</label>
                <input id="logo-file" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" aria-describedby="logo-help"
                       class="block text-sm text-gray-700 file:mr-3 file:rounded-control file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300 dark:file:bg-brand-950 dark:file:text-brand-200" />
                <p id="logo-help" class="text-sm text-gray-600 dark:text-gray-400">PNG, JPG, WebP or SVG, up to 2MB. A square or wide logo works best.</p>
                <p wire:loading wire:target="logo" class="text-sm text-gray-600 dark:text-gray-400" role="status">Uploading…</p>
                @error('logo')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                <div class="flex flex-wrap gap-2 pt-1">
                    @if($logo)
                        <x-ui.button size="sm" wire:click="save" wire:target="save">Save logo</x-ui.button>
                    @endif
                    @if($currentLogoUrl && ! $logo)
                        <x-ui.button size="sm" variant="danger-outline" wire:click="remove" wire:target="remove">Remove logo</x-ui.button>
                    @endif
                </div>
            </div>
        </div>
    @endif

<script>
    (function(){
        const flash = document.getElementById('logo-flash');
        if(flash){
            setTimeout(()=>{
                flash.style.transition='opacity .4s';
                flash.style.opacity='0';
                setTimeout(()=> flash.remove(), 600);
            },4000);
        }
    })();
    window.addEventListener('logo-updated', e => {
        const faviconLink = document.querySelector('link[rel="icon"]');
        if (faviconLink && e.detail.url) {
            faviconLink.href = e.detail.url;
        }
    });
</script>
</div>
