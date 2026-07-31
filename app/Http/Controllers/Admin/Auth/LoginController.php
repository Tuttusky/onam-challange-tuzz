<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        $success = Auth::guard('admin')->attempt($credentials, $remember);
        $admin = Auth::guard('admin')->user();

        if ($success && $admin && ! $admin->is_active) {
            Auth::guard('admin')->logout();
            $success = false;
            $admin = null;
        }

        LoginLog::query()->create([
            'admin_id' => $admin?->id,
            'email' => $credentials['email'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => $success,
        ]);

        if (! $success) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid credentials or inactive account.']);
        }

        $admin->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
