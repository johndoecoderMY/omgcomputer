  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title>@yield('title', 'OMG Computer') - OMG Computer</title>

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicons/favicon-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicons/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('assets/img/favicons/manifest.json') }}">
     <meta name="theme-color" content="#ffffff">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('vendors/simplebar/simplebar.min.js') }}"></script>

    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link href="{{ asset('vendors/prism/prism-okaidia.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('vendors/simplebar/simplebar.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/datatables.net-bs5/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
     <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet" id="style-default">
     <link href="{{ asset('assets/css/user.min.css') }}" rel="stylesheet" id="user-style-default">
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

         .modal-footer {
             justify-content: flex-end;
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