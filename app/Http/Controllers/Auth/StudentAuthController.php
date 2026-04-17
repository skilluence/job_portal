<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('student_id')) {
            return redirect()->route('student.dashboard');
        }

        return view('student.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower($request->email);
        $candidate = Candidate::withTrashed()
            ->whereRaw('LOWER(email_id) = ?', [$email])
            ->first();

        if (!$candidate) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput(['email' => $request->email]);
        }

        if ($candidate->trashed()) {
            return back()
                ->withErrors(['email' => 'Your account has been deleted. Please contact your recruiter.'])
                ->withInput(['email' => $request->email]);
        }

        if ($candidate->status === 'inactive') {
            return back()
                ->withErrors(['email' => 'Your account is inactive. Please contact your recruiter.'])
                ->withInput(['email' => $request->email]);
        }

        if (!Hash::check($request->password, $candidate->login_password)) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput(['email' => $request->email]);
        }

        $request->session()->regenerate();
        session(['student_id' => $candidate->id]);

        AuditLog::log('login', 'student_auth', "Student logged in: {$candidate->full_name}");

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        $candidateId = session('student_id');
        $candidate = $candidateId ? Candidate::find($candidateId) : null;

        if ($candidate) {
            AuditLog::log('logout', 'student_auth', "Student logged out: {$candidate->full_name}");
        }

        session()->forget('student_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
