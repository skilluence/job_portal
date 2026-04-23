<?php

namespace App\Http\Middleware;

use App\Models\Candidate;
use App\Services\StudentRememberService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentAuth
{
    public function __construct(private readonly StudentRememberService $studentRememberService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $studentId = session('student_id');

        if (!$studentId) {
            $candidate = $this->studentRememberService->restoreFromRememberCookie($request);
            $studentId = $candidate?->id;
        }

        if (!$studentId) {
            return redirect()->route('student.login')->with('error', 'Please log in to continue.');
        }

        $candidate = Candidate::withTrashed()->find($studentId);

        if (!$candidate || $candidate->trashed()) {
            $this->studentRememberService->forgetRememberCookie();
            session()->forget('student_id');

            return redirect()->route('student.login')->with('error', 'Your portal account has been deleted.');
        }

        if ($candidate->status === 'inactive') {
            $candidate->forceFill(['remember_token' => null])->save();
            $this->studentRememberService->forgetRememberCookie();
            session()->forget('student_id');

            return redirect()->route('student.login')->with('error', 'Your portal access has been deactivated.');
        }

        return $next($request);
    }
}
