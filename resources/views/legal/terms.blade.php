<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-gray-900">
                        📸 {{ config('app.name') }}
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-4xl font-bold mb-6">Terms of Service</h1>
            <p class="text-gray-600 mb-8">Last updated: {{ date('F d, Y') }}</p>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-bold mt-8 mb-4">1. Acceptance of Terms</h2>
                <p class="mb-4">By accessing and using {{ config('app.name') }} ("Service"), you accept and agree to be bound by the terms and provision of this agreement.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">2. Use License</h2>
                <p class="mb-4">Permission is granted to temporarily use the Service for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Under this license you may not:</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Modify or copy the materials</li>
                    <li>Use the materials for any commercial purpose without a paid subscription</li>
                    <li>Attempt to decompile or reverse engineer any software contained on the Service</li>
                    <li>Remove any copyright or other proprietary notations from the materials</li>
                    <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">3. User Accounts</h2>
                <p class="mb-4">When you create an account with us, you must provide accurate, complete, and current information. You are responsible for safeguarding the password and for all activities that occur under your account.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">4. Subscriptions</h2>
                <p class="mb-4">Some parts of the Service are billed on a subscription basis. You will be billed in advance on a recurring and periodic basis. Billing cycles are set on a monthly basis.</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Subscription Plans:</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li><strong>Free Plan:</strong> Limited to 100 photos and 5 collections</li>
                    <li><strong>Photographer Plan ($9.99/mo):</strong> Unlimited photos and collections with additional features</li>
                    <li><strong>Videographer Plan ($14.99/mo):</strong> All Photographer features plus video upload capabilities</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">5. Refunds</h2>
                <p class="mb-4">Except when required by law, paid subscription fees are non-refundable. You can cancel your subscription at any time, but you will not receive a refund for the current billing period.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">6. Content</h2>
                <p class="mb-4">You retain all rights to the content you upload to the Service. By uploading content, you grant us a worldwide, non-exclusive, royalty-free license to use, reproduce, and display your content solely for the purpose of providing the Service.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">7. Prohibited Uses</h2>
                <p class="mb-4">You may not use the Service:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
                    <li>To violate any international, federal, provincial or state regulations, rules, laws, or local ordinances</li>
                    <li>To infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                    <li>To upload or transmit viruses or any other type of malicious code</li>
                    <li>To collect or track the personal information of others</li>
                    <li>To spam, phish, pharm, pretext, spider, crawl, or scrape</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">8. Intellectual Property</h2>
                <p class="mb-4">The Service and its original content (excluding user-uploaded content), features, and functionality are and will remain the exclusive property of {{ config('app.name') }} and its licensors.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">9. Termination</h2>
                <p class="mb-4">We may terminate or suspend your account immediately, without prior notice or liability, for any reason, including without limitation if you breach the Terms. Upon termination, your right to use the Service will immediately cease.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">10. Limitation of Liability</h2>
                <p class="mb-4">In no event shall {{ config('app.name') }}, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">11. Governing Law</h2>
                <p class="mb-4">These Terms shall be governed and construed in accordance with the laws of the jurisdiction in which {{ config('app.name') }} operates, without regard to its conflict of law provisions.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">12. Changes to Terms</h2>
                <p class="mb-4">We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will provide notice of any significant changes by posting the new Terms on this page.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">13. Contact Us</h2>
                <p class="mb-4">If you have any questions about these Terms, please contact us at:</p>
                <p class="mb-4">
                    Email: <a href="mailto:{{ 'legal@' . config('app.name') . '.com' }}" class="text-indigo-600 hover:underline">{{ 'legal@' . config('app.name') . '.com' }}</a>
                </p>
            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-gray-400 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="mt-4 space-x-4">
                <a href="{{ route('terms') }}" class="hover:text-white">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-white">Privacy</a>
                <a href="{{ route('sla') }}" class="hover:text-white">SLA</a>
            </div>
        </div>
    </footer>
    @livewireScriptConfig
</body>
</html>
