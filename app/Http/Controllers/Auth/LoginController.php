<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
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

        $canSelfRegister = !User::query()->exists();
        $initialTab = old('auth_mode', $request->query('mode') === 'signup' ? 'signup' : 'signin');
        if (!$canSelfRegister && $initialTab === 'signup') {
            $initialTab = 'signin';
        }

        return view('auth.login', [
            'canSelfRegister' => $canSelfRegister,
            'initialAuthTab' => $initialTab,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'status' => 'active',
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid credentials or inactive account.'])
                ->withInput(['email' => $request->email]);
        }

        $request->session()->regenerate();

        AuditLog::log('login', 'auth', 'Staff logged in');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function register(Request $request)
    {
        if (User::query()->exists()) {
            return back()
                ->withErrors(['signup' => 'Sign up is disabled after first account setup. Please sign in.'])
                ->withInput(['auth_mode' => 'signup']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'status' => 'active',
            'team_manager_id' => null,
        ]);

        AuditLog::log('created', 'users', "Self-signup created initial admin: {$user->name}");

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::log('login', 'auth', 'Staff logged in');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Account created successfully. You are now signed in.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('logout', 'auth', 'Staff logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
