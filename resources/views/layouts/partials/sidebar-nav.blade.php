{{--
    Main app navigation, shared by the desktop sidebar and the mobile drawer.
    Each item: [label, route, icon, active route pattern(s), extra].
--}}
@php
    $user = auth()->user();
    $canCustomize = $user->is_admin || $user->isFeatureOverrideActive() || $user->hasFeature('upload_logo');

    $sections = [
        null => [
            ['Dashboard', route('dashboard'), 'home', 'dashboard'],
            ['Collections', route('collections.index'), 'rectangle-stack', ['collections.*', 'sets.*']],
        ],
        'Portfolio' => array_values(array_filter([
            $user->username ? ['View portfolio', route('profile.view', $user->username), 'globe-alt', 'profile.view', ['external' => true]] : null,
            $canCustomize
                ? ['Customize', route('profile.customize'), 'paint-brush', 'profile.customize']
                : ['Customize', route('pricing'), 'paint-brush', null, ['badge' => 'Upgrade']],
            ['Your domain', route('custom-domains.index'), 'link', 'custom-domains.*'],
        ])),
        'Selling' => [
            ['Print products', route('profile.prodigi-products.edit'), 'shopping-bag', 'profile.prodigi-products.*'],
            ['Print templates', route('templates.index'), 'document-duplicate', 'templates.*'],
            ['Fonts', route('fonts.index'), 'language', 'fonts.*'],
        ],
        'Account' => [
            ['Settings', route('profile.edit'), 'user-circle', 'profile.edit'],
            ['Plan & billing', route('pricing'), 'credit-card', ['pricing', 'checkout']],
            ['Payouts', route('connect.stripe'), 'banknotes', 'connect.stripe'],
        ],
    ];

    if ($user->is_admin) {
        $sections['Admin'] = [
            ['Overview', route('admin.dashboard'), 'chart-bar', 'admin.dashboard'],
            ['Users', route('admin.users.index'), 'users', 'admin.users.*'],
            ['Plans', route('admin.plans.index'), 'squares-2x2', 'admin.plans.*'],
            ['Subscriptions', route('admin.subscriptions.index'), 'arrow-path', 'admin.subscriptions.*'],
            ['Orders', route('admin.orders.index'), 'receipt-percent', 'admin.orders.*'],
            ['Settings', route('admin.settings.index'), 'adjustments-horizontal', 'admin.settings.*'],
        ];
    }
@endphp

<nav aria-label="Main" class="flex flex-1 flex-col gap-6">
    @foreach($sections as $heading => $items)
        <div>
            @if($heading)
                <h2 class="px-3 pb-1 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">{{ $heading }}</h2>
            @endif
            <ul role="list" class="space-y-0.5">
                @foreach($items as $item)
                    @php
                        [$label, $href, $icon, $pattern] = $item;
                        $extra = $item[4] ?? [];
                        $active = $pattern && request()->routeIs(...(array) $pattern);
                    @endphp
                    <li>
                        <a href="{{ $href }}"
                           @if($active) aria-current="page" @endif
                           @if($extra['external'] ?? false) target="_blank" rel="noopener" @endif
                           @class([
                               'group flex items-center gap-3 rounded-control px-3 py-2 text-sm font-medium transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500',
                               'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-200' => $active,
                               'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' => ! $active,
                           ])>
                            <x-ui.icon :name="$icon" @class(['size-5', 'text-brand-600 dark:text-brand-300' => $active, 'text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300' => ! $active]) />
                            <span class="flex-1">{{ $label }}</span>
                            @if($extra['external'] ?? false)
                                <x-ui.icon name="arrow-top-right-on-square" class="size-4 text-gray-400" />
                                <span class="sr-only">(opens in a new tab)</span>
                            @endif
                            @isset($extra['badge'])
                                <x-ui.badge tone="brand">{{ $extra['badge'] }}</x-ui.badge>
                            @endisset
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
