<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->get();
        $roles = Role::all();

        return Inertia::render('Dashboard', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
