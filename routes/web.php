<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\CustomResetPasswordController; // নতুন কন্ট্রোলার
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('admin.auth.login');
});

Route::get('/clear', function() {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('config:cache');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return redirect()->back();
});

// ডিফল্ট অথ রাউট (কিন্তু রিসেট রাউটগুলো ফলস করে দিলাম)
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/download-pdf', [PDFController::class, 'generatePDF']);
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');

// ==========================================
// Custom Password Reset Routes (GET & POST)
// ==========================================

// 1. Forgot Password Form & Send OTP
Route::get('/forgot-password', [CustomResetPasswordController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [CustomResetPasswordController::class, 'sendOtp'])->name('password.email');

// 2. OTP Verification Form & Verify Process
Route::get('/verify-otp', [CustomResetPasswordController::class, 'showOtpForm'])->name('password.otp');
Route::post('/verify-otp', [CustomResetPasswordController::class, 'verifyOtp'])->name('password.otp.verify');

// 3. Reset Password Form & Update Password
Route::get('/reset-password', [CustomResetPasswordController::class, 'showResetForm'])->name('password.resetForm');
Route::post('/reset-password', [CustomResetPasswordController::class, 'resetPassword'])->name('password.update');
