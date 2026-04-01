<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminUser = User::where('email', $adminEmail)->first();

        if (
            $adminUser &&
            hash_equals($adminEmail, (string) $credentials['email']) &&
            Hash::check((string) $credentials['password'], (string) $adminUser->password)
        ) {
            $request->session()->put('admin_logged_in', true);
            $request->session()->put('admin_username', $adminUser->name);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid admin credentials.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_logged_in', 'admin_username']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form');
    }
}
