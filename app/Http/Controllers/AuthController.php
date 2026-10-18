<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $demoUsers = User::with('role')->whereIn('email', [
            'admin@nttn.test',
            'md@nttn.test',
            'reseller@isp.test',
            'tx@nttn.test',
            'noc@nttn.test',
            'billing@nttn.test',
        ])->get();

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function quickLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Switched session to ' . $user->name . ' (' . strtoupper($user->role->name) . ')');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out.');
    }
}
