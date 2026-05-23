<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request; // এটি যুক্ত করতে হবে

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    // লগইনের পর রোল চেক করে রিডাইরেক্ট করার মেথড
    protected function authenticated(Request $request, $user)
    {
        if ($user->hasRole('waiter')) {
            return redirect()->route('pos.index'); // ওয়েটার হলে সরাসরি POS-এ যাবে
        }

        return redirect()->route('home'); // এডমিন বা অন্য কেউ হলে ড্যাশবোর্ডে যাবে
    }
}
