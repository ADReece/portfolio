@if(config('app.debug') && request()->boolean('debug_theme') && isset($user) && method_exists($user,'canUseCustomizations'))
    @php
        $apply = $user->canUseCustomizations();
    @endphp
    <div class="fixed left-4 bottom-4 z-50 text-[10px] px-2 py-1 rounded-sm bg-black/70 text-white shadow-sm">
        font={{ $user->portfolio_font ?? 'system' }} theme={{ $user->portfolio_theme ?? 'auto' }} accent={{ $user->portfolio_accent_color ?? '#4F46E5' }} override={{ $user->isFeatureOverrideActive() ? 'yes':'no' }} customizations={{ $apply ? 'on':'off' }}
    </div>
@endif

