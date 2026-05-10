<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>@yield('title')</title>
  <meta name="title" content="@yield('title')">
  <meta name="description" content="Progga Restaurant Management System - Complete solution for your restaurant operations.">
  <meta name="keywords" content="restaurant management, pos, kitchen board, progga rms, dashboard">
  <meta name="author" content="Progga RMS">

  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->full() }}">
  <meta property="og:title" content="@yield('title')">
  <meta property="og:description" content="Manage your restaurant efficiently with Progga RMS.">
  <meta property="og:image" content=""> <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="{{ url()->full() }}">
  <meta property="twitter:title" content="@yield('title')">
  <meta property="twitter:description" content="Manage your restaurant efficiently with Progga RMS.">
  <meta property="twitter:image" content="">
  <link rel="icon" type="image/x-icon" href="">
  <link rel="apple-touch-icon" href="">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
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
    </div></div><div class="progga-toast-container"></div>

 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

  <script src="{{ asset('/') }}public/admin/assets/js/progga-app.js"></script>
  <script src="{{ asset('/') }}public/admin/assets/js/progga-charts.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {

    // ১. Choices.js (Select2 এর বিকল্প) ইনিশিয়ালাইজেশন
    // ক্লাস: .progga-select
    const selectElements = document.querySelectorAll('.progga-select');
    selectElements.forEach(function(element) {
        new Choices(element, {
            searchEnabled: true,      // সার্চ অপশন চালু
            itemSelectText: '',       // সিলেক্ট করার টেক্সট হাইড করা (ক্লিন লুকের জন্য)
            removeItemButton: true,   // মাল্টিপলের ক্ষেত্রে রিমুভ বাটন
            shouldSort: false         // অপশনগুলো ডিফল্ট অর্ডারে রাখার জন্য
        });
    });

    // ২. Flatpickr (No-jQuery Datepicker) ইনিশিয়ালাইজেশন
    // ক্লাস: .progga-datepicker
    flatpickr('.progga-datepicker', {
        dateFormat: "Y-m-d",          // ডাটাবেস ফ্রেন্ডলি ফরম্যাট
        allowInput: true,             // ম্যানুয়ালি টাইপ করার অপশন
        // enableTime: true,          // সময় লাগলে এটি আনকমেন্ট করবেন
    });

    // ৩. Summernote Lite (Rich Text Editor) ইনিশিয়ালাইজেশন
    // ক্লাস: .progga-editor
    if (typeof jQuery !== 'undefined') {
        $('.progga-editor').summernote({
            height: 250,              // ডিফল্ট উচ্চতা
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

    // ৪. SweetAlert2 (Delete Confirmation এর জন্য)
    // ক্লাস: .progga-delete-btn
    document.addEventListener('click', function (e) {
        // যদি ক্লিক করা এলিমেন্ট বা তার প্যারেন্ট .progga-delete-btn হয়
        const deleteBtn = e.target.closest('.progga-delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            const form = deleteBtn.closest('form'); // ডিলিট বাটনের ফর্মটি খুঁজবে

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this data!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#21352a', // আপনার থিমের প্রাইমারি কালার
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed && form) {
                    form.submit(); // ইউজার ইয়েস দিলে ফর্ম সাবমিট হবে
                }
            });
        }
    });

});

// ৫. SweetAlert2 (সাকসেস বা এরর মেসেজ দেখানোর ফাংশন)
// যেকোনো পেজ থেকে কল করা যাবে: showToast('Success', 'Data saved!', 'success');
window.showToast = function(title, text, icon = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon, // 'success', 'error', 'warning', 'info'
        title: title,
        text: text,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
};
  </script>

  @yield('script')
</body>
</html>
