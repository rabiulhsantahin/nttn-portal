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
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account is currently deactivated. Please contact the Super Administrator.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, '.$user->name.'!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function quickLogin(Request $request)
    {
        $currentAuth = Auth::user();
        $isImpersonating = session()->has('impersonator_id');

        // Only authenticated Super Admin or active impersonator can switch roles
        if (! $currentAuth || (! $currentAuth->isSuperAdmin() && ! $isImpersonating)) {
            abort(403, 'Unauthorized. Only Super Admin can switch roles.');
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $targetUser = User::where('email', $request->email)->firstOrFail();

        // If target is super admin, end impersonation
        if ($targetUser->isSuperAdmin()) {
            session()->forget('impersonator_id');
        } elseif (! $isImpersonating) {
            session(['impersonator_id' => $currentAuth->id]);
        }

        Auth::login($targetUser);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Switched session to '.$targetUser->name.' ('.strtoupper($targetUser->role->name).')');
    }

    public function logout(Request $request)
    {
        session()->forget('impersonator_id');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out.');
    }
}
