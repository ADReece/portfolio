{{--
    Loads only the web font the photographer chose for their portfolio
    (previously all eight families were loaded on every public page).
--}}
@php
    $bunnyFamilies = [
        'nunito' => 'nunito:400,600,700',
        'inter' => 'inter:400,500,600,700',
        'roboto' => 'roboto:400,500,700',
        'open-sans' => 'open-sans:400,600,700',
        'lato' => 'lato:400,700',
        'montserrat' => 'montserrat:400,500,600,700',
        'playfair' => 'playfair-display:400,700',
        'merriweather' => 'merriweather:400,700',
    ];
    $chosenFont = $bunnyFamilies[$user->portfolio_font ?? ''] ?? null;
@endphp
@if($chosenFont)
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family={{ $chosenFont }}&display=swap" rel="stylesheet">
@endif
