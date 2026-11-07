<div class="space-y-4">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
        <span>Brand Logo</span>
        @if(!$canUpload)
            <span class="px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Locked</span>
        @endif
    </h3>

    @if(session('logo-status'))
        <div id="logo-flash" class="text-xs px-3 py-2 rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
            {{ session('logo-status') }}
        </div>
    @endif

    @if(!$canUpload)
        <p class="text-xs text-gray-500 dark:text-gray-400">Upgrade your plan to upload a custom logo. <a href="{{ route('pricing') }}" class="text-indigo-600 dark:text-indigo-400 underline">View pricing</a></p>
    @else
        <div class="flex items-center gap-4">
            @if($currentLogoUrl)
                <img src="{{ $user->logoUrl() }}" alt="Logo" class="h-16 max-w-[10rem] object-contain rounded shadow" />
            @elseif($logo)
                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="h-16 max-w-[10rem] object-contain rounded shadow" />
            @else
                <div class="h-16 w-16 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 text-[10px] text-gray-400 dark:text-gray-500">No Logo</div>
            @endif

            <div class="flex flex-col gap-2">
                <input type="file" wire:model="logo" accept="image/*" class="text-xs" />
                @error('logo') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                <div class="flex gap-2">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" {{ $logo ? '' : 'disabled' }} class="px-3 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">Save Logo</button>
                    @if($currentLogoUrl)
                        <button type="button" wire:click="remove" wire:loading.attr="disabled" class="px-3 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50">Remove</button>
                    @endif
                </div>
                <div wire:loading wire:target="logo" class="text-xs text-gray-500 dark:text-gray-400">Uploading...</div>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 dark:text-gray-500">PNG, JPG, WebP, SVG. Max 2MB. Thumbnail auto-generated (160px wide for raster images).</p>
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
