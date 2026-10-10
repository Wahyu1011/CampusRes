<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }
        $users = User::latest()->get();

        return view('users', compact('users'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,petugas,user',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'account_status' => 'approved', // langsung approve jika dibuat admin
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateStatus(Request $request, User $user)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'account_status' => 'required|in:approved,rejected',
        ]);

        $user->update(['account_status' => $request->account_status]);

        return back()->with('success', 'Status akun pengguna berhasil diperbarui.');
    }
}
