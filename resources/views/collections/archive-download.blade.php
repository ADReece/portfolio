<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 py-12 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-8 text-center">
                    <div class="inline-block p-4 bg-white rounded-full mb-4">
                        <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">Your Download is Ready!</h1>
                    <p class="text-indigo-100">{{ $collection->name }}</p>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <!-- Archive Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Archive Details</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Collection:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $collection->name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total Photos:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $photoCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Format:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">High-Resolution ZIP</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expiration Timer -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6"
                         x-data="countdownTimer('{{ $expiresAt }}')"
                         x-init="startCountdown()">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Download Expires In:</h3>
                                <template x-if="!expired">
                                    <div class="flex gap-4 text-center">
                                        <div class="flex-1">
                                            <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100" x-text="hours"></div>
                                            <div class="text-xs text-yellow-700 dark:text-yellow-300">Hours</div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100" x-text="minutes"></div>
                                            <div class="text-xs text-yellow-700 dark:text-yellow-300">Minutes</div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100" x-text="seconds"></div>
                                            <div class="text-xs text-yellow-700 dark:text-yellow-300">Seconds</div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="expired">
                                    <div class="text-red-600 dark:text-red-400 font-semibold">
                                        ⚠️ This download link has expired
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Download Button -->
                    <div x-data="{ expired: false }"
                         x-init="setTimeout(() => { if (new Date() > new Date('{{ $expiresAt }}')) { expired = true; } }, 1000)">
                        <template x-if="!expired">
                            <a href="{{ $downloadUrl }}"
                               class="block w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center py-4 px-6 rounded-lg font-semibold text-lg shadow-lg transform transition hover:scale-105">
                                <svg class="inline-block w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Archive
                            </a>
                        </template>
                        <template x-if="expired">
                            <div class="block w-full bg-gray-400 text-white text-center py-4 px-6 rounded-lg font-semibold text-lg cursor-not-allowed">
                                Download Link Expired
                            </div>
                        </template>
                    </div>

                    <!-- Info -->
                    <div class="mt-6 text-sm text-gray-600 dark:text-gray-400 text-center">
                        <p>💡 Once downloaded, you'll have unlimited access to your photos.</p>
                        <p class="mt-2">The download is a ZIP file containing all high-resolution images.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function countdownTimer(expiresAt) {
            return {
                hours: 0,
                minutes: 0,
                seconds: 0,
                expired: false,
                interval: null,

                startCountdown() {
                    this.updateCountdown();
                    this.interval = setInterval(() => {
                        this.updateCountdown();
                    }, 1000);
                },

                updateCountdown() {
                    const now = new Date().getTime();
                    const expiry = new Date(expiresAt).getTime();
                    const distance = expiry - now;

                    if (distance <= 0) {
                        this.expired = true;
                        this.hours = 0;
                        this.minutes = 0;
                        this.seconds = 0;
                        if (this.interval) {
                            clearInterval(this.interval);
                        }
                        return;
                    }

                    this.hours = Math.floor(distance / (1000 * 60 * 60));
                    this.minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    this.seconds = Math.floor((distance % (1000 * 60)) / 1000);
                }
            }
        }
    </script>
</x-guest-layout>

