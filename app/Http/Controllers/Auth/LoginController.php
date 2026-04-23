<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\LeaveStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, LeaveStatusService $leaveStatusService)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower(trim((string) $request->email));
        $password = (string) $request->password;
        $user = User::where('email', $email)->first();

        if ($user) {
            $leaveStatusService->syncUserStatus($user);
            $user->refresh();
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput(['email' => $email]);
        }

        if ($user->status !== 'active') {
            return back()
                ->withErrors(['email' => 'Your account is inactive. Please contact an administrator.'])
                ->withInput(['email' => $email]);
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Unable to sign in right now. Please try again.'])
                ->withInput(['email' => $email]);
        }

        $request->session()->regenerate();

        AuditLog::log('login', 'auth', 'Staff logged in');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('logout', 'auth', 'Staff logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(route('login'));
    }
}
