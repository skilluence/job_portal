<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $isRecruiter = $user->isRecruiter();
        $isManager = $user->isManager();
        $teamMemberIds = $isManager ? $user->teamMemberIds() : [];

        // Widget 1: Today/Tomorrow interviews in India timezone order.
        $dashboardTimezone = 'Asia/Kolkata';
        $todayKey = now($dashboardTimezone)->format('Y-m-d');
        $tomorrowKey = now($dashboardTimezone)->addDay()->format('Y-m-d');
        $performanceMonth = (string) $request->get('perf_month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $performanceMonth)) {
            $performanceMonth = now()->format('Y-m');
        }
        [$performanceYear, $performanceMon] = explode('-', $performanceMonth);
        $performanceStart = Carbon::createFromDate((int) $performanceYear, (int) $performanceMon, 1)->startOfDay();
        $performanceEnd = $performanceStart->copy()->endOfMonth()->endOfDay();
        $performanceMonths = [];
        for ($i = 0; $i <= 12; $i++) {
            $monthCursor = now()->subMonths($i);
            $performanceMonths[$monthCursor->format('Y-m')] = $monthCursor->format('F Y');
        }

        $upcomingInterviews = Interview::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager, fn ($q) => $q->whereIn('recruiter_id', $teamMemberIds))
            ->with(['candidate:id,full_name', 'recruiter:id,name'])
            ->whereNotNull('scheduled_date')
            ->get()
            ->map(function (Interview $interview) use ($dashboardTimezone) {
                $scheduledAtUtc = $this->interviewUtcTimestamp($interview);
                $interview->dashboard_sort_ts = $scheduledAtUtc?->getTimestamp();
                $interview->has_precise_time = !empty($interview->scheduled_time);
                $sourceTimezone = strtoupper((string) $interview->scheduled_timezone) ?: null;
                $sourceTimezoneIana = $this->timezoneAbbreviationToIana($sourceTimezone);

                if ($scheduledAtUtc) {
                    $dashboardMoment = $scheduledAtUtc->copy()->setTimezone($dashboardTimezone);
                    $interview->dashboard_date_key = $dashboardMoment->format('Y-m-d');
                    $interview->dashboard_display_date = $dashboardMoment->format('M d, Y');
                    $interview->dashboard_display_time = $interview->scheduled_time
                        ? $dashboardMoment->format('h:i A')
                        : 'TBD';
                    $interview->dashboard_display_timezone = $dashboardMoment->format('T');

                    $sourceMoment = $scheduledAtUtc->copy()->setTimezone($sourceTimezoneIana);
                    $interview->source_display_date = $sourceMoment->format('M d, Y');
                    $interview->source_display_time = $interview->scheduled_time
                        ? $sourceMoment->format('h:i A')
                        : 'TBD';
                    $interview->source_display_timezone = $sourceTimezone ?: $sourceMoment->format('T');
                } else {
                    $interview->dashboard_date_key = $interview->scheduled_date?->format('Y-m-d');
                    $interview->dashboard_display_date = $interview->scheduled_date?->format('M d, Y');
                    $interview->dashboard_display_time = 'TBD';
                    $interview->dashboard_display_timezone = $sourceTimezone;
                    $interview->source_display_date = $interview->scheduled_date?->format('M d, Y');
                    $interview->source_display_time = $this->formatDashboardTime($interview->scheduled_time) ?: 'TBD';
                    $interview->source_display_timezone = $sourceTimezone;
                }

                return $interview;
            });

        $todayInterviews = $this->sortDashboardInterviews(
            $upcomingInterviews->where('dashboard_date_key', $todayKey)
        );

        $tomorrowInterviews = $this->sortDashboardInterviews(
            $upcomingInterviews->where('dashboard_date_key', $tomorrowKey)
        );

        // Widget 2: Top performance recruiter (by valid interview count).
        $validInterviewCounts = Interview::where('interview_status', 'valid')
            ->whereBetween('scheduled_date', [
                $performanceStart->toDateString(),
                $performanceEnd->toDateString(),
            ])
            ->selectRaw('COALESCE(recruiter_id, created_by) as performer_id, COUNT(*) as cnt')
            ->where(function ($q) {
                $q->whereNotNull('recruiter_id')
                    ->orWhereNotNull('created_by');
            })
            ->groupBy('performer_id')
            ->pluck('cnt', 'performer_id');

        $candidateOwnershipCounts = Candidate::query()
            ->selectRaw('COALESCE(recruiter_id, team_manager_id) as owner_id, COUNT(*) as cnt')
            ->where(function ($q) {
                $q->whereNotNull('recruiter_id')
                    ->orWhereNotNull('team_manager_id');
            })
            ->groupBy('owner_id')
            ->pluck('cnt', 'owner_id');

        $topPerformers = User::query()
            ->whereIn('role', ['recruiter', 'manager'])
            ->active()
            ->select(['id', 'name', 'role'])
            ->get()
            ->map(function ($member) use ($validInterviewCounts, $candidateOwnershipCounts) {
                $member->valid_interview_count = (int) ($validInterviewCounts[$member->id] ?? 0);
                $member->performance_candidates_count = (int) ($candidateOwnershipCounts[$member->id] ?? 0);
                return $member;
            })
            ->sortByDesc('valid_interview_count')
            ->take(10)
            ->values();

        // Widget 3: Pending applications.
        $pendingDateStr = $request->get('pend_date', today()->toDateString());
        $pendingDateEndStr = $request->get('pend_date_end');

        try {
            $pendingStart = Carbon::parse($pendingDateStr)->startOfDay();
            $pendingEnd = $pendingDateEndStr
                ? Carbon::parse($pendingDateEndStr)->endOfDay()
                : $pendingStart->copy()->endOfDay();
        } catch (\Exception $e) {
            $pendingStart = Carbon::today();
            $pendingEnd = Carbon::today()->endOfDay();
            $pendingDateStr = today()->toDateString();
            $pendingDateEndStr = null;
        }

        $pendingDays = max(1, (int) $pendingStart->diffInDays($pendingEnd) + 1);

        $logSums = DailyLog::whereBetween('log_date', [$pendingStart->toDateString(), $pendingEnd->toDateString()])
            ->groupBy('candidate_id')
            ->selectRaw('candidate_id, SUM(applications) as total_logged')
            ->pluck('total_logged', 'candidate_id');

        $pendingCandidates = Candidate::whereIn('status', ['active', 'enrolled'])
            ->where('no_of_applications', '>', 0)
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager, fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)))
            ->with('recruiter:id,name')
            ->get(['id', 'full_name', 'no_of_applications', 'recruiter_id'])
            ->map(function ($c) use ($logSums, $pendingDays) {
                $target = $c->no_of_applications * $pendingDays;
                $logged = (int) ($logSums[$c->id] ?? 0);
                $c->pending_target = $target;
                $c->pending_logged = $logged;
                $c->pending_gap = max(0, $target - $logged);
                return $c;
            })
            ->filter(fn ($c) => $c->pending_gap > 0)
            ->sortByDesc('pending_gap')
            ->values();

        // Widget 4: Attention required.
        $twentyDaysAgo = today()->subDays(20)->toDateString();

        $recentlyInterviewedIds = Interview::whereDate('created_at', '>=', $twentyDaysAgo)
            ->pluck('candidate_id')
            ->unique()
            ->toArray();

        $attentionCandidates = Candidate::whereIn('status', ['active', 'enrolled'])
            ->whereDate('created_at', '<=', $twentyDaysAgo)
            ->whereNotIn('id', $recentlyInterviewedIds)
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager, fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)))
            ->with('recruiter:id,name')
            ->orderBy('created_at')
            ->get(['id', 'full_name', 'status', 'recruiter_id', 'created_at', 'domain']);

        // Manager header stats.
        $managerMyCandidatesCount = $isManager
            ? Candidate::where('team_manager_id', $user->id)->whereNull('recruiter_id')->count()
            : 0;
        $managerAllCandidatesCount = $isManager ? Candidate::whereIn('recruiter_id', $teamMemberIds)->count() : 0;

        // Summary stats (scoped by role).
        $statsQuery = fn () => Candidate::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager, fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)));

        $statActiveCandidates = $statsQuery()->whereIn('status', ['active', 'enrolled'])->count();
        $statTodayInterviews = $todayInterviews->count();

        $statTotalRecruiters = $isAdmin
            ? User::recruiters()->active()->count()
            : ($isManager ? User::recruiters()->active()->whereIn('id', $teamMemberIds)->count() : 0);

        $statPendingCount = $pendingCandidates->count();

        // Trending domains.
        $trendingDomains = Interview::where('interviews.interview_status', 'valid')
            ->when($isRecruiter, fn ($q) => $q->where('interviews.recruiter_id', $user->id))
            ->when($isManager, fn ($q) => $q->whereIn('interviews.recruiter_id', $teamMemberIds))
            ->join('candidates', 'interviews.candidate_id', '=', 'candidates.id')
            ->whereNotNull('candidates.domain')
            ->where('candidates.domain', '!=', '')
            ->selectRaw('candidates.domain, COUNT(*) as cnt')
            ->groupBy('candidates.domain')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'todayInterviews' => $todayInterviews,
            'tomorrowInterviews' => $tomorrowInterviews,
            'dashboardTimezone' => $dashboardTimezone,
            'topPerformers' => $topPerformers,
            'performanceMonth' => $performanceMonth,
            'performanceMonths' => $performanceMonths,
            'pendingCandidates' => $pendingCandidates,
            'pendingDateStr' => $pendingDateStr,
            'pendingDateEndStr' => $pendingDateEndStr,
            'pendingDays' => $pendingDays,
            'attentionCandidates' => $attentionCandidates,
            'isAdmin' => $isAdmin,
            'isManager' => $isManager,
            'isRecruiter' => $isRecruiter,
            'managerMyCandidatesCount' => $managerMyCandidatesCount,
            'managerAllCandidatesCount' => $managerAllCandidatesCount,
            'statActiveCandidates' => $statActiveCandidates,
            'statTodayInterviews' => $statTodayInterviews,
            'statTotalRecruiters' => $statTotalRecruiters,
            'statPendingCount' => $statPendingCount,
            'trendingDomains' => $trendingDomains,
        ]);
    }

    private function sortDashboardInterviews(Collection $interviews): Collection
    {
        return $interviews->sort(function (Interview $a, Interview $b) {
            $aTs = $a->dashboard_sort_ts;
            $bTs = $b->dashboard_sort_ts;

            if ($aTs === null && $bTs === null) {
                return $a->id <=> $b->id;
            }
            if ($aTs === null) {
                return 1;
            }
            if ($bTs === null) {
                return -1;
            }
            if ($aTs !== $bTs) {
                return $aTs <=> $bTs;
            }
            if ($a->has_precise_time !== $b->has_precise_time) {
                return $a->has_precise_time ? -1 : 1;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    private function interviewUtcTimestamp(Interview $interview): ?Carbon
    {
        if (!$interview->scheduled_date) {
            return null;
        }

        $sourceTimezone = $this->timezoneAbbreviationToIana($interview->scheduled_timezone);
        $timePart = $interview->scheduled_time ? substr((string) $interview->scheduled_time, 0, 5) : '00:00';
        $scheduledString = $interview->scheduled_date->format('Y-m-d') . ' ' . $timePart;

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $scheduledString, $sourceTimezone)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timezoneAbbreviationToIana(?string $abbr): string
    {
        return match (strtoupper((string) $abbr)) {
            'EST', 'EDT' => 'America/New_York',
            'CST', 'CDT' => 'America/Chicago',
            'MST', 'MDT' => 'America/Denver',
            'PST', 'PDT' => 'America/Los_Angeles',
            'AKST' => 'America/Anchorage',
            'HST' => 'Pacific/Honolulu',
            default => config('app.timezone', 'UTC'),
        };
    }

    private function formatDashboardTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $normalizedTime = substr((string) $time, 0, 5);

        try {
            return Carbon::createFromFormat('H:i', $normalizedTime)->format('h:i A');
        } catch (\Throwable) {
            return trim((string) $time) ?: null;
        }
    }
}
