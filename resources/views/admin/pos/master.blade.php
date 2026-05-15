<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title')</title>
    <meta name="title" content="@yield('title')">
  <meta name="description" content="Progga Restaurant Management System - Complete solution for your restaurant operations.">
  <meta name="keywords" content="restaurant management, pos, kitchen board, progga rms, dashboard">
  <meta name="author" content="Progga RMS">

  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->full() }}">
  <meta property="og:title" content="@yield('title')">
  <meta property="og:description" content="Manage your restaurant efficiently with Progga RMS.">
  <meta property="og:image" content="{{ asset('public/'.$restaurantSettingLogo) }}">
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="{{ url()->full() }}">
  <meta property="twitter:title" content="@yield('title')">
  <meta property="twitter:description" content="Manage your restaurant efficiently with Progga RMS.">
  <meta property="twitter:image" content="{{ asset('public/'.$restaurantSettingLogo) }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('public/'.$restaurantSettingIconName) }}">
  <link rel="apple-touch-icon" href="{{ asset('public/'.$restaurantSettingIconName) }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="{{ asset('/') }}public/admin/assets/css/progga-style.css">
  
    @yield('css')
</head>

<body class="progga-pos-overflow-lock">

    @yield('body')


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="{{ asset('/') }}public/admin/assets/js/progga-app.js"></script>
  <script src="{{ asset('/') }}public/admin/assets/js/progga-pos.js"></script>

    @yield('script')

</body>
</html>
