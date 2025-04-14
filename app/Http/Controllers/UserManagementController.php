<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->get();
        $roles = Role::all();

        return Inertia::render('UserManagemen/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6|confirmed',
            'role_id'   => 'required|exists:roles,id',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        // Validasi umum untuk semua field kecuali email
        $rules = [
            'name'      => 'required|string|max:255',
            'password'  => 'nullable|min:6|confirmed',
            'role_id'   => 'required|exists:roles,id',
        ];
        
        // Jika email berubah, validasi sebagai email unik
        // Jika tidak berubah, cukup validasi format email saja
        if ($request->email !== $user->email) {
            $rules['email'] = 'required|email|unique:users,email';
        } else {
            $rules['email'] = 'required|email';
        }
        
        $data = $request->validate($rules);

        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }


    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

}
