<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Share Fair</title>
<!-- General CSS Files -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/app.min.css') }}">
<link rel="stylesheet" href="{{ asset('backend-assets/bundles/bootstrap-social/bootstrap-social.css') }}">
<!-- Template CSS -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('backend-assets/css/components.css') }}">
<!-- Custom style CSS -->
<link rel="stylesheet" href="{{ asset('backend-assets/css/custom.css') }}">
<link rel='shortcut icon' type='image/x-icon' href="{{ asset('backend-assets/img/favicon.ico') }}" />
<style>
.skip-link { position: absolute; left: -9999px; z-index: 9999; padding: 0.5rem 1rem; font-weight: 600; }
.skip-link:focus { left: 0.5rem; top: 0.5rem; background: #fff; color: #0f172a; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
*:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
</style>
</head>

<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="loader" aria-hidden="true"></div>
<div id="app">
<main id="main-content" role="main">
<section class="section">
<div class="container mt-5">
    <div class="row">
        <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
            @yield('login')
        </div>
    </div>
</div>
</section>
</main>
</div>


<!-- General JS Scripts -->
<script src="{{ asset('backend-assets/js/app.min.js') }}"></script>
<!-- JS Libraies -->
<!-- Page Specific JS File -->
<!-- Template JS File -->
<script src="{{ asset('backend-assets/js/scripts.js') }}"></script>
<!-- Custom JS File -->
<script src="{{ asset('backend-assets/js/custom.js') }}"></script>
</body>
</html>