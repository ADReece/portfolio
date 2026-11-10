<div x-data="collectionOrder" class="space-y-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Reorder Collections</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">Drag and drop to reorder how collections appear publicly.</p>

    <ul id="collection-sortable" class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 rounded-md shadow-sm">
        @foreach($collections as $c)
            <li class="flex items-center justify-between px-4 py-3 cursor-move" data-id="{{ $c['id'] }}">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M4 14h16"/></svg>
                    <span class="text-gray-800 dark:text-gray-100 font-medium">{{ $c['name'] }}</span>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $c['sort_order'] }}</span>
            </li>
        @endforeach
    </ul>

    <div class="flex items-center gap-4">
        <button x-ref="saveBtn" @click="saveOrder" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-40">Save Order</button>
        <span x-show="saved" x-transition class="text-green-600 dark:text-green-400 text-sm">Saved!</span>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('collectionOrder', () => ({
                saved:false,
                sortable:null,
                init(){
                    this.initSortable();
                    window.addEventListener('order-saved', ()=>{ this.saved=true; setTimeout(()=>this.saved=false,2000); });
                },
                initSortable(){
                    const el = document.getElementById('collection-sortable');
                    if(window.Sortable){
                        this.sortable = new window.Sortable(el, {
                            animation:150,
                            ghostClass:'bg-indigo-50 dark:bg-indigo-900',
                            onEnd:()=>{ this.saved=false; }
                        });
                    }
                },
                saveOrder(){
                    if(!this.sortable) return;
                    const ids = Array.from(document.querySelectorAll('#collection-sortable li')).map(li=>li.dataset.id);
                    this.$wire.saveOrder(ids);
                }
            }))
        });
    </script>
</div>
