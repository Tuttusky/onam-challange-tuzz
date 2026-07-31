<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $seo['theme_color'] ?? '#6366f1' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo['title'] ?? config('app.name') }}</title>

    @if(!empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif

    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif

    @if(!empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif

    @if(!empty($seo['canonical_url']))
        <link rel="canonical" href="{{ $seo['canonical_url'] }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    @if(!empty($seo['og_title']))
        <meta property="og:title" content="{{ $seo['og_title'] }}">
    @endif
    @if(!empty($seo['og_description']))
        <meta property="og:description" content="{{ $seo['og_description'] }}">
    @endif
    @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif

    {{-- Twitter --}}
    @if(!empty($seo['twitter_card']))
        <meta name="twitter:card" content="{{ $seo['twitter_card'] }}">
    @endif
    @if(!empty($seo['og_title']))
        <meta name="twitter:title" content="{{ $seo['og_title'] }}">
    @endif
    @if(!empty($seo['og_description']))
        <meta name="twitter:description" content="{{ $seo['og_description'] }}">
    @endif
    @if(!empty($seo['og_image']))
        <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    @endif

    {{-- Verification --}}
    @if(!empty($seo['google_verification']))
        <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
    @endif
    @if(!empty($seo['bing_verification']))
        <meta name="msvalidate.01" content="{{ $seo['bing_verification'] }}">
    @endif
    @if(!empty($seo['facebook_verification']))
        <meta name="facebook-domain-verification" content="{{ $seo['facebook_verification'] }}">
    @endif

    @if(!empty($seo['schema_markup']))
        <script type="application/ld+json">{!! json_encode($seo['schema_markup'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ $seo['og_image'] ?? '/images/branding/icon-192.png' }}">

    {{-- Analytics head scripts --}}
    @if(!empty($analytics['google_analytics_id']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics['google_analytics_id'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $analytics['google_analytics_id'] }}');
        </script>
    @endif

    @if(!empty($analytics['facebook_pixel_id']))
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $analytics['facebook_pixel_id'] }}');
            fbq('track', 'PageView');
        </script>
    @endif

    @if(!empty($analytics['custom_head_scripts']))
        {!! $analytics['custom_head_scripts'] !!}
    @endif

    @vite(['resources/css/spa.css', 'resources/js/spa/main.js'])

    <script>
        window.__APP_SEO__ = @json($seo ?? []);
    </script>
</head>
<body>
    @if(!empty($analytics['facebook_pixel_id']))
        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $analytics['facebook_pixel_id'] }}&ev=PageView&noscript=1" alt="" />
        </noscript>
    @endif

    <div id="app"></div>

    @if(!empty($analytics['custom_body_scripts']))
        {!! $analytics['custom_body_scripts'] !!}
    @endif
</body>
</html>
