<?php

/*
 * Homepage showcases and the sample portfolios they link to (/examples/{slug}).
 * Photos are keys in resources/marketing/images.json (see
 * scripts/marketing-images.sh): "{slug}-1" … "{slug}-6".
 * The samples are clearly labelled as samples, with no invented people.
 */
return [
    'showcases' => [
        'weddings' => [
            'title' => 'Weddings',
            'blurb' => 'Ceremonies, receptions and quiet moments in between.',
            'photos' => 6,
        ],
        'portraits' => [
            'title' => 'Portraits',
            'blurb' => 'Headshots, families and personal branding.',
            'photos' => 6,
        ],
        'landscapes' => [
            'title' => 'Landscapes',
            'blurb' => 'Mountains, coastlines and big skies.',
            'photos' => 6,
        ],
        'events' => [
            'title' => 'Events',
            'blurb' => 'Concerts, parties and conferences.',
            'photos' => 6,
        ],
        'film' => [
            'title' => 'Film & video',
            'blurb' => 'Stills and films side by side: video galleries are on the Videographer plan.',
            'photos' => 6,
        ],
    ],
];
