
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    @php
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title', 'OMG Computer')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pageTitle = html_entity_decode($pageTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pageTitle = $pageTitle !== '' ? __($pageTitle) : 'OMG Computer';
        $metaDescription = __(trim($__env->yieldContent(
            'meta_description',
            'OMG Computer provides computer repair, upgrades, custom PCs, and website design for home and business customers.',
        )));
        $canonicalUrl = trim($__env->yieldContent('canonical_url', url()->current()));
        $socialImage = trim($__env->yieldContent('og_image', asset('assets/img/omg/hero-technician.jpg')));
        $socialImageAlt = trim($__env->yieldContent('og_image_alt', 'OMG Computer providing computer repair and technology support.'));
    @endphp
    <title>{{ str_ends_with($pageTitle, '| OMG Computer') || $pageTitle === 'OMG Computer' ? $pageTitle : $pageTitle.' | OMG Computer' }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="@yield('meta_robots', 'index,follow')">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', $pageTitle)">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $socialImage }}">
    <meta property="og:image:alt" content="{{ $socialImageAlt }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $pageTitle)">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $socialImage }}">
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/omg/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/omg/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/omg/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/omg/favicon.ico') }}">
     <meta name="theme-color" content="#ffffff">
    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link href="{{ asset('assets/css/local-fonts.css') }}" rel="stylesheet">
     <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
     <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet" id="user-style-default">
     @if (file_exists(public_path('hot')))
         @vite('resources/css/icons.css')
     @else
         <link href="{{ Vite::asset('resources/css/icons.css') }}" rel="stylesheet" media="print" onload="this.media='all'">
         <noscript><link href="{{ Vite::asset('resources/css/icons.css') }}" rel="stylesheet"></noscript>
     @endif
     @stack('styles')
  </head>