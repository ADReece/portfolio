{{--
    Global confirm and prompt dialogs, driven from JavaScript:
      window.confirmAction({ title, message, confirmText, isDanger, onConfirm })
      window.promptUser({ title, message, placeholder, inputType, confirmText, onConfirm })
    Forms can also use <form data-confirm="Message" data-confirm-title="…" data-confirm-danger>.
    Both trap focus, close on Escape or backdrop click, and return focus afterwards.
--}}

<!-- Confirm dialog -->
<div x-data="confirmModal"
     x-show="show"
     x-cloak
     x-on:keydown.escape.window="show && close()"
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-gray-950/50 transition-opacity"
         x-show="show"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         x-on:click="close()" aria-hidden="true"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message"
             x-show="show" x-trap.noscroll="show"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full overflow-hidden rounded-card bg-white text-left shadow-xl sm:max-w-lg dark:bg-gray-800">
            <div class="flex gap-4 px-5 pt-5 pb-4 sm:p-6">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full"
                      :class="isDanger ? 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400' : 'bg-brand-100 text-brand-600 dark:bg-brand-950 dark:text-brand-300'">
                    <x-ui.icon name="exclamation-triangle" class="size-5" x-show="isDanger" />
                    <x-ui.icon name="question-mark-circle" class="size-5" x-show="!isDanger" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="confirm-dialog-title" class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="title"></h2>
                    <p id="confirm-dialog-message" class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="message"></p>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 bg-gray-50 px-5 py-3 sm:flex-row sm:justify-end sm:px-6 dark:bg-gray-900/60">
                <x-ui.button variant="secondary" x-on:click="close()" x-ref="cancel"><span x-text="cancelText"></span></x-ui.button>
                <button type="button" x-on:click="confirm()"
                        :class="isDanger ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : 'bg-brand-600 hover:bg-brand-700 focus-visible:ring-brand-500'"
                        class="inline-flex items-center justify-center rounded-control px-4 py-2 text-sm font-semibold text-white shadow-xs focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                        x-text="confirmText"></button>
            </div>
        </div>
    </div>
</div>

<!-- Prompt dialog -->
<div x-data="promptModal"
     x-show="show"
     x-cloak
     x-on:keydown.escape.window="show && close()"
     class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-gray-950/50 transition-opacity"
         x-show="show"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         x-on:click="close()" aria-hidden="true"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div role="dialog" aria-modal="true" aria-labelledby="prompt-dialog-title"
             x-show="show" x-trap.noscroll="show"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full overflow-hidden rounded-card bg-white text-left shadow-xl sm:max-w-lg dark:bg-gray-800">
            <form x-on:submit.prevent="confirm()" novalidate>
                <div class="space-y-3 px-5 pt-5 pb-4 sm:p-6">
                    <h2 id="prompt-dialog-title" class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="title"></h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="message" x-show="message" id="prompt-dialog-message"></p>
                    <div>
                        <label for="prompt-dialog-input" class="sr-only" x-text="title"></label>
                        <input id="prompt-dialog-input" x-ref="promptInput" x-model="inputValue" :type="inputType" :placeholder="placeholder"
                               aria-describedby="prompt-dialog-message prompt-dialog-error" :aria-invalid="error ? 'true' : 'false'"
                               class="block w-full rounded-control border-gray-300 shadow-xs focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <p id="prompt-dialog-error" class="mt-1 text-sm text-red-700 dark:text-red-400" x-text="error" x-show="error" role="alert"></p>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 bg-gray-50 px-5 py-3 sm:flex-row sm:justify-end sm:px-6 dark:bg-gray-900/60">
                    <x-ui.button variant="secondary" x-on:click="close()"><span x-text="cancelText"></span></x-ui.button>
                    <x-ui.button type="submit"><span x-text="confirmText"></span></x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
