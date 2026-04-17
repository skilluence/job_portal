<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;

class DashboardController extends Controller
{
    public function index()
    {
        $candidate = Candidate::with('recruiter')->findOrFail(session('student_id'));

        $recentStudentLogs = AuditLog::where('actor_type', 'student')
            ->where('actor_name', $candidate->full_name)
            ->latest()
            ->limit(6)
            ->get();

        $profileCompletion = $this->profileCompletion($candidate);

        return view('student.dashboard', [
            'candidate' => $candidate,
            'recentStudentLogs' => $recentStudentLogs,
            'profileCompletion' => $profileCompletion,
        ]);
    }

    private function profileCompletion(Candidate $candidate): int
    {
        $fields = [
            $candidate->full_name,
            $candidate->email_id,
            $candidate->linkedin_id,
            $candidate->address,
            $candidate->profile,
            $candidate->notes,
        ];

        $filled = collect($fields)->filter(fn ($value) => !empty($value))->count();

        return (int) round(($filled / count($fields)) * 100);
    }
}
