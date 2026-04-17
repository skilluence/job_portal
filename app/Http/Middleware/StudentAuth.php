<?php

namespace App\Http\Middleware;

use App\Models\Candidate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $studentId = session('student_id');

        if (!$studentId) {
            return redirect()->route('student.login')->with('error', 'Please log in to continue.');
        }

        $candidate = Candidate::withTrashed()->find($studentId);

        if (!$candidate || $candidate->trashed()) {
            session()->forget('student_id');

            return redirect()->route('student.login')->with('error', 'Your portal account has been deleted.');
        }

        if ($candidate->status === 'inactive') {
            session()->forget('student_id');

            return redirect()->route('student.login')->with('error', 'Your portal account is inactive.');
        }

        return $next($request);
    }
}
