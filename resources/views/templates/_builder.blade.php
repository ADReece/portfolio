{{-- Shared template builder UI (included by create.blade.php and edit.blade.php) --}}
{{-- Expects Alpine component `templateEditor` to be on the parent form --}}

<div class="flex gap-6 items-start">

    {{-- ══════════════ LEFT — FORM ══════════════ --}}
    <div class="w-full lg:w-96 shrink-0 space-y-5">

        {{-- Info --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wide">Template Info</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" required
                       placeholder="e.g. Sports Portrait – Home Kit"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea name="description" rows="2"
                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                          placeholder="Optional notes">{{ old('description', $template->description ?? '') }}</textarea>
            </div>
        </div>

        {{-- Overlay --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Overlay</h3>

            @isset($template)
                @if($template->overlay_path)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $template->overlayUrl() }}" alt="Current overlay"
                             class="h-16 w-16 object-contain bg-gray-900 rounded-sm border border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Current overlay
                            @if($template->psd_path)
                                <br><span class="text-indigo-400">PSD source stored</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endisset

            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                Upload a <strong>PNG</strong> (transparent areas show through) or a <strong>PSD</strong> (auto-flattened to PNG).
            </label>
            <input type="file" name="overlay" accept=".png,.psd"
                   @change="handleOverlayChange"
                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                          file:mr-3 file:py-1.5 file:px-3 file:rounded-sm file:border-0
                          file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-300">
            <p class="mt-1 text-xs text-gray-400">Max 20 MB &nbsp;·&nbsp; PNG or PSD</p>
        </div>

        {{-- Text Fields --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Text Fields</h3>
                <button type="button" @click="addField()"
                        class="inline-flex items-center px-2.5 py-1 text-xs bg-indigo-600 text-white rounded-sm hover:bg-indigo-700">
                    + Add Field
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(field, index) in fields" :key="index">
                    <div :class="activeField === index ? 'ring-2 ring-indigo-400' : ''"
                         class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/30 cursor-pointer"
                         @click="activeField = index">

                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                  x-text="field.label || ('Field ' + (index + 1))"></span>
                            <button type="button" @click.stop="removeField(index)"
                                    class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-400">Key (no spaces)</label>
                                <input type="text" :name="'fields[' + index + '][key]'"
                                       x-model="field.key" placeholder="player_name"
                                       class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Label</label>
                                <input type="text" :name="'fields[' + index + '][label]'"
                                       x-model="field.label" placeholder="Player Name"
                                       class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">X position %</label>
                                <input type="number" :name="'fields[' + index + '][x]'"
                                       x-model.number="field.x" min="0" max="100" step="0.1"
                                       class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Y position %</label>
                                <input type="number" :name="'fields[' + index + '][y]'"
                                       x-model.number="field.y" min="0" max="100" step="0.1"
                                       class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Font size (px)</label>
                                <input type="number" :name="'fields[' + index + '][font_size]'"
                                       x-model.number="field.font_size" min="8" max="300"
                                       class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Text color</label>
                                <div class="mt-0.5 flex gap-1">
                                    <input type="color" :name="'fields[' + index + '][color]'"
                                           x-model="field.color"
                                           class="h-7 w-8 rounded-sm border-gray-300 cursor-pointer">
                                    <input type="text" x-model="field.color"
                                           class="flex-1 rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="#ffffff">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Alignment</label>
                                <select :name="'fields[' + index + '][align]'" x-model="field.align"
                                        class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="center">Center</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Font</label>
                                <select :name="'fields[' + index + '][font_id]'" x-model="field.font_id"
                                        class="mt-0.5 w-full rounded-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Default (OpenSans Bold)</option>
                                    <template x-for="font in availableFonts" :key="font.id">
                                        <option :value="font.id" x-text="font.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="fields.length === 0" class="text-xs text-gray-400 text-center py-3">
                    No text fields yet. Click "+ Add Field" to add one.
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ $cancelRoute }}"
               class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                {{ $submitLabel }}
            </button>
        </div>
    </div>

    {{-- ══════════════ RIGHT — LIVE PREVIEW ══════════════ --}}
    <div class="flex-1 min-w-0">
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 sticky top-6">

            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Live Preview</h3>
                <span class="text-xs text-gray-400">Drag labels to reposition · click to select</span>
            </div>

            {{-- Preview canvas area --}}
            <div class="relative bg-gray-900 rounded-lg overflow-hidden select-none"
                 style="aspect-ratio: 3/2;"
                 x-ref="previewContainer"
                 @mousemove="onPreviewMouseMove"
                 @mouseup="stopDrag"
                 @mouseleave="stopDrag">

                {{-- Placeholder --}}
                <div x-show="!previewUrl" class="absolute inset-0 flex flex-col items-center justify-center text-gray-600">
                    <svg class="w-12 h-12 mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs opacity-40">Upload a PNG or PSD overlay to preview it here</p>
                </div>

                <img x-show="previewUrl" :src="previewUrl" alt="Overlay preview"
                     class="absolute inset-0 w-full h-full object-contain pointer-events-none">

                {{-- Draggable text field handles --}}
                <template x-for="(field, index) in fields" :key="index">
                    <div class="absolute cursor-move px-2 py-0.5 rounded-sm border transition-colors"
                         :class="activeField === index
                             ? 'border-yellow-400 bg-yellow-400/25 shadow-lg'
                             : 'border-white/50 bg-black/40 hover:border-white hover:bg-black/60'"
                         :style="`left: ${field.x}%; top: ${field.y}%; transform: translate(-50%, -50%);`"
                         @mousedown.prevent="startDrag($event, index)"
                         @click.stop="activeField = index">
                        <span class="text-white text-xs font-medium whitespace-nowrap drop-shadow-sm pointer-events-none"
                              x-text="field.label || field.key || ('Field ' + (index + 1))"></span>
                    </div>
                </template>
            </div>

            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                (0, 0) = top-left &nbsp;·&nbsp; (50, 50) = centre &nbsp;·&nbsp; (100, 100) = bottom-right
            </p>

            @isset($template)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('templates.test', $template) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Test with a photo
                    </a>
                </div>
            @endisset
        </div>
    </div>

</div>

@push('scripts')
<script>
function templateEditor(existingFields, availableFonts) {
    return {
        fields: (existingFields && existingFields.length) ? existingFields : [],
        availableFonts: availableFonts || [],
        activeField: null,
        previewUrl: @isset($template) @if($template->overlay_path) '{{ $template->overlayUrl() }}' @else null @endif @else null @endisset,
        dragging: null,

        addField() {
            this.fields.push({ key: '', label: '', x: 50, y: 80, font_size: 60, color: '#ffffff', align: 'center', font_id: '' });
            this.activeField = this.fields.length - 1;
        },

        removeField(index) {
            this.fields.splice(index, 1);
            if (this.activeField !== null && this.activeField >= this.fields.length) {
                this.activeField = this.fields.length ? this.fields.length - 1 : null;
            }
        },

        handleOverlayChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.name.toLowerCase().endsWith('.psd')) {
                this.previewUrl = null; // PSD can't preview in browser
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => { this.previewUrl = e.target.result; };
            reader.readAsDataURL(file);
        },

        startDrag(event, fieldIndex) {
            const rect = this.$refs.previewContainer.getBoundingClientRect();
            this.activeField = fieldIndex;
            this.dragging = {
                fieldIndex,
                startMouseX:  event.clientX,
                startMouseY:  event.clientY,
                startFieldX:  parseFloat(this.fields[fieldIndex].x) || 0,
                startFieldY:  parseFloat(this.fields[fieldIndex].y) || 0,
                containerW:   rect.width,
                containerH:   rect.height,
            };
        },

        onPreviewMouseMove(event) {
            if (!this.dragging) return;
            const dx  = event.clientX - this.dragging.startMouseX;
            const dy  = event.clientY - this.dragging.startMouseY;
            const newX = this.dragging.startFieldX + (dx / this.dragging.containerW) * 100;
            const newY = this.dragging.startFieldY + (dy / this.dragging.containerH) * 100;
            this.fields[this.dragging.fieldIndex].x = Math.round(Math.min(100, Math.max(0, newX)) * 10) / 10;
            this.fields[this.dragging.fieldIndex].y = Math.round(Math.min(100, Math.max(0, newY)) * 10) / 10;
        },

        stopDrag() {
            this.dragging = null;
        },
    };
}
</script>
@endpush
