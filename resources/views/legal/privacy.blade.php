<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <nav class="bg-white shadow-xs">
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
            <h1 class="text-4xl font-bold mb-6">Privacy Policy</h1>
            <p class="text-gray-600 mb-8">Last updated: {{ date('F d, Y') }}</p>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-bold mt-8 mb-4">1. Information We Collect</h2>

                <h3 class="text-xl font-semibold mt-6 mb-3">Personal Information</h3>
                <p class="mb-4">When you register for an account, we collect:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Name and username</li>
                    <li>Email address</li>
                    <li>Password (encrypted)</li>
                    <li>Payment information (processed securely by Stripe)</li>
                    <li>Profile information (bio, social media links, etc.)</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Content You Upload</h3>
                <p class="mb-4">We store and process:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Photos and videos you upload</li>
                    <li>Collection and gallery information</li>
                    <li>Metadata associated with your uploads</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Usage Information</h3>
                <p class="mb-4">We automatically collect:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>IP address and device information</li>
                    <li>Browser type and version</li>
                    <li>Pages visited and features used</li>
                    <li>Time and date of visits</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">2. How We Use Your Information</h2>
                <p class="mb-4">We use the collected information to:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Provide, maintain, and improve our services</li>
                    <li>Process payments and manage subscriptions</li>
                    <li>Send transactional emails and service updates</li>
                    <li>Respond to customer support requests</li>
                    <li>Detect and prevent fraud or abuse</li>
                    <li>Comply with legal obligations</li>
                    <li>Analyze usage patterns to improve user experience</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">3. Information Sharing and Disclosure</h2>
                <p class="mb-4">We do not sell your personal information. We may share your information with:</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Service Providers</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li><strong>Stripe:</strong> For payment processing</li>
                    <li><strong>AWS:</strong> For secure cloud storage</li>
                    <li><strong>Email service providers:</strong> For transactional emails</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Legal Requirements</h3>
                <p class="mb-4">We may disclose your information if required by law or to:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Comply with legal process</li>
                    <li>Protect our rights or property</li>
                    <li>Prevent fraud or security issues</li>
                    <li>Protect the safety of users or the public</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">4. Data Security</h2>
                <p class="mb-4">We implement industry-standard security measures to protect your data:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Encrypted data transmission (HTTPS/TLS)</li>
                    <li>Encrypted password storage</li>
                    <li>Secure cloud infrastructure</li>
                    <li>Regular security audits and updates</li>
                    <li>Access controls and authentication</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">5. Your Rights and Choices</h2>

                <h3 class="text-xl font-semibold mt-6 mb-3">Access and Correction</h3>
                <p class="mb-4">You can access and update your account information at any time through your account settings.</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Data Deletion</h3>
                <p class="mb-4">You can request deletion of your account and associated data by contacting us. Please note that some information may be retained for legal or legitimate business purposes.</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Data Portability</h3>
                <p class="mb-4">You can download your content at any time through the Service.</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Marketing Communications</h3>
                <p class="mb-4">You can opt out of marketing emails by clicking the unsubscribe link in any marketing email.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">6. Cookies and Tracking</h2>
                <p class="mb-4">We use cookies and similar tracking technologies to:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Maintain your session</li>
                    <li>Remember your preferences</li>
                    <li>Analyze site usage</li>
                    <li>Improve our services</li>
                </ul>
                <p class="mb-4">You can control cookie preferences through your browser settings.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">7. Data Retention</h2>
                <p class="mb-4">We retain your information for as long as your account is active or as needed to provide services. After account deletion, we may retain certain information as required by law or for legitimate business purposes.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">8. International Data Transfers</h2>
                <p class="mb-4">Your information may be transferred to and processed in countries other than your country of residence. We ensure appropriate safeguards are in place to protect your data.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">9. Children's Privacy</h2>
                <p class="mb-4">Our Service is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">10. Changes to This Policy</h2>
                <p class="mb-4">We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the new policy on this page and updating the "Last updated" date.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">11. GDPR Compliance</h2>
                <p class="mb-4">If you are in the European Economic Area (EEA), you have additional rights under GDPR, including:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Right to access your data</li>
                    <li>Right to rectification</li>
                    <li>Right to erasure ("right to be forgotten")</li>
                    <li>Right to restrict processing</li>
                    <li>Right to data portability</li>
                    <li>Right to object to processing</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">12. California Privacy Rights</h2>
                <p class="mb-4">If you are a California resident, you have rights under the California Consumer Privacy Act (CCPA), including the right to know what personal information we collect and how it is used.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">13. Contact Us</h2>
                <p class="mb-4">If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                <p class="mb-4">
                    Email: <a href="mailto:{{ 'privacy@' . config('app.name') . '.com' }}" class="text-indigo-600 hover:underline">{{ 'privacy@' . config('app.name') . '.com' }}</a>
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
