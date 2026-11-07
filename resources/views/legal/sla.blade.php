<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Level Agreement - {{ config('app.name') }}</title>
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
            <h1 class="text-4xl font-bold mb-6">Service Level Agreement (SLA)</h1>
            <p class="text-gray-600 mb-8">Last updated: {{ date('F d, Y') }}</p>

            <div class="prose max-w-none">
                <h2 class="text-2xl font-bold mt-8 mb-4">1. Service Availability</h2>
                <p class="mb-4">{{ config('app.name') }} is committed to providing reliable service to our customers. We guarantee the following uptime levels:</p>

                <h3 class="text-xl font-semibold mt-6 mb-3">Uptime Guarantee</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li><strong>Free Plan:</strong> Best effort, no uptime guarantee</li>
                    <li><strong>Photographer Plan:</strong> 99.5% monthly uptime</li>
                    <li><strong>Videographer Plan:</strong> 99.9% monthly uptime</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Planned Maintenance</h3>
                <p class="mb-4">We reserve the right to perform scheduled maintenance with advance notice. Planned maintenance windows are excluded from uptime calculations. We will provide at least 48 hours notice for planned maintenance.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">2. Performance Standards</h2>

                <h3 class="text-xl font-semibold mt-6 mb-3">Page Load Times</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Portfolio pages: &lt; 2 seconds (95th percentile)</li>
                    <li>Dashboard and admin pages: &lt; 3 seconds (95th percentile)</li>
                    <li>Image loading: Optimized delivery via CDN</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Upload Speeds</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Photos: Upload processing within 30 seconds</li>
                    <li>Videos (Videographer Plan): Processing time varies by file size</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">3. Support Response Times</h2>

                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Plan</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Initial Response</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Support Channels</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Free</td>
                                <td class="border border-gray-300 px-4 py-2">5 business days</td>
                                <td class="border border-gray-300 px-4 py-2">Email only</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Photographer</td>
                                <td class="border border-gray-300 px-4 py-2">24 hours</td>
                                <td class="border border-gray-300 px-4 py-2">Email, Help Center</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">Videographer</td>
                                <td class="border border-gray-300 px-4 py-2">12 hours</td>
                                <td class="border border-gray-300 px-4 py-2">Email, Help Center, Priority Support</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="text-2xl font-bold mt-8 mb-4">4. Data Backup and Recovery</h2>

                <h3 class="text-xl font-semibold mt-6 mb-3">Backup Schedule</h3>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Database backups: Daily</li>
                    <li>File storage: Real-time replication across multiple availability zones</li>
                    <li>Backup retention: 30 days</li>
                </ul>

                <h3 class="text-xl font-semibold mt-6 mb-3">Disaster Recovery</h3>
                <p class="mb-4">Recovery Time Objective (RTO): 4 hours</p>
                <p class="mb-4">Recovery Point Objective (RPO): 24 hours</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">5. Security Measures</h2>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>SSL/TLS encryption for all data in transit</li>
                    <li>Encryption at rest for stored data</li>
                    <li>Regular security audits and vulnerability scanning</li>
                    <li>DDoS protection and mitigation</li>
                    <li>Access controls and authentication systems</li>
                    <li>Security incident response within 2 hours of detection</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">6. Service Credits</h2>
                <p class="mb-4">If we fail to meet our uptime guarantee, you may be eligible for service credits:</p>

                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left">Monthly Uptime</th>
                                <th class="border border-gray-300 px-4 py-2 text-left">Service Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">99.0% - 99.5%</td>
                                <td class="border border-gray-300 px-4 py-2">10% of monthly fee</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">95.0% - 98.9%</td>
                                <td class="border border-gray-300 px-4 py-2">25% of monthly fee</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2">&lt; 95.0%</td>
                                <td class="border border-gray-300 px-4 py-2">50% of monthly fee</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-xl font-semibold mt-6 mb-3">Claiming Service Credits</h3>
                <p class="mb-4">To claim service credits:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Submit a claim within 30 days of the incident</li>
                    <li>Provide details of the downtime experienced</li>
                    <li>We will review and process valid claims within 15 business days</li>
                    <li>Credits are applied to future invoices</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">7. Exclusions</h2>
                <p class="mb-4">The SLA does not apply to service unavailability caused by:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Scheduled maintenance (with advance notice)</li>
                    <li>Factors outside our reasonable control (force majeure)</li>
                    <li>Issues caused by your equipment, software, or internet connection</li>
                    <li>Violations of our Terms of Service</li>
                    <li>Third-party services or APIs beyond our control</li>
                    <li>DDoS attacks or other security incidents</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">8. Monitoring and Reporting</h2>
                <p class="mb-4">We continuously monitor our service performance and maintain a public status page showing:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Current system status</li>
                    <li>Scheduled maintenance windows</li>
                    <li>Historical uptime data</li>
                    <li>Incident reports and post-mortems</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">9. Payment Processing</h2>
                <p class="mb-4">For users selling through our platform:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Payment processing via Stripe (99.99% uptime SLA)</li>
                    <li>Payouts processed within 2-7 business days</li>
                    <li>Transaction fee: 5% + Stripe fees</li>
                    <li>Automatic retry for failed transactions</li>
                </ul>

                <h2 class="text-2xl font-bold mt-8 mb-4">10. Account Suspension</h2>
                <p class="mb-4">We reserve the right to suspend accounts that:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Violate our Terms of Service</li>
                    <li>Engage in fraudulent activity</li>
                    <li>Exceed fair use policies</li>
                    <li>Fail to pay subscription fees</li>
                </ul>
                <p class="mb-4">We will provide notice before suspension when possible, except in cases of suspected fraud or abuse.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">11. Service Modifications</h2>
                <p class="mb-4">We reserve the right to modify or discontinue features with 30 days notice. We will not reduce core functionality of paid plans without providing alternatives or refunds.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">12. Liability Limitations</h2>
                <p class="mb-4">Our total liability for any claims related to the Service is limited to the amount you paid in the 12 months preceding the claim. We are not liable for indirect, incidental, special, or consequential damages.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">13. Changes to SLA</h2>
                <p class="mb-4">We may update this SLA from time to time. We will notify paid subscribers of significant changes at least 30 days in advance. Continued use of the Service after changes constitutes acceptance.</p>

                <h2 class="text-2xl font-bold mt-8 mb-4">14. Contact for SLA Issues</h2>
                <p class="mb-4">For questions about this SLA or to report service issues:</p>
                <p class="mb-4">
                    Email: <a href="mailto:{{ 'sla@' . config('app.name') . '.com' }}" class="text-indigo-600 hover:underline">{{ 'sla@' . config('app.name') . '.com' }}</a><br>
                    Support: <a href="mailto:{{ 'support@' . config('app.name') . '.com' }}" class="text-indigo-600 hover:underline">{{ 'support@' . config('app.name') . '.com' }}</a>
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
