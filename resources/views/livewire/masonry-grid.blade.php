<div class="relative">
    <div x-data="masonryData" class="relative">
        <!-- Loading Overlay -->
        @php
            $hasCustomStyles = isset($user) && method_exists($user,'canUseCustomizations') && $user->canUseCustomizations();
        @endphp
        <div class="absolute inset-0 m-0 overflow-hidden loading-overlay {{ $hasCustomStyles ? '' : 'bg-gray-100 dark:bg-gray-900' }} z-40"
             x-show="initialLoading"
             x-transition>
            <div class="loader"></div>
        </div>

        <!-- Masonry Grid Container -->
        <div class="masonry-container w-full">
            <!-- Hidden template that Livewire renders into -->
            <div id="masonry-template" style="display: none;">
                @foreach($media as $index => $m)
                    @php
                        // Calculate the previous page's item count
                        $previousPageCount = ($page - 1) * $perPage;
                        $isNewItem = $index >= $previousPageCount;
                    @endphp
                    <div class="masonry-item" data-photo-id="{{ $m->id }}" wire:key="photo-{{ $m->id }}" data-new-item="{{ $isNewItem ? 'true' : 'false' }}">
                        <div class="relative group overflow-hidden rounded-lg shadow-xs hover:shadow-xl transition-shadow duration-300">
                            <!-- Image (clickable for lightbox) -->
                            <a href="{{ $m->getUri() }}"
                               data-fslightbox="gallery"
                               data-caption="{{ $m->caption ?? '' }}"
                               class="block"
                               @if($copyProtection) oncontextmenu="return false;" @endif
                               @if($copyProtection) ondragstart="return false;" @endif>
                                <img src="{{ $m->getAwsThumbnail() }}"
                                     alt="{{ $m->caption ?? 'Photo' }}"
                                     class="w-full cursor-pointer select-none"
                                     loading="{{ $index < 10 ? 'eager' : 'lazy' }}"
                                     @if($copyProtection) oncontextmenu="return false;" @endif
                                     @if($copyProtection) ondragstart="return false;" @endif
                                     draggable="false"
                                     style="user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;"
                                />
                            </a>

                            <!-- Hover Overlay with Buttons -->
                            <div class="absolute inset-0 pointer-events-none bg-black/0 can-hover:group-hover:bg-black/40 transition-all duration-300 flex items-end justify-end p-3 opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 group-focus-within:opacity-100">
                                @if($favourites)
                                    <!-- Favourite (proofing): state is kept by the gallery's favourites script -->
                                    <button type="button" data-favourite="{{ $m->id }}" aria-pressed="false"
                                            class="favourite-button pointer-events-auto mr-2 bg-white hover:bg-gray-100 text-gray-800 rounded-full p-2 shadow-lg transition-transform transform motion-safe:hover:scale-110 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
                                            title="Favourite" aria-label="Favourite {{ $m->caption ? Str::limit($m->caption, 40) : 'this photo' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                        </svg>
                                    </button>
                                @endif

                                <!-- Download Request Button (or Buy, when the collection sells downloads) -->
                                <a href="{{ $buyCollection ? route('download-purchase.show', ['collection' => $buyCollection, 'photo' => $m->id]) : '/photos/'.$m->id.'/download-options' }}"
                                   class="pointer-events-auto mr-2 bg-white hover:bg-gray-100 text-gray-800 rounded-full p-2 shadow-lg transition-transform transform motion-safe:hover:scale-110 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
                                   title="{{ $buyCollection ? 'Buy this photo' : 'Download' }}" aria-label="{{ $buyCollection ? 'Buy this photo' : 'Download this photo' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>

                                @if($ownerCanSell)
                                    <!-- Buy Print Button -->
                                    <a href="{{ route('print-purchase.show', ['photo' => $m->id]) }}"
                                       class="pointer-events-auto mr-2 bg-white hover:bg-gray-100 text-gray-800 rounded-full p-2 shadow-lg transition-transform transform motion-safe:hover:scale-110 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
                                       title="Order a print" aria-label="Order a print of this photo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-1.2 6.4A1 1 0 006.8 21h10.4a1 1 0 001-.8L20 13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                    </a>
                                @endif

                                <!-- Lightbox View Button -->
                                <button type="button"
                                        onclick="event.stopPropagation(); this.closest('.masonry-item').querySelector('[data-fslightbox]').click();"
                                        class="pointer-events-auto bg-white hover:bg-gray-100 text-gray-800 rounded-full p-2 shadow-lg transition-transform transform motion-safe:hover:scale-110 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
                                        title="View full size" aria-label="View full size">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Caption Overlay (if exists) -->
                            @if($m->caption)
                            <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <p class="text-white text-sm font-medium truncate">{{ $m->caption }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Actual masonry grid that JS controls -->
            <div id="masonry" class="masonry mx-auto" wire:ignore data-masonry-initialized="false" data-is-initial="{{ $isInitialLoad ? 'true' : 'false' }}">
            </div>
        </div>

        <!-- Loading Indicator for Infinite Scroll -->
        @if($hasMore)
            <!-- Scroll trigger - invisible element that triggers loading at bottom of page -->
            <div class="w-full h-px"
                 x-intersect:enter.once="loadMorePhotos">
            </div>

            <!-- Loading indicator (only shows during loading) -->
            <div class="w-full py-8 flex items-center justify-center"
                 x-show="isLoadingMore"
                 x-cloak
                 style="display: none;">
                <div class="loader small"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-400 text-sm">Loading more photos...</span>
            </div>
        @endif

        @if(!$hasMore && $loadedCount > 0)
            <!-- All photos loaded message -->
            <div class="w-full py-8 flex justify-center">
                <div class="text-gray-600 dark:text-gray-400 text-sm">
                    Showing all {{ $loadedCount }} {{ Str::plural('photo', $loadedCount) }}
                </div>
            </div>
        @endif

        @if($loadedCount === 0)
            <!-- No photos message -->
            <div class="w-full py-24 flex items-center justify-center">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No photos</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No photos found in this collection.</p>
                </div>
            </div>
        @endif
    </div>

    @php
        $cols = $columns ?? 4;
    @endphp

    <style>
        /* Custom Column-Based Masonry Layout */
        #masonry {
            display: flex;
            width: 100%;
        }

        .masonry-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 4px;
        }

        .masonry-column:first-child {
            padding-left: 0;
        }

        .masonry-column:last-child {
            padding-right: 0;
        }

        .masonry-item {
            margin-bottom: 8px;
            break-inside: avoid;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
        }

        /* Client favourites: a filled heart, and a badge that stays visible off hover */
        .favourite-button[aria-pressed="true"] { color: #e11d48; }
        .favourite-button[aria-pressed="true"] svg { fill: currentColor; }
        .masonry-item:has(.favourite-button[aria-pressed="true"]) > div::after {
            content: '\2665'; position: absolute; top: 8px; left: 8px; z-index: 1;
            display: flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 9999px;
            background: #fff; color: #e11d48; font-size: 16px; line-height: 1;
            box-shadow: 0 1px 3px rgba(0,0,0,.3); pointer-events: none;
        }

        .masonry-item.loaded {
            opacity: 1;
            transform: translateY(0);
        }

        .masonry-item img {
            width: 100%;
            height: auto;
            display: block;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
            pointer-events: auto;
        }

        /* Prevent text selection on masonry items */
        .masonry-item {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        /* Allow clicks on links and buttons */
        .masonry-item a,
        .masonry-item button {
            user-select: none;
            -webkit-user-select: none;
            pointer-events: auto;
        }

        /* Hide sizer elements */
        .masonry-sizer,
        .masonry-gutter-sizer {
            display: none;
        }

        /* Loading overlay inherits portfolio background */
        .loading-overlay {
            background: var(--portfolio-bg-light, #F3F4F6);
        }

        @media (prefers-color-scheme: dark) {
            .loading-overlay {
                background: var(--portfolio-bg-dark, #111827);
            }
        }

        .loader {
            border-radius: 50%;
            width: 5em;
            height: 5em;
            margin: 60px auto;
            font-size: 10px;
            position: relative;
            text-indent: -9999em;
            border-top: 1.1em solid rgba(var(--portfolio-accent-rgb, 99, 102, 241), 0.2);
            border-right: 1.1em solid rgba(var(--portfolio-accent-rgb, 99, 102, 241), 0.2);
            border-bottom: 1.1em solid rgba(var(--portfolio-accent-rgb, 99, 102, 241), 0.2);
            border-left: 1.1em solid var(--portfolio-accent, #4F46E5);
            transform: translateZ(0);
            animation: load8 1.1s infinite linear;
        }

        .loader.small {
            width: 20px;
            height: 20px;
            border-width: 3px;
            margin: 0;
            font-size: 10px;
        }


        @keyframes load8 {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    @if($copyProtection)
    <script>
        // Copy protection (photographer setting): discourage casual saving of
        // photos. Right-click and dragging photos, and the save-page shortcut,
        // are blocked quietly. Printing, screenshots and developer tools are not
        // blocked: that never stopped copying and got in the way of clients.
        document.addEventListener('DOMContentLoaded', function() {
            const onPhoto = (e) => e.target.tagName === 'IMG' || e.target.closest?.('.masonry-item');
            document.addEventListener('contextmenu', (e) => { if (onPhoto(e)) e.preventDefault(); });
            document.addEventListener('dragstart', (e) => { if (onPhoto(e)) e.preventDefault(); });
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) e.preventDefault();
            });
        });
    </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add watermark overlay on images (subtle but visible when inspecting)
            const style = document.createElement('style');
            style.textContent = `
                .masonry-item img::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    pointer-events: none;
                    z-index: 1;
                }
            `;
            document.head.appendChild(style);
        });

        // Register Alpine component immediately (before Alpine starts)
        document.addEventListener('alpine:init', () => {
            Alpine.data('masonryData', () => ({
                initialLoading: true,
                isLoadingMore: false,
                loadMoreLock: false,
                columns: [],
                // The photographer's column setting is the maximum; narrower
                // screens get fewer columns so photos stay a usable size.
                maxColumns: {{ $cols }},
                minColumnWidth: 170,
                columnCount: {{ $cols }},

                responsiveColumnCount() {
                    const grid = document.querySelector('#masonry');
                    const width = grid?.clientWidth || window.innerWidth;
                    return Math.max(1, Math.min(this.maxColumns, Math.floor(width / this.minColumnWidth)));
                },

                init() {
                    this.columnCount = this.responsiveColumnCount();
                    this.$nextTick(() => {
                        this.initMasonry();
                    });

                    // Only rebuild the columns when the column count actually changes.
                    window.addEventListener('resize', () => {
                        this.debounce(() => {
                            const next = this.responsiveColumnCount();
                            if (next !== this.columnCount) {
                                this.columnCount = next;
                                this.relayout();
                            }
                        }, 200);
                    });
                },

                initMasonry() {
                    const grid = document.querySelector('#masonry');
                    const template = document.querySelector('#masonry-template');

                    if (!grid || !template) {
                        this.initialLoading = false;
                        return;
                    }

                    // Get all items from the hidden template (where Livewire renders them)
                    const items = Array.from(template.querySelectorAll('.masonry-item'));

                    if (items.length === 0) {
                        this.initialLoading = false;
                        return;
                    }

                    // Clone items and move them to the visible grid
                    const clonedItems = items.map(item => item.cloneNode(true));

                    // Create columns structure with cloned items
                    this.createColumns(grid, clonedItems);

                    // Wait for images to load for better positioning
                    const imgLoad = imagesLoaded(clonedItems, { background: true });

                    imgLoad.on('always', () => {
                        // Only redistribute once all initial images are loaded
                        this.redistributeAllItems();
                        this.initialLoading = false;
                        grid.setAttribute('data-masonry-initialized', 'true');
                        refreshFsLightbox();
                    });
                },

                createColumns(grid, existingItems) {
                    // Store items temporarily
                    const items = existingItems || Array.from(grid.querySelectorAll('.masonry-item'));

                    // Clear grid
                    grid.innerHTML = '';
                    this.columns = [];

                    // Create column elements
                    for (let i = 0; i < this.columnCount; i++) {
                        const column = document.createElement('div');
                        column.className = 'masonry-column';
                        column.dataset.columnIndex = i;
                        grid.appendChild(column);
                        this.columns.push(column);
                    }

                    // Immediately distribute the items
                    items.forEach((item, index) => {
                        const shortestColumn = this.getShortestColumn();
                        shortestColumn.appendChild(item);

                        // Add loaded class with staggered delay for smooth animation
                        setTimeout(() => {
                            item.classList.add('loaded');
                        }, index * 50); // 50ms delay between each item
                    });
                },

                redistributeAllItems() {
                    if (!this.columns || this.columns.length === 0) return;

                    // Collect all items from all columns
                    const allItems = [];
                    this.columns.forEach(column => {
                        const items = Array.from(column.querySelectorAll('.masonry-item'));
                        allItems.push(...items);
                    });

                    // Clear all columns
                    this.columns.forEach(col => col.innerHTML = '');

                    // Redistribute items
                    allItems.forEach(item => {
                        const shortestColumn = this.getShortestColumn();
                        shortestColumn.appendChild(item);
                    });
                },

                getShortestColumn() {
                    let shortestColumn = this.columns[0];
                    let shortestHeight = this.getColumnHeight(shortestColumn);

                    this.columns.forEach(column => {
                        const height = this.getColumnHeight(column);
                        if (height < shortestHeight) {
                            shortestHeight = height;
                            shortestColumn = column;
                        }
                    });

                    return shortestColumn;
                },

                getColumnHeight(column) {
                    // Calculate total height of all items in column (actual content height)
                    let totalHeight = 0;
                    const items = column.querySelectorAll('.masonry-item');

                    items.forEach(item => {
                        // Use offsetHeight which gives the actual rendered height of each item
                        totalHeight += item.offsetHeight || 0;
                        // Add margin
                        totalHeight += 8;
                    });

                    return totalHeight;
                },

                appendNewItems() {
                    const template = document.querySelector('#masonry-template');
                    if (!template) {
                        this.isLoadingMore = false;
                        return;
                    }

                    // Find new items in the template (Livewire renders all items there)
                    const newTemplateItems = Array.from(template.querySelectorAll('.masonry-item[data-new-item="true"]'));

                    if (newTemplateItems.length > 0) {
                        // Clone the new items
                        const newItems = newTemplateItems.map(item => {
                            const cloned = item.cloneNode(true);
                            // Ensure animation styles are set BEFORE adding to DOM
                            cloned.classList.remove('loaded');
                            cloned.style.opacity = '0';
                            cloned.style.transform = 'translateY(30px)';
                            return cloned;
                        });

                        // Remove the marker from template items
                        newTemplateItems.forEach(item => {
                            item.removeAttribute('data-new-item');
                        });

                        // Wait for images to load BEFORE distributing
                        const imgLoad = imagesLoaded(newItems, { background: true });

                        imgLoad.on('always', () => {
                            // Now that images are loaded, distribute them
                            this.distributeNewItemsWithAnimation(newItems);

                            // Hide loading spinner after distribution
                            this.isLoadingMore = false;

                            // Refresh lightbox to include new items
                            refreshFsLightbox();
                        });
                    } else {
                        // No new items found
                        this.isLoadingMore = false;
                        refreshFsLightbox();
                    }
                },

                distributeNewItemsWithAnimation(items) {
                    items.forEach((item, index) => {
                        // Find the shortest column based on current actual heights
                        const shortestColumn = this.getShortestColumn();

                        // Add item to the shortest column
                        shortestColumn.appendChild(item);

                        // Force reflow to ensure the item is rendered
                        item.offsetHeight;

                        // Trigger animation with staggered delay
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                            item.classList.add('loaded');
                        }, index * 50); // 50ms delay between each new item
                    });
                },

                relayout() {
                    const grid = document.querySelector('#masonry');
                    if (!grid) return;

                    const allItems = Array.from(grid.querySelectorAll('.masonry-item'));
                    this.createColumns(grid, allItems);
                    refreshFsLightbox();
                },

                debounce(func, wait) {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(func, wait);
                },

                loadMorePhotos() {
                    // Prevent multiple simultaneous calls
                    if (this.isLoadingMore || this.loadMoreLock) {
                        return;
                    }

                    // Set lock to prevent rapid-fire calls
                    this.loadMoreLock = true;
                    this.isLoadingMore = true;

                    // Small delay before actually calling Livewire
                    setTimeout(() => {
                        if (window.Livewire && window.Livewire.dispatch) {
                            window.Livewire.dispatch('load-more-photos');
                        } else {
                            this.isLoadingMore = false;
                        }

                        // Release lock after a short delay
                        setTimeout(() => {
                            this.loadMoreLock = false;
                        }, 500);
                    }, 100);
                }
            }));
        });

        // After "load more" renders new items (MasonryGrid::loadMore dispatches
        // this once the DOM has been updated), move them into the columns.
        window.addEventListener('masonry-items-loaded', () => {
            const el = document.querySelector('[x-data="masonryData"]');
            const alpineComponent = el && Alpine.$data(el);
            if (alpineComponent) {
                setTimeout(() => alpineComponent.appendNewItems(), 50);
            }
        });

        // Download request function
        function requestDownload(photoId) {
            window.promptUser({
                title: 'Request Download',
                message: 'Enter your email address to receive the download link:',
                placeholder: 'your@email.com',
                inputType: 'email',
                confirmText: 'Send Link',
                onConfirm: (email) => {
                    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        window.showErrorToast('Please enter a valid email address');
                        return;
                    }

                    fetch(`/photos/${photoId}/request-download`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.showSuccessToast('Download link has been sent to ' + email);
                        } else {
                            window.showErrorToast('Error: ' + (data.message || 'Failed to send download link'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.showErrorToast('An error occurred. Please try again.');
                    });
                }
            });
        }
    </script>
</div>
