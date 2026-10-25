<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::where('role', 0)
            ->orderBy('id')
            ->get();

        return view('admin.staff-list', [
            'users' => $users,
        ]);
    }
}
