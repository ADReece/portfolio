@php
    $targetIp = config('custom_domains.target_ip');
    $cnameTarget = collect(config('custom_domains.cname_targets'))->first();
    $platformHost = parse_url(config('app.url'), PHP_URL_HOST);
@endphp

<x-app-layout title="Your domain">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Your domain"
                          description="Show your portfolio on your own domain, like yourname.com, instead of {{ $platformHost }}/{{ '@'.$user->username }}." />

        @if($limit === 0)
            <x-ui.alert tone="amber" title="Custom domains are included on paid plans">
                Upgrade to put your portfolio on your own domain.
                <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
            </x-ui.alert>
        @endif

        @foreach($domains as $domain)
            @php
                $hosts = $domain->hostnames();
                $live = $domain->isVerified() && $limit > 0;
            @endphp
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $domain->displayHostname($domain->primaryHostname()) }}</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @if($live)
                                Live. Visitors to {{ collect($hosts)->map(fn ($h) => $domain->displayHostname($h))->implode(' and ') }} see your portfolio.
                            @elseif($domain->status === 'failed')
                                Not connected. {{ $domain->failure_reason }}
                            @elseif($domain->isVerified())
                                Verified, but your plan no longer includes custom domains.
                            @else
                                Waiting for DNS. Add the records below, then check.
                            @endif
                        </p>
                        @if($domain->last_checked_at)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Last checked {{ $domain->last_checked_at->diffForHumans() }}.</p>
                        @endif
                    </div>
                    @if($live)
                        <x-ui.badge tone="green" icon="check">Live</x-ui.badge>
                    @elseif($domain->status === 'failed')
                        <x-ui.badge tone="red">Not connected</x-ui.badge>
                    @else
                        <x-ui.badge tone="amber">Pending</x-ui.badge>
                    @endif
                </div>

                @if($domain->failure_reason && ! $live)
                    <x-ui.alert tone="amber" title="What still needs fixing" class="mt-4">{{ $domain->failure_reason }}</x-ui.alert>
                @elseif($domain->failure_reason && $live)
                    <x-ui.alert tone="amber" title="Your DNS has changed" class="mt-4">
                        {{ $domain->failure_reason }} Fix it soon: if it isn't fixed within {{ config('custom_domains.grace_hours') }} hours, your domain will stop showing your portfolio.
                    </x-ui.alert>
                @endif

                <h3 class="mt-6 font-semibold text-gray-900 dark:text-gray-100">DNS records to add</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Add these where you bought your domain (your registrar or DNS provider). Changes can take up to a few hours to spread.</p>
                <div class="mt-3 overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th scope="col" class="px-3 py-2 font-medium">Type</th><th scope="col" class="px-3 py-2 font-medium">Name</th><th scope="col" class="px-3 py-2 font-medium">Value</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 font-mono text-gray-900 dark:divide-gray-700 dark:text-gray-100">
                            @unless($domain->ownership_trusted)
                                <tr><td class="px-3 py-2">TXT</td><td class="px-3 py-2 break-all">{{ $domain->txtRecordName() }}</td><td class="px-3 py-2 break-all">{{ $domain->verification_token }}</td></tr>
                            @endunless
                            @foreach($hosts as $host)
                                @if($host === $domain->hostname && $domain->isApex())
                                    <tr><td class="px-3 py-2">A</td><td class="px-3 py-2 break-all">{{ $host }} <span class="font-sans text-gray-500">(often written “@”)</span></td><td class="px-3 py-2">{{ $targetIp }}</td></tr>
                                @else
                                    <tr><td class="px-3 py-2">CNAME</td><td class="px-3 py-2 break-all">{{ $host }}</td><td class="px-3 py-2 break-all">{{ $cnameTarget }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    An A record to {{ $targetIp }} works for any of these names. Remove any other A or AAAA records for them, or visitors may reach the wrong server.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <form method="POST" action="{{ route('custom-domains.verify', $domain) }}">
                        @csrf
                        <x-ui.button type="submit" icon="arrow-path">{{ $live ? 'Check DNS again' : 'Check DNS now' }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('custom-domains.destroy', $domain) }}"
                          data-confirm="Your portfolio will go back to {{ $platformHost }}/{{ '@'.$user->username }}. Links to {{ $domain->displayHostname() }} will stop working."
                          data-confirm-title="Remove {{ $domain->displayHostname() }}?" data-confirm-button="Remove domain" data-confirm-danger>
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost">Remove</x-ui.button>
                    </form>
                </div>

                @if(substr_count($domain->hostname, '.') === 1)
                    <form method="POST" action="{{ route('custom-domains.update', $domain) }}" class="mt-5 space-y-3 border-t border-gray-200 pt-5 dark:border-gray-700"
                          x-data="{ www: {{ $domain->include_www ? 'true' : 'false' }} }">
                        @csrf
                        @method('PATCH')
                        <x-ui.checkbox name="include_www" id="include_www_{{ $domain->id }}" :checked="$domain->include_www" x-model="www"
                                       label="Also use www.{{ $domain->displayHostname() }}" description="Visitors who type www get your portfolio too." />
                        <div x-show="www">
                            <x-ui.checkbox name="primary_www" id="primary_www_{{ $domain->id }}" :checked="$domain->primary_www"
                                           label="Make www.{{ $domain->displayHostname() }} the main address" description="The other address redirects to it." />
                        </div>
                        <x-ui.button type="submit" variant="secondary" size="sm">Save</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
        @endforeach

        @if($limit > 0 && $domains->count() < $limit)
            <x-ui.card title="Connect a domain" description="Use a domain you already own. Buy one from any registrar if you don't have one yet.">
                <form method="POST" action="{{ route('custom-domains.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-64 flex-1">
                        <x-ui.input name="hostname" label="Domain" placeholder="yourname.com" autocomplete="off" autocapitalize="off" spellcheck="false" required />
                    </div>
                    <x-ui.button type="submit">Add domain</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
