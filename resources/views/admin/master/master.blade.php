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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

  <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('/') }}public/admin/assets/css/progga-style.css">
  @yield('css')
</head>
<body data-page-title="@yield('title')" data-page-subtitle="Overview">

  <div class="progga-layout">

    @include('admin.include.sidebar')
    <div class="progga-sidebar-overlay" id="sidebarOverlay"></div>

    <div class="progga-main">

      @include('admin.include.header')

      @yield('body')

      @include('admin.include.footer')
    </div>
  </div>

  <div class="progga-toast-container"></div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

  <script src="{{ asset('/') }}public/admin/assets/js/progga-app.js"></script>
  <script src="{{ asset('/') }}public/admin/assets/js/progga-charts.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {

    // ১. Choices.js (যেখানে বিশেষভাবে Choices.js লাগবে সেখানে .progga-choices ক্লাস ব্যবহার করবেন)
    const choicesElements = document.querySelectorAll('.progga-choices');
    choicesElements.forEach(function(element) {
        new Choices(element, {
            searchEnabled: true,
            itemSelectText: '',
            removeItemButton: true,
            shouldSort: false
        });
        // Select2 যাতে এই এলিমেন্টে কাজ না করে, তা নিশ্চিত করতে
        element.setAttribute('data-no-select2', 'true');
    });

    // ২. Flatpickr (No-jQuery Datepicker)
    flatpickr('.progga-datepicker', {
        dateFormat: "Y-m-d",
        allowInput: true,
    });

    // ৩. Summernote Lite (Rich Text Editor)
    if (typeof jQuery !== 'undefined') {
        $('.progga-editor').summernote({
            height: 250,
            placeholder: 'Type your content here...',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    // ৪. SweetAlert2 (Delete Confirmation)
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.progga-delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            const form = deleteBtn.closest('form');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this data!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#21352a',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed && form) {
                    form.submit();
                }
            });
        }
    });

    // 5. Sidebar Auto Scroll to Active Item
    const activeSidebarItem = document.querySelector('.progga-sidebar-nav .progga-nav-link.active');
    if (activeSidebarItem) {
        setTimeout(() => {
            activeSidebarItem.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 100);
    }

});

// ৬. Global Toast Function
window.showToast = function(title, text, icon = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        text: text,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
};

$(document).ready(function() {
    // ৩ সেকেন্ড (৩০০০ মিলি-সেকেন্ড) পর অ্যালার্ট মেসেজ অটোমেটিক হাইড হয়ে যাবে
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove(); // হাইড হওয়ার পর DOM থেকেও রিমুভ করে দিবে
        });
    }, 3000);
});
  </script>

  @yield('script')
</body>
</html>
