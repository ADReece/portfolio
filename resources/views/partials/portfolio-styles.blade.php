@php
    /** @var \App\Models\User|null $user */
    $can = isset($user) && method_exists($user,'canUseCustomizations') && $user->canUseCustomizations();
@endphp
@if($can)
    @php
        $fontKey = $user->portfolio_font ?? 'system';
        $theme   = $user->portfolio_theme ?? 'auto';
        $accent  = $user->portfolio_accent_color ?? '#6366F1';
        $bg      = $user->portfolio_background_color ?: null;
        $text    = $user->portfolio_text_color ?: null;
        $heading = $user->portfolio_heading_color ?: null;
        $fontMap = [
            'system' => 'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
            'nunito' => '"Nunito",sans-serif',
            'inter' => '"Inter",sans-serif',
            'roboto' => '"Roboto",sans-serif',
            'open-sans' => '"Open Sans",sans-serif',
            'lato' => '"Lato",sans-serif',
            'montserrat' => '"Montserrat",sans-serif',
            'playfair' => '"Playfair Display",serif',
            'merriweather' => '"Merriweather",serif',
        ];
        $fontFamily = $fontMap[$fontKey] ?? $fontMap['system'];
        $lightBg = $bg ?: '#F3F4F6';
        $lightText = $text ?: '#1F2937';
        $lightHeading = $heading ?: '#111827';
        $darkBg = $bg ?: '#111827';
        $darkText = $text ?: '#F3F4F6';
        $darkHeading = $heading ?: '#F9FAFB';
        // Accent RGB + brightness for overlays
        $hex = ltrim($accent,'#');
        if(strlen($hex)===3){ $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
        $brightness = ($r*299 + $g*587 + $b*114)/1000;
        $overlayBase = $brightness > 180 ? '0,0,0' : "$r,$g,$b";
    @endphp
    <style>
        :root {
            --portfolio-font: {{ $fontFamily }};
            --portfolio-accent: {{ $accent }};
            --portfolio-accent-rgb: {{ $r }},{{ $g }},{{ $b }};
            --portfolio-accent-overlay-start: rgba({{ $overlayBase }},0.70);
            --portfolio-accent-overlay-mid: rgba({{ $overlayBase }},0.40);
            --portfolio-accent-overlay-end: rgba({{ $overlayBase }},0.05);
            --portfolio-bg-light: {{ $lightBg }};
            --portfolio-text-light: {{ $lightText }};
            --portfolio-heading-light: {{ $lightHeading }};
            --portfolio-bg-dark: {{ $darkBg }};
            --portfolio-text-dark: {{ $darkText }};
            --portfolio-heading-dark: {{ $darkHeading }};
        }
        /* Font cascade */
        body, body * { font-family: var(--portfolio-font) !important; }
        /* Accent utilities */
        .portfolio-accent, a.portfolio-link, a[href^="/@"]:not(.no-accent) { color: var(--portfolio-accent) !important; }
        .portfolio-button, button.portfolio-button, .portfolio-bg-accent { background: var(--portfolio-accent) !important; border-color: var(--portfolio-accent) !important; color:#fff !important; }
        .portfolio-button:hover, button.portfolio-button:hover { filter: brightness(.92); }
        /* Primary actions take the accent colour. Opt-in (submit buttons and
           .portfolio-button) so links, icon buttons and dismiss controls keep
           their own styling. */
        main.portfolio-content button[type="submit"]:not(.no-accent) {
            background: var(--portfolio-accent) !important;
            border-color: var(--portfolio-accent) !important;
            color: #fff !important;
        }
        main.portfolio-content button[type="submit"]:not(.no-accent):hover {
            filter: brightness(.92);
        }
        /* Hero overlay helper */
        .portfolio-hero-overlay {
            background: linear-gradient(180deg,var(--portfolio-accent-overlay-start),var(--portfolio-accent-overlay-mid),var(--portfolio-accent-overlay-end));
        }
        @if($theme === 'light')
            html { color-scheme: light; }
            body { background: var(--portfolio-bg-light) !important; color: var(--portfolio-text-light) !important; }
            header.portfolio-header, main.portfolio-content { background: var(--portfolio-bg-light) !important; color: var(--portfolio-text-light) !important; }
            header.portfolio-header h1, header.portfolio-header h2, header.portfolio-header h3, header.portfolio-header h4, header.portfolio-header h5, header.portfolio-header h6,
            main.portfolio-content h1, main.portfolio-content h2, main.portfolio-content h3, main.portfolio-content h4, main.portfolio-content h5, main.portfolio-content h6 { color: var(--portfolio-heading-light) !important; }
            /* Input fields */
            main.portfolio-content input[type="text"], main.portfolio-content input[type="password"], main.portfolio-content input[type="email"], main.portfolio-content textarea, main.portfolio-content select {
                background: #fff !important;
                color: var(--portfolio-text-light) !important;
                border-color: rgba(0,0,0,0.2) !important;
            }
            main.portfolio-content label { color: var(--portfolio-text-light) !important; }
        @elseif($theme === 'dark')
            html { color-scheme: dark; }
            body { background: var(--portfolio-bg-dark) !important; color: var(--portfolio-text-dark) !important; }
            header.portfolio-header, main.portfolio-content { background: var(--portfolio-bg-dark) !important; color: var(--portfolio-text-dark) !important; }
            header.portfolio-header h1, header.portfolio-header h2, header.portfolio-header h3, header.portfolio-header h4, header.portfolio-header h5, header.portfolio-header h6,
            main.portfolio-content h1, main.portfolio-content h2, main.portfolio-content h3, main.portfolio-content h4, main.portfolio-content h5, main.portfolio-content h6 { color: var(--portfolio-heading-dark) !important; }
            /* Input fields */
            main.portfolio-content input[type="text"], main.portfolio-content input[type="password"], main.portfolio-content input[type="email"], main.portfolio-content textarea, main.portfolio-content select {
                background: rgba(255,255,255,0.1) !important;
                color: var(--portfolio-text-dark) !important;
                border-color: rgba(255,255,255,0.2) !important;
            }
            main.portfolio-content label { color: var(--portfolio-text-dark) !important; }
        @else
            /* Auto theme - respect system preference with custom or default colors */
            @media (prefers-color-scheme: light){
                body { background: var(--portfolio-bg-light) !important; color: var(--portfolio-text-light) !important; }
                header.portfolio-header, main.portfolio-content { background: var(--portfolio-bg-light) !important; color: var(--portfolio-text-light) !important; }
                header.portfolio-header h1, header.portfolio-header h2, header.portfolio-header h3, header.portfolio-header h4, header.portfolio-header h5, header.portfolio-header h6,
                main.portfolio-content h1, main.portfolio-content h2, main.portfolio-content h3, main.portfolio-content h4, main.portfolio-content h5, main.portfolio-content h6 { color: var(--portfolio-heading-light) !important; }
                main.portfolio-content input[type="text"], main.portfolio-content input[type="password"], main.portfolio-content input[type="email"], main.portfolio-content textarea, main.portfolio-content select {
                    background: #fff !important;
                    color: var(--portfolio-text-light) !important;
                    border-color: rgba(0,0,0,0.2) !important;
                }
                main.portfolio-content label { color: var(--portfolio-text-light) !important; }
            }
            @media (prefers-color-scheme: dark){
                body { background: var(--portfolio-bg-dark) !important; color: var(--portfolio-text-dark) !important; }
                header.portfolio-header, main.portfolio-content { background: var(--portfolio-bg-dark) !important; color: var(--portfolio-text-dark) !important; }
                header.portfolio-header h1, header.portfolio-header h2, header.portfolio-header h3, header.portfolio-header h4, header.portfolio-header h5, header.portfolio-header h6,
                main.portfolio-content h1, main.portfolio-content h2, main.portfolio-content h3, main.portfolio-content h4, main.portfolio-content h5, main.portfolio-content h6 { color: var(--portfolio-heading-dark) !important; }
                main.portfolio-content input[type="text"], main.portfolio-content input[type="password"], main.portfolio-content input[type="email"], main.portfolio-content textarea, main.portfolio-content select {
                    background: rgba(255,255,255,0.1) !important;
                    color: var(--portfolio-text-dark) !important;
                    border-color: rgba(255,255,255,0.2) !important;
                }
                main.portfolio-content label { color: var(--portfolio-text-dark) !important; }
            }
        @endif
    </style>
    <!-- customization-debug: applied theme={{ $theme }} font={{ $fontKey }} accent={{ $accent }} bg={{ $bg ?? 'default' }} text={{ $text ?? 'default' }} heading={{ $heading ?? 'default' }} override={{ $user->isFeatureOverrideActive() ? 'yes':'no' }} lightBg={{ $lightBg }} darkBg={{ $darkBg }} -->
@endif
