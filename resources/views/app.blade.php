<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $seo = app(\App\Support\Seo::class);
        @endphp
        <title inertia>{{ $seo->fullTitle() }}</title>
        @if($seo->description)
            <meta name="description" content="{{ $seo->description }}">
        @endif
        @if($seo->ogDescriptionValue())
            <meta property="og:description" content="{{ $seo->ogDescriptionValue() }}">
        @endif
        @if($seo->twitterDescriptionValue())
            <meta name="twitter:description" content="{{ $seo->twitterDescriptionValue() }}">
        @endif
        <meta name="robots" content="{{ $seo->robotsContent() }}">
        <link rel="canonical" href="{{ $seo->canonicalUrl() }}">
        <meta property="og:url" content="{{ $seo->canonicalUrl() }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $seo->ogTitleValue() }}">
        <meta property="og:type" content="{{ $seo->type }}">
        <meta property="og:locale" content="ro_RO">
        <meta property="og:image" content="{{ $seo->imageUrl() }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo->twitterTitleValue() }}">
        <meta name="twitter:image" content="{{ $seo->twitterImageUrl() }}">
        @foreach ($seo->jsonLd as $block)
            <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endforeach

        <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon-32x32.png?v=1">
        <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon-16x16.png?v=1">
        <link rel="apple-touch-icon" href="/branding/apple-touch-icon.png?v=1">

        <!-- Fonts -->
        <!-- Fonts: incarcate asincron pentru a nu bloca randarea -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:500,600,700,800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:500,600,700,800&display=swap"></noscript>

        <style>
            .seo-fallback { max-width: 72rem; margin: 0 auto; padding: 4rem 1.5rem; color: #03172a; }
            .seo-fallback h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; line-height: 1.1; letter-spacing: -0.03em; }
            .seo-fallback p { margin-top: 1.25rem; max-width: 52rem; color: #334155; line-height: 1.7; }
            .seo-fallback h2 { margin-top: 2rem; font-size: 1.125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
            .seo-fallback nav { display: flex; flex-wrap: wrap; gap: 0.75rem 1.5rem; margin-top: 0.75rem; }
            .seo-fallback a { color: #03172a; text-decoration: underline; text-underline-offset: 3px; }
            .seo-fallback a:hover { color: #f59e0b; }
        </style>

        @php
            $gaId = \App\Models\Setting::get('google_analytics_id');
            $gtmId = \App\Models\Setting::get('google_tag_manager_id');
            $pixelId = \App\Models\Setting::get('meta_pixel_id');
        @endphp

        @if($gtmId)
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
            <!-- End Google Tag Manager -->
        @endif

        @if($gaId)
            <!-- Google Analytics GA4 -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '{{ $gaId }}');
            </script>
        @endif

        @if($pixelId)
            <!-- Meta Pixel Code -->
            <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $pixelId }}');
            fbq('track', 'PageView');
            </script>
        @endif

        <!-- Scripts -->
        @if(auth()->check())
            @routes
        @else
            @routes('public')
        @endif
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if($gtmId)
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
        @endif
        {{-- echivalentul directivei @inertia, cu HTML de rezervă în interior pentru crawlerele fără JS --}}
        @php
            if (!isset($__inertiaSsrDispatched)) {
                $__inertiaSsrDispatched = true;
                $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page);
            }
        @endphp
        @if($__inertiaSsrResponse ?? null)
            {!! $__inertiaSsrResponse->body !!}
        @else
            <div id="app" data-page="{{ json_encode($page) }}">@if($seo->description)@include('partials.seo-fallback')@endif</div>
        @endif
    </body>
</html>
