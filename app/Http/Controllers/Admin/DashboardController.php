<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user          = $request->user();
        $isAdmin       = $user->isAdmin();
        $isRecruiter   = $user->isRecruiter();
        $isManager     = $user->isManager();
        $teamMemberIds = $isManager ? $user->teamMemberIds() : [];

        // ── Widget 1 – Today & Tomorrow Interviews ────────────────────────────
        $today    = today()->toDateString();
        $tomorrow = today()->addDay()->toDateString();

        $interviewBaseQuery = fn () => Interview::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager,   fn ($q) => $q->whereIn('recruiter_id', $teamMemberIds))
            ->with(['candidate:id,full_name', 'recruiter:id,name'])
            ->orderBy('scheduled_time');

        $todayInterviews    = $interviewBaseQuery()->whereDate('scheduled_date', $today)->get();
        $tomorrowInterviews = $interviewBaseQuery()->whereDate('scheduled_date', $tomorrow)->get();

        // ── Widget 2 – Top Performance Recruiter (by valid interview count) ───
        $validInterviewCounts = Interview::where('interview_status', 'valid')
            ->selectRaw('recruiter_id, COUNT(*) as cnt')
            ->groupBy('recruiter_id')
            ->pluck('cnt', 'recruiter_id');

        $topRecruiters = User::recruiters()->active()
            ->when($isManager, fn ($q) => $q->whereIn('id', $teamMemberIds))
            ->withCount('candidates')
            ->get()
            ->map(fn ($r) => tap($r, fn ($r) => $r->valid_interview_count = (int) ($validInterviewCounts[$r->id] ?? 0)))
            ->sortByDesc('valid_interview_count')
            ->take(10)
            ->values();

        // ── Widget 3 – Pending Applications ───────────────────────────────────
        $pendingDateStr    = $request->get('pend_date', today()->toDateString());
        $pendingDateEndStr = $request->get('pend_date_end');

        try {
            $pendingStart = Carbon::parse($pendingDateStr)->startOfDay();
            $pendingEnd   = $pendingDateEndStr
                ? Carbon::parse($pendingDateEndStr)->endOfDay()
                : $pendingStart->copy()->endOfDay();
        } catch (\Exception $e) {
            $pendingStart      = Carbon::today();
            $pendingEnd        = Carbon::today()->endOfDay();
            $pendingDateStr    = today()->toDateString();
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
            ->when($isManager,   fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)))
            ->with('recruiter:id,name')
            ->get(['id', 'full_name', 'no_of_applications', 'recruiter_id'])
            ->map(function ($c) use ($logSums, $pendingDays) {
                $target            = $c->no_of_applications * $pendingDays;
                $logged            = (int) ($logSums[$c->id] ?? 0);
                $c->pending_target = $target;
                $c->pending_logged = $logged;
                $c->pending_gap    = max(0, $target - $logged);
                return $c;
            })
            ->filter(fn ($c) => $c->pending_gap > 0)
            ->sortByDesc('pending_gap')
            ->values();

        // ── Widget 4 – Attention Required ────────────────────────────────────
        $twentyDaysAgo = today()->subDays(20)->toDateString();

        $recentlyInterviewedIds = Interview::where('created_at', '>=', $twentyDaysAgo)
            ->pluck('candidate_id')
            ->unique()
            ->toArray();

        $attentionCandidates = Candidate::whereIn('status', ['active', 'enrolled'])
            ->whereNotIn('id', $recentlyInterviewedIds)
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager,   fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)))
            ->with('recruiter:id,name')
            ->orderBy('created_at')
            ->get(['id', 'full_name', 'status', 'recruiter_id', 'created_at', 'domain']);

        // ── Manager header stat counts ────────────────────────────────────────
        $managerMyCandidatesCount  = $isManager ? Candidate::where('team_manager_id', $user->id)->count() : 0;
        $managerAllCandidatesCount = $isManager ? Candidate::whereIn('recruiter_id', $teamMemberIds)->count() : 0;

        // ── Summary stats (scoped by role) ────────────────────────────────────
        $statsQuery = fn () => Candidate::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager,   fn ($q) => $q->where(fn ($q2) => $q2->where('team_manager_id', $user->id)->orWhereIn('recruiter_id', $teamMemberIds)));

        $statTotalCandidates  = $statsQuery()->count();
        $statActiveCandidates = $statsQuery()->whereIn('status', ['active', 'enrolled'])->count();
        $statInterviewsMonth  = Interview::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager,   fn ($q) => $q->whereIn('recruiter_id', $teamMemberIds))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $statValidInterviews  = Interview::where('interview_status', 'valid')
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager,   fn ($q) => $q->whereIn('recruiter_id', $teamMemberIds))
            ->count();
        $statTotalRecruiters  = $isAdmin
            ? User::recruiters()->active()->count()
            : ($isManager ? User::recruiters()->active()->whereIn('id', $teamMemberIds)->count() : 0);

        return view('admin.dashboard', [
            'todayInterviews'           => $todayInterviews,
            'tomorrowInterviews'        => $tomorrowInterviews,
            'topRecruiters'             => $topRecruiters,
            'pendingCandidates'         => $pendingCandidates,
            'pendingDateStr'            => $pendingDateStr,
            'pendingDateEndStr'         => $pendingDateEndStr,
            'pendingDays'               => $pendingDays,
            'attentionCandidates'       => $attentionCandidates,
            'isAdmin'                   => $isAdmin,
            'isManager'                 => $isManager,
            'isRecruiter'               => $isRecruiter,
            'managerMyCandidatesCount'  => $managerMyCandidatesCount,
            'managerAllCandidatesCount' => $managerAllCandidatesCount,
            // Summary stat cards
            'statTotalCandidates'       => $statTotalCandidates,
            'statActiveCandidates'      => $statActiveCandidates,
            'statInterviewsMonth'       => $statInterviewsMonth,
            'statValidInterviews'       => $statValidInterviews,
            'statTotalRecruiters'       => $statTotalRecruiters,
        ]);
    }
}
