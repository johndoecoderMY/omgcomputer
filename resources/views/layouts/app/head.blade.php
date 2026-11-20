  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    @php
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title', 'OMG Computer')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pageTitle = html_entity_decode($pageTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pageTitle = $pageTitle !== '' ? $pageTitle : 'OMG Computer';
    @endphp
    <title>{{ str_ends_with($pageTitle, '| OMG Computer') || $pageTitle === 'OMG Computer' ? $pageTitle : $pageTitle.' | OMG Computer' }}</title>

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/omg/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/omg/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/omg/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/omg/favicon.ico') }}">
    @if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'member']))
        <link rel="manifest" href="{{ route('staff-pwa.manifest') }}">
        <meta name="theme-color" content="#f34303">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="OMG Staff">
    @else
        <meta name="theme-color" content="#ffffff">
    @endif
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>

    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link href="{{ asset('vendors/prism/prism-okaidia.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/local-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-bs5/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
     <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
     <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet" id="user-style-default">
     @vite('resources/css/icons.css')
     <link href="{{ asset('vendors/select2/select2.min.css') }}" rel="stylesheet">
     <link href="{{ asset('vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet">
     <style>
         html[data-bs-theme="light"] .brand-logo-dark,
         html[data-bs-theme="dark"] .brand-logo-light {
             display: none;
         }

         .card-header {
             display: flex;
             flex-wrap: wrap;
             align-items: center;
             gap: .5rem;
         }

         .card-header > a.btn,
         .card-header > button.btn,
         .card-header > form,
         .card-header > .btn-group {
             margin-inline-start: auto;
         }

         .card-header > a.btn ~ a.btn,
         .card-header > a.btn ~ button.btn,
         .card-header > a.btn ~ form,
         .card-header > a.btn ~ .btn-group,
         .card-header > button.btn ~ a.btn,
         .card-header > button.btn ~ button.btn,
         .card-header > button.btn ~ form,
         .card-header > button.btn ~ .btn-group,
         .card-header > form ~ a.btn,
         .card-header > form ~ button.btn,
         .card-header > form ~ form,
         .card-header > form ~ .btn-group,
         .card-header > .btn-group ~ a.btn,
         .card-header > .btn-group ~ button.btn,
         .card-header > .btn-group ~ form,
         .card-header > .btn-group ~ .btn-group {
             margin-inline-start: 0;
         }

         .card-footer:has(> .btn, > form .btn, > .btn-group) {
             display: flex;
             flex-wrap: wrap;
             align-items: center;
             justify-content: flex-end;
             gap: .5rem;
         }

         .dropdown-menu-notification {
             border: 1px solid var(--falcon-border-color) !important;
         }

         @media (max-width: 575.98px) {
             .dropdown-menu-notification {
                 position: fixed !important;
                 top: 4.5rem !important;
                 right: .5rem !important;
                 left: auto !important;
                 width: min(22rem, calc(100% - 1rem));
                 max-width: 22rem;
             }
         }

         .modal-footer {
             justify-content: flex-end;
         }

         .navbar-vibrant .navbar-vertical-content .nav-link.dropdown-indicator.active {
             background-color: transparent !important;
             color: #fff !important;
             font-size: .95rem;
             font-weight: 700 !important;
         }

         .navbar-vibrant .navbar-vertical-content .nav-link[aria-current="page"] {
             background-color: transparent !important;
             color: #fff !important;
             font-size: .95rem;
             font-weight: 700 !important;
         }
     </style>
     @stack('styles')
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      var linkDefault = document.getElementById('style-default');
      var userLinkDefault = document.getElementById('user-style-default');
      var linkRTL = document.getElementById('style-rtl');
      var userLinkRTL = document.getElementById('user-style-rtl');
      if (isRTL) {
        document.querySelector('html').setAttribute('dir', 'rtl');
        if (linkRTL && userLinkRTL) {
          linkDefault.setAttribute('disabled', true);
          userLinkDefault.setAttribute('disabled', true);
        }
      } else {
        if (linkRTL) linkRTL.setAttribute('disabled', true);
        if (userLinkRTL) userLinkRTL.setAttribute('disabled', true);
      }
    </script>
  </head>