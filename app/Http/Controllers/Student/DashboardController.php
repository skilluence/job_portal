<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\DailyLog;
use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $candidateId = session('student_id');

        $candidate = Candidate::with('recruiter')->findOrFail($candidateId);
        $candidateTimezone = $this->resolveCandidateTimezone($candidate);

        $profileCompletion = $this->profileCompletion($candidate);

        // Tab 1: Application and Assisment daily logs
        $dailyLogs = DailyLog::where('candidate_id', $candidateId)
            ->orderBy('log_date', 'desc')
            ->get(['log_date', 'applications', 'assistant_count']);

        // Tab 2: Interview list sorted by actual scheduled timestamp,
        // then rendered in candidate timezone.
        $interviews = Interview::where('candidate_id', $candidateId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Interview $interview) use ($candidateTimezone) {
                $scheduledAtUtc = $this->interviewUtcTimestamp($interview);

                if ($scheduledAtUtc) {
                    $candidateMoment = $scheduledAtUtc->copy()->setTimezone($candidateTimezone);
                    $interview->candidate_display_date = $candidateMoment->format('M d, Y');
                    $interview->candidate_display_date_key = $candidateMoment->format('Y-m-d');
                    $interview->candidate_display_time = $candidateMoment->format('h:i A');
                    $interview->candidate_display_timezone = $candidateMoment->format('T');
                    $interview->candidate_sort_ts = $scheduledAtUtc->getTimestamp();
                } else {
                    $interview->candidate_display_date = null;
                    $interview->candidate_display_date_key = null;
                    $interview->candidate_display_time = null;
                    $interview->candidate_display_timezone = null;
                    $interview->candidate_sort_ts = null;
                }

                return $interview;
            });

        $interviews = $this->sortInterviewsByCandidateTime($interviews);

        // Stat card: today's scheduled interviews in candidate timezone.
        $todayKey = now($candidateTimezone)->format('Y-m-d');
        $todayInterviewsCount = $interviews
            ->where('candidate_display_date_key', $todayKey)
            ->count();

        // Tab 3: Documents and resumes
        $resumes = CandidateResume::where('candidate_id', $candidateId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.dashboard', [
            'candidate'            => $candidate,
            'candidateTimezone'    => $candidateTimezone,
            'profileCompletion'    => $profileCompletion,
            'dailyLogs'            => $dailyLogs,
            'interviews'           => $interviews,
            'resumes'              => $resumes,
            'todayInterviewsCount' => $todayInterviewsCount,
        ]);
    }

    private function sortInterviewsByCandidateTime(Collection $interviews): Collection
    {
        return $interviews->sort(function (Interview $a, Interview $b) {
            $aTs = $a->candidate_sort_ts;
            $bTs = $b->candidate_sort_ts;

            if ($aTs === null && $bTs === null) {
                return $b->created_at <=> $a->created_at;
            }
            if ($aTs === null) {
                return 1;
            }
            if ($bTs === null) {
                return -1;
            }
            if ($aTs === $bTs) {
                return $b->created_at <=> $a->created_at;
            }

            // Earliest scheduled interview appears first.
            return $aTs <=> $bTs;
        })->values();
    }

    private function interviewUtcTimestamp(Interview $interview): ?Carbon
    {
        if (!$interview->scheduled_date || !$interview->scheduled_time) {
            return null;
        }

        $sourceTimezone = $this->timezoneAbbreviationToIana($interview->scheduled_timezone);
        $scheduledString = $interview->scheduled_date->format('Y-m-d') . ' ' . substr((string) $interview->scheduled_time, 0, 5);

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $scheduledString, $sourceTimezone)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveCandidateTimezone(Candidate $candidate): string
    {
        $state = strtoupper((string) ($candidate->state_province ?? ''));

        $stateToTimezone = [
            'CT' => 'America/New_York', 'DE' => 'America/New_York', 'DC' => 'America/New_York',
            'FL' => 'America/New_York', 'GA' => 'America/New_York', 'ME' => 'America/New_York',
            'MD' => 'America/New_York', 'MA' => 'America/New_York', 'NH' => 'America/New_York',
            'NJ' => 'America/New_York', 'NY' => 'America/New_York', 'NC' => 'America/New_York',
            'OH' => 'America/New_York', 'PA' => 'America/New_York', 'RI' => 'America/New_York',
            'SC' => 'America/New_York', 'VT' => 'America/New_York', 'VA' => 'America/New_York',
            'WV' => 'America/New_York',
            'AL' => 'America/Chicago', 'AR' => 'America/Chicago', 'IL' => 'America/Chicago',
            'IA' => 'America/Chicago', 'KS' => 'America/Chicago', 'KY' => 'America/Chicago',
            'LA' => 'America/Chicago', 'MI' => 'America/Chicago', 'MN' => 'America/Chicago',
            'MS' => 'America/Chicago', 'MO' => 'America/Chicago', 'NE' => 'America/Chicago',
            'ND' => 'America/Chicago', 'OK' => 'America/Chicago', 'TN' => 'America/Chicago',
            'TX' => 'America/Chicago', 'WI' => 'America/Chicago', 'SD' => 'America/Chicago',
            'AZ' => 'America/Phoenix', 'CO' => 'America/Denver', 'ID' => 'America/Denver',
            'NM' => 'America/Denver', 'MT' => 'America/Denver', 'UT' => 'America/Denver',
            'WY' => 'America/Denver',
            'CA' => 'America/Los_Angeles', 'NV' => 'America/Los_Angeles',
            'OR' => 'America/Los_Angeles', 'WA' => 'America/Los_Angeles',
            'AK' => 'America/Anchorage',
            'HI' => 'Pacific/Honolulu',
        ];

        if ($state && isset($stateToTimezone[$state])) {
            return $stateToTimezone[$state];
        }

        return config('app.timezone', 'UTC');
    }

    private function timezoneAbbreviationToIana(?string $abbr): string
    {
        return match (strtoupper((string) $abbr)) {
            'EST', 'EDT' => 'America/New_York',
            'CST', 'CDT' => 'America/Chicago',
            'MST', 'MDT' => 'America/Denver',
            'PST', 'PDT' => 'America/Los_Angeles',
            'AKST'       => 'America/Anchorage',
            'HST'        => 'Pacific/Honolulu',
            default      => config('app.timezone', 'UTC'),
        };
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
