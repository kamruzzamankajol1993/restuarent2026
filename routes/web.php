<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\CustomResetPasswordController; // নতুন কন্ট্রোলার
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\WaiterController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\RewardPointSettingController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\TableBookingController;
use App\Http\Controllers\Admin\OccasionController;
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

Route::middleware(['auth'])->group(function () {



// ==========================================
    // Table Booking Routes
    // ==========================================
    Route::resource('table-booking', TableBookingController::class);

    // ==========================================
    // Occasion Management Routes (AJAX Based)
    // ==========================================
    Route::get('occasion', [OccasionController::class, 'index'])->name('occasion.index');
    Route::post('occasion', [OccasionController::class, 'store'])->name('occasion.store');
    Route::put('occasion/{id}', [OccasionController::class, 'update'])->name('occasion.update');
    Route::delete('occasion/{id}', [OccasionController::class, 'destroy'])->name('occasion.destroy');

// ==========================================
    // Table Management Routes
    // ==========================================
    Route::resource('table', TableController::class);


// ==========================================
    // Customer Management Routes
    // ==========================================

    // Customer Export Routes
Route::get('customer-export-pdf', [CustomerController::class, 'exportPDF'])->name('customer.export.pdf');
Route::get('customer-export-excel', [CustomerController::class, 'exportExcel'])->name('customer.export.excel');


    Route::resource('customer', CustomerController::class);
// Customer History Route (Ajax)
    Route::get('customer-history/{id}', [CustomerController::class, 'history'])->name('customer.history');
    // ==========================================
    // Reward Point Settings Routes
    // ==========================================
    Route::get('reward-points', [RewardPointSettingController::class, 'index'])->name('reward-points.index');
    Route::post('reward-points/update', [RewardPointSettingController::class, 'update'])->name('reward-points.update');


// Zone Management Routes
    Route::resource('zone', ZoneController::class);

    // Shift Management Routes
    Route::resource('shift', ShiftController::class);
Route::post('waiter-update-status', [WaiterController::class, 'updateStatus'])->name('waiter.status');
    // Waiter Management Routes
    Route::resource('waiter', WaiterController::class);


    Route::resource('permission', PermissionController::class);
    Route::resource('role', RoleController::class);
    Route::resource('user', UserController::class);

    // Profile Routes
    Route::get('/my-profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/my-profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/my-profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Settings Routes
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/restaurant', [SettingController::class, 'updateRestaurant'])->name('settings.restaurant');
    Route::post('/settings/tax', [SettingController::class, 'updateTax'])->name('settings.tax');
    Route::post('/settings/invoice', [SettingController::class, 'updateInvoice'])->name('settings.invoice');
    Route::post('/settings/pos', [SettingController::class, 'updatePos'])->name('settings.pos');
});
