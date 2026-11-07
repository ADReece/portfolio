{{--
    Title, description and link-preview tags (Open Graph / Twitter) so shared
    portfolio and gallery links show a name and image in messages and social apps.
    @include('partials.share-meta', ['title' => …, 'description' => …, 'image' => …, 'noindex' => bool])
--}}
@php
    $metaTitle = $title ?? config('app.name');
    $metaDescription = isset($description) ? \Illuminate\Support\Str::limit(strip_tags($description), 160) : null;
@endphp
<title>{{ $metaTitle }}</title>
@if($metaDescription)<meta name="description" content="{{ $metaDescription }}">@endif
@if(!empty($noindex))<meta name="robots" content="noindex, nofollow">@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:url" content="{{ url()->current() }}">
@if($metaDescription)<meta property="og:description" content="{{ $metaDescription }}">@endif
@if(!empty($image))
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif
