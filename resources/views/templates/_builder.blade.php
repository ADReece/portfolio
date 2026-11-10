{{-- Shared template builder UI (included by create.blade.php and edit.blade.php) --}}
{{-- Expects Alpine component `templateEditor` to be on the parent form --}}

@php
    $fieldInput = 'block w-full rounded-control border-gray-300 text-sm shadow-xs focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
    $fieldLabel = 'block text-xs font-medium text-gray-700 dark:text-gray-300';
@endphp

<div class="grid items-start gap-6 lg:grid-cols-[24rem_minmax(0,1fr)]">

    {{-- ══════════════ FORM ══════════════ --}}
    <div class="min-w-0 space-y-6">

        <x-ui.card title="Details">
            <div class="space-y-4">
                <x-ui.input name="name" label="Template name" :value="old('name', $template->name ?? '')" required
                            placeholder="e.g. Sports portrait – home kit" />
                <x-ui.textarea name="description" label="Description" rows="2" optional
                               placeholder="A note to help you tell templates apart">{{ old('description', $template->description ?? '') }}</x-ui.textarea>
            </div>
        </x-ui.card>

        <x-ui.card title="Overlay" description="An image laid over the photo. Transparent areas let the photo show through.">
            @isset($template)
                @if($template->overlay_path)
                    <div class="mb-4 flex items-center gap-3">
                        <img src="{{ $template->overlayUrl() }}" alt="Current overlay"
                             class="size-16 rounded-control bg-gray-900 object-contain ring-1 ring-gray-700">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <p>Current overlay</p>
                            @if($template->psd_path)
                                <p class="text-xs text-brand-700 dark:text-brand-300">Original PSD file saved</p>
                            @endif
                        </div>
                    </div>
                @endif
            @endisset

            <div class="space-y-1">
                <label for="overlay" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                    @isset($template) @if($template->overlay_path) Replace overlay @else Overlay image @endif @else Overlay image @endisset
                    <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
                </label>
                <input id="overlay" type="file" name="overlay" accept=".png,.psd" aria-describedby="overlay-help"
                       @change="handleOverlayChange"
                       class="block w-full text-sm text-gray-600 dark:text-gray-400
                              file:mr-3 file:rounded-control file:border-0 file:bg-brand-50 file:px-3 file:py-1.5
                              file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100
                              dark:file:bg-brand-950 dark:file:text-brand-300">
                <p id="overlay-help" class="text-sm text-gray-600 dark:text-gray-400">
                    PNG or Photoshop (PSD) file, up to 20 MB. PSD files are flattened for you, but can't be previewed here.
                </p>
            </div>
        </x-ui.card>

        <x-ui.card title="Text fields" description="Text that changes per photo, like a name or date.">
            <x-slot:actions>
                <x-ui.button size="sm" icon="plus" x-on:click="addField()">Add field</x-ui.button>
            </x-slot:actions>

            <div class="space-y-3">
                <template x-for="(field, index) in fields" :key="index">
                    <div :class="activeField === index ? 'ring-2 ring-brand-500' : 'ring-1 ring-gray-200 dark:ring-gray-700'"
                         class="rounded-card bg-gray-50 p-3 dark:bg-gray-900/40"
                         @click="activeField = index"
                         @focusin="activeField = index">

                        <div class="mb-3 flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-800 dark:text-gray-200"
                                  x-text="field.label || ('Field ' + (index + 1))"></span>
                            <button type="button" @click.stop="removeField(index)"
                                    class="inline-flex items-center gap-1 rounded-control px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 dark:text-red-300 dark:hover:bg-red-950">
                                <x-ui.icon name="trash" class="size-4" />
                                Remove<span class="sr-only" x-text="' ' + (field.label || ('field ' + (index + 1)))"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 sm:col-span-1">
                                <label :for="'field-' + index + '-label'" class="{{ $fieldLabel }}">Label</label>
                                <input type="text" :id="'field-' + index + '-label'" :name="'fields[' + index + '][label]'"
                                       x-model="field.label" placeholder="Player name"
                                       class="mt-1 {{ $fieldInput }}">
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label :for="'field-' + index + '-key'" class="{{ $fieldLabel }}">Name in data file</label>
                                <input type="text" :id="'field-' + index + '-key'" :name="'fields[' + index + '][key]'"
                                       :aria-describedby="'field-' + index + '-key-help'"
                                       x-model="field.key" placeholder="player_name" autocapitalize="off" spellcheck="false"
                                       class="mt-1 font-mono {{ $fieldInput }}">
                                <p :id="'field-' + index + '-key-help'" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    How this field is named when you fill in values. No spaces — use letters, numbers, - or _.
                                </p>
                            </div>
                            <div>
                                <label :for="'field-' + index + '-x'" class="{{ $fieldLabel }}">Across (%)</label>
                                <input type="number" :id="'field-' + index + '-x'" :name="'fields[' + index + '][x]'"
                                       x-model.number="field.x" min="0" max="100" step="0.1" inputmode="decimal"
                                       class="mt-1 tabular-nums {{ $fieldInput }}">
                            </div>
                            <div>
                                <label :for="'field-' + index + '-y'" class="{{ $fieldLabel }}">Down (%)</label>
                                <input type="number" :id="'field-' + index + '-y'" :name="'fields[' + index + '][y]'"
                                       x-model.number="field.y" min="0" max="100" step="0.1" inputmode="decimal"
                                       class="mt-1 tabular-nums {{ $fieldInput }}">
                            </div>
                            <div>
                                <label :for="'field-' + index + '-size'" class="{{ $fieldLabel }}">Text size (px)</label>
                                <input type="number" :id="'field-' + index + '-size'" :name="'fields[' + index + '][font_size]'"
                                       x-model.number="field.font_size" min="8" max="300" inputmode="numeric"
                                       class="mt-1 tabular-nums {{ $fieldInput }}">
                            </div>
                            <div>
                                <label :for="'field-' + index + '-color'" class="{{ $fieldLabel }}">Text colour</label>
                                <div class="mt-1 flex gap-1.5">
                                    <input type="color" :id="'field-' + index + '-color'" :name="'fields[' + index + '][color]'"
                                           x-model="field.color"
                                           class="h-9 w-10 shrink-0 cursor-pointer rounded-control border-gray-300 dark:border-gray-600">
                                    <label :for="'field-' + index + '-color-hex'" class="sr-only">Text colour hex code</label>
                                    <input type="text" :id="'field-' + index + '-color-hex'" x-model="field.color"
                                           placeholder="#ffffff" spellcheck="false"
                                           class="min-w-0 flex-1 font-mono {{ $fieldInput }}">
                                </div>
                            </div>
                            <div>
                                <label :for="'field-' + index + '-align'" class="{{ $fieldLabel }}">Alignment</label>
                                <select :id="'field-' + index + '-align'" :name="'fields[' + index + '][align]'" x-model="field.align"
                                        class="mt-1 {{ $fieldInput }}">
                                    <option value="center">Centre</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div>
                                <label :for="'field-' + index + '-font'" class="{{ $fieldLabel }}">Font</label>
                                <select :id="'field-' + index + '-font'" :name="'fields[' + index + '][font_id]'" x-model="field.font_id"
                                        class="mt-1 {{ $fieldInput }}">
                                    <option value="">Default (OpenSans Bold)</option>
                                    <template x-for="font in availableFonts" :key="font.id">
                                        <option :value="font.id" x-text="font.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

                <p x-show="fields.length === 0" class="py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                    No text fields yet. Choose “Add field” to add one, or leave it as an overlay only.
                </p>
            </div>
        </x-ui.card>

        <div class="flex flex-wrap justify-end gap-3">
            <x-ui.button variant="secondary" :href="$cancelRoute">Cancel</x-ui.button>
            <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
        </div>
    </div>

    {{-- ══════════════ LIVE PREVIEW ══════════════ --}}
    <div class="min-w-0 lg:sticky lg:top-6">
        <x-ui.card title="Preview" description="Drag a label to move it, or select it and use the arrow keys.">
            <div class="relative select-none overflow-hidden rounded-control bg-gray-900"
                 style="aspect-ratio: 3/2;"
                 x-ref="previewContainer"
                 @pointermove="onPreviewMouseMove"
                 @pointerup.window="stopDrag"
                 @pointercancel.window="stopDrag">

                {{-- Placeholder --}}
                <div x-show="!previewUrl" class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center text-gray-400">
                    <x-ui.icon name="photo" class="size-10 opacity-60" />
                    <p class="text-sm">Upload a PNG overlay to see it here</p>
                </div>

                <img x-show="previewUrl" :src="previewUrl" alt="Overlay preview"
                     class="pointer-events-none absolute inset-0 h-full w-full object-contain">

                {{-- Draggable text field handles --}}
                <template x-for="(field, index) in fields" :key="index">
                    <button type="button"
                            class="absolute cursor-move touch-none rounded-sm border px-2 py-0.5 transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"
                            :class="activeField === index
                                ? 'border-yellow-400 bg-yellow-400/25 shadow-lg'
                                : 'border-white/50 bg-black/40 hover:border-white hover:bg-black/60'"
                            :style="`left: ${field.x}%; top: ${field.y}%; transform: translate(-50%, -50%);`"
                            :aria-label="(field.label || field.key || ('Field ' + (index + 1))) + ': ' + field.x + '% across, ' + field.y + '% down. Use arrow keys to move.'"
                            @pointerdown.prevent="startDrag($event, index)"
                            @click.stop="activeField = index"
                            @focus="activeField = index"
                            @keydown.arrow-left.prevent="nudge(index, $event.shiftKey ? -5 : -0.5, 0)"
                            @keydown.arrow-right.prevent="nudge(index, $event.shiftKey ? 5 : 0.5, 0)"
                            @keydown.arrow-up.prevent="nudge(index, 0, $event.shiftKey ? -5 : -0.5)"
                            @keydown.arrow-down.prevent="nudge(index, 0, $event.shiftKey ? 5 : 0.5)">
                        <span class="pointer-events-none whitespace-nowrap text-xs font-medium text-white drop-shadow-sm"
                              x-text="field.label || field.key || ('Field ' + (index + 1))"></span>
                    </button>
                </template>
            </div>

            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                Positions are measured from the top-left corner: 50% across and 50% down is the centre. Hold Shift with the arrow keys to move further.
            </p>

            @isset($template)
                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <x-ui.button variant="secondary" icon="beaker" :href="route('templates.test', $template)">Try on a photo</x-ui.button>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Save your changes first — the test uses the saved template.</p>
                </div>
            @endisset
        </x-ui.card>
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
            // Only the primary button / a single touch or pen contact starts a drag.
            if (event.button !== undefined && event.button !== 0) return;
            const rect = this.$refs.previewContainer.getBoundingClientRect();
            this.activeField = fieldIndex;
            // Keep receiving pointermove even if the finger/cursor leaves the handle or preview.
            try { event.currentTarget && event.currentTarget.setPointerCapture(event.pointerId); } catch (e) {}
            this.dragging = {
                fieldIndex,
                pointerId:    event.pointerId,
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
            if (this.dragging.pointerId !== undefined && event.pointerId !== this.dragging.pointerId) return;
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

        nudge(index, dx, dy) {
            const f = this.fields[index];
            if (!f) return;
            this.activeField = index;
            f.x = Math.round(Math.min(100, Math.max(0, (parseFloat(f.x) || 0) + dx)) * 10) / 10;
            f.y = Math.round(Math.min(100, Math.max(0, (parseFloat(f.y) || 0) + dy)) * 10) / 10;
        },
    };
}
</script>
@endpush
