<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $meta = $page['props']['meta'] ?? null;
            $title = $meta['title'] ?? config('app.name', 'TapH5 Games') . ' — Free Online HTML5 Games';
            $description = $meta['description'] ?? 'Play free HTML5 games instantly on TapH5 Games with no downloads.';
            $canonical = $meta['canonical'] ?? url()->current();
            $ogType = $meta['og_type'] ?? 'website';
            $ogTitle = $meta['og_title'] ?? $title;
            $ogDescription = $meta['og_description'] ?? $description;
            $ogImage = $meta['og_image'] ?? url('/images/thumbnails/neon-pulse.svg');
            $twitterCard = $meta['twitter_card'] ?? 'summary_large_image';
            $schema = $meta['schema'] ?? null;
        @endphp

        <title inertia>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonical }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:site_name" content="{{ config('app.name', 'TapH5 Games') }}">

        <!-- Twitter -->
        <meta name="twitter:card" content="{{ $twitterCard }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <!-- Theme Color & Mobile App -->
        <meta name="theme-color" content="#090d16">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        @if($schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
            </script>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen selection:bg-emerald-500 selection:text-slate-950">
        @inertia
    </body>
</html>
