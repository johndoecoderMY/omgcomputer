
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    @php
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title', 'OMG Computer')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pageTitle = $pageTitle !== '' ? $pageTitle : 'OMG Computer';
        $metaDescription = trim($__env->yieldContent(
            'meta_description',
            'OMG Computer provides computer repair, upgrades, custom PCs, and website design for home and business customers.',
        ));
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

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
     <meta name="theme-color" content="#ffffff">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>

    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link href="{{ asset('vendors/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
     <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
     <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet" id="user-style-default">
     <link rel="preload" href="{{ Vite::asset('node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
     @vite('resources/css/icons.css')
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        if (linkRTL) linkRTL.setAttribute('disabled', true);
        if (userLinkRTL) userLinkRTL.setAttribute('disabled', true);
      }
    </script>
  </head>