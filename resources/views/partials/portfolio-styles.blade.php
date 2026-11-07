@php
    /** @var \App\Models\User|null $user */
    $can = isset($user) && method_exists($user,'canUseCustomizations') && $user->canUseCustomizations();
@endphp
@if($can)
    @php
        $fontKey = $user->portfolio_font ?? 'system';
        $theme   = $user->portfolio_theme ?? 'auto';
        $accent  = $user->portfolio_accent_color ?? '#4F46E5';
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
        @php
            // Theme rules for one colour scheme. Elements inside .portfolio-neutral
            // (checkout forms, order summaries, dialogs) keep the standard readable
            // styling instead of the photographer's colours.
            $not = ':not(.portfolio-neutral, .portfolio-neutral *)';
            $schemeCss = function (string $s) use ($not) {
                $isDark = $s === 'dark';
                return "
                    body { background: var(--portfolio-bg-$s) !important; color: var(--portfolio-text-$s) !important; }
                    header.portfolio-header, main.portfolio-content { background: var(--portfolio-bg-$s) !important; color: var(--portfolio-text-$s) !important; }
                    :is(header.portfolio-header, main.portfolio-content) :is(h1,h2,h3,h4,h5,h6)$not { color: var(--portfolio-heading-$s) !important; }
                    main.portfolio-content :is(input[type=text],input[type=password],input[type=email],textarea,select)$not {
                        background: ".($isDark ? 'rgba(255,255,255,0.1)' : '#fff')." !important;
                        color: var(--portfolio-text-$s) !important;
                        border-color: ".($isDark ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)')." !important;
                    }
                    main.portfolio-content label$not { color: var(--portfolio-text-$s) !important; }
                ";
            };
        @endphp
        @if($theme === 'light')
            html { color-scheme: light; }
            {!! $schemeCss('light') !!}
        @elseif($theme === 'dark')
            html { color-scheme: dark; }
            {!! $schemeCss('dark') !!}
        @else
            @media (prefers-color-scheme: light) { {!! $schemeCss('light') !!} }
            @media (prefers-color-scheme: dark) { {!! $schemeCss('dark') !!} }
        @endif
    </style>
@endif
