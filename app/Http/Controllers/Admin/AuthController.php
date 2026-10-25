<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.admin-login');
    }

    public function login(
        LoginRequest $request,
        AuthenticatedSessionController $fortifyController
    ) {
        $request->session()->put(
            'url.intended',
            route('admin.attendance.list')
        );

        return $fortifyController->store($request);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
