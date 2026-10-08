<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="/branding/favicon-32x32.png?v=1">
        <link rel="icon" type="image/png" sizes="16x16" href="/branding/favicon-16x16.png?v=1">
        <link rel="apple-touch-icon" href="/branding/apple-touch-icon.png?v=1">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:500,600,700,800&display=swap" rel="stylesheet" />

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
        @routes
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
        @inertia
    </body>
</html>
