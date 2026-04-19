<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\DailyLog;
use App\Models\Interview;

class DashboardController extends Controller
{
    public function index()
    {
        $candidateId = session('student_id');

        $candidate = Candidate::with('recruiter')->findOrFail($candidateId);

        $profileCompletion = $this->profileCompletion($candidate);

        // Tab 1: Application & Assistant — daily log records for this candidate
        $dailyLogs = DailyLog::where('candidate_id', $candidateId)
            ->orderBy('log_date', 'desc')
            ->get(['log_date', 'applications', 'assistant_count']);

        // Tab 2: Interview — interview records for this candidate
        $interviews = Interview::where('candidate_id', $candidateId)
            ->orderBy('scheduled_date', 'desc')
            ->get();

        // Stat card: today's scheduled interviews
        $todayInterviewsCount = Interview::where('candidate_id', $candidateId)
            ->whereDate('scheduled_date', today())
            ->count();

        // Tab 3: Documents — resume records for this candidate
        $resumes = CandidateResume::where('candidate_id', $candidateId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.dashboard', [
            'candidate'              => $candidate,
            'profileCompletion'      => $profileCompletion,
            'dailyLogs'              => $dailyLogs,
            'interviews'             => $interviews,
            'resumes'                => $resumes,
            'todayInterviewsCount'   => $todayInterviewsCount,
        ]);
    }

    private function profileCompletion(Candidate $candidate): int
    {
        $fields = [
            $candidate->full_name,
            $candidate->email_id,
            $candidate->phone_number,
            $candidate->domain,
            $candidate->linkedin_url,
            $candidate->city,
            $candidate->visa_immigration_status,
            $candidate->cv_file_path,
        ];

        $filled = collect($fields)->filter(fn ($v) => !empty($v))->count();

        return (int) round(($filled / count($fields)) * 100);
    }
}
