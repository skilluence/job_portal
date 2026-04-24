<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use App\Services\CandidateOwnershipService;
use App\Services\LeaveStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();
        $search    = trim((string) $request->get('search'));
        $role      = $request->get('role');

        $query = User::withCount(['candidates', 'directManagedCandidates', 'teamCandidates'])->with('teamManager');

        if ($isManager) {
            // Manager sees only their own team members
            $query->where('team_manager_id', $authUser->id);
        } else {
            $query->when($role, fn ($q) => $q->where('role', $role));
        }

        $query->when($search, function ($q) use ($search) {
            $like = '%' . str_replace('%', '\%', $search) . '%';
            $q->where(fn ($sub) => $sub->where('name', 'like', $like)->orWhere('email', 'like', $like));
        })->latest();

        $users = $query->paginate(15)->withQueryString();

        $managers    = User::managers()->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', [
            'users'       => $users,
            'search'      => $search,
            'role'        => $role,
            'managers'    => $managers,
            'currentUser' => $authUser,
            'isAdmin'     => $isAdmin,
            'isManager'   => $isManager,
        ]);
    }

    public function report(Request $request, User $user, CandidateOwnershipService $candidateOwnershipService)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        // Recruiters have no access to any report
        if ($authUser->isRecruiter()) {
            abort(403, 'Not authorized.');
        }

        // Managers can only view their own team recruiters (not themselves, not others)
        if ($isManager) {
            if ($user->id === $authUser->id) {
                abort(403, 'You cannot view your own report.');
            }
            if ($user->team_manager_id !== $authUser->id) {
                abort(403, 'You can only view reports for members of your team.');
            }
        }

        // Admin can view any recruiter or manager (not self-blocked — they "own" everything)

        // ── Month selection ──────────────────────────────────────
        $ownedCandidatesCount = $candidateOwnershipService->totalOwnedCandidateCount($user);
        $reportOwnerIds = $user->isManager()
            ? array_values(array_unique(array_merge([$user->id], $user->teamMemberIds())))
            : [$user->id];
        $latestActivityMonth = $this->latestReportActivityMonth($reportOwnerIds);

        $month = $request->get('month', $latestActivityMonth ?? now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = $latestActivityMonth ?? now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);
        $startDate  = Carbon::createFromDate((int) $year, (int) $mon, 1)->startOfDay();
        $endDate    = $startDate->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $startDate->daysInMonth;

        // ── Applications / Assisment from daily_logs ─────────────
        // Sum per day across all candidates managed by this recruiter
        $rawLogs = DailyLog::where(fn (Builder $query) => $this->applyReportActivityScope($query, $reportOwnerIds))
            ->whereBetween('log_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('log_date, SUM(applications) as applications')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get()
            ->keyBy(fn ($l) => Carbon::parse($l->log_date)->format('j')); // key by day-of-month (1-31)

        $rawAssessmentCounts = Assessment::query()
            ->where(fn (Builder $query) => $this->applyReportActivityScope($query, $reportOwnerIds))
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('assessment_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function (Builder $fallbackQuery) use ($startDate, $endDate) {
                        $fallbackQuery->whereNull('assessment_date')
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
            })
            ->get(['id', 'assessment_date', 'created_at'])
            ->groupBy(fn (Assessment $assessment) => $this->assessmentReportLogDate($assessment)?->format('j'))
            ->map(fn ($items) => $items->count());

        $rawInterviewCounts = Interview::query()
            ->where(fn (Builder $query) => $this->applyReportActivityScope($query, $reportOwnerIds))
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('application_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function (Builder $fallbackQuery) use ($startDate, $endDate) {
                        $fallbackQuery->whereNull('application_date')
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
            })
            ->get(['id', 'application_date', 'created_at'])
            ->groupBy(fn (Interview $interview) => $this->interviewReportLogDate($interview)?->format('j'))
            ->map(fn ($items) => $items->count());

        // Build full-month arrays for chart (0 on days with no log)
        $chartLabels    = [];
        $chartApps      = [];
        $chartAssessment = [];
        $detailRows     = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $log = $rawLogs->get($day);
            $assessmentCount = (int) ($rawAssessmentCounts->get((string) $day) ?? 0);
            $interviewCount = (int) ($rawInterviewCounts->get((string) $day) ?? 0);
            $chartLabels[]    = $day;
            $chartApps[]      = $log ? (int) $log->applications    : 0;
            $chartAssessment[] = $assessmentCount;
            if (($log && $log->applications > 0) || $assessmentCount > 0 || $interviewCount > 0) {
                $date = Carbon::createFromDate((int) $year, (int) $mon, $day);
                $detailRows[] = [
                    'date'           => $date->format('M d, Y'),
                    'day_name'       => $date->format('D'),
                    'applications'   => $log ? (int) $log->applications : 0,
                    'assessment'     => $assessmentCount,
                    'interviews'     => $interviewCount,
                ];
            }
        }

        $totals = [
            'applications' => array_sum($chartApps),
            'assessment'   => $rawAssessmentCounts->sum(),
            'log_interviews' => $rawInterviewCounts->sum(),
        ];

        // ── Interviews scheduled this month ──────────────────────
        $interviews = Interview::where(fn (Builder $query) => $this->applyReportActivityScope($query, $reportOwnerIds))
            ->whereBetween('scheduled_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('candidate')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $assessments = Assessment::where(fn (Builder $query) => $this->applyReportActivityScope($query, $reportOwnerIds))
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('assessment_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function (Builder $fallbackQuery) use ($startDate, $endDate) {
                        $fallbackQuery->whereNull('assessment_date')
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
            })
            ->with('candidate')
            ->orderBy('assessment_date')
            ->orderBy('assessment_time')
            ->orderBy('created_at')
            ->get();

        // ── Past 13 months dropdown ───────────────────────────────
        $months = [];
        for ($i = 0; $i <= 12; $i++) {
            $m = now()->subMonths($i);
            $months[$m->format('Y-m')] = $m->format('F Y');
        }
        if (!isset($months[$month])) {
            $months[$month] = $startDate->format('F Y');
            krsort($months);
        }

        AuditLog::log(
            'viewed',
            'users',
            "Viewed monthly report for {$user->name} ({$month})",
            [],
            ['viewer' => $authUser->email, 'month' => $month]
        );

        return view('admin.users.report', [
            'user'          => $user,
            'ownedCandidatesCount' => $ownedCandidatesCount,
            'month'         => $month,
            'months'        => $months,
            'startDate'     => $startDate,
            'daysInMonth'   => $daysInMonth,
            'chartLabels'   => $chartLabels,
            'chartApps'     => $chartApps,
            'chartAssessment'=> $chartAssessment,
            'detailRows'    => $detailRows,
            'totals'        => $totals,
            'interviews'    => $interviews,
            'assessments'   => $assessments,
            'isAdmin'       => $isAdmin,
            'isManager'     => $isManager,
        ]);
    }

    private function applyReportActivityScope(Builder $query, array $reportOwnerIds): Builder
    {
        return $query->whereIn('recruiter_id', $reportOwnerIds);
    }

    private function latestReportActivityMonth(array $reportOwnerIds): ?string
    {
        $latestLogDate = DailyLog::query()
            ->whereIn('recruiter_id', $reportOwnerIds)
            ->max('log_date');

        $latestScheduledDate = Interview::query()
            ->whereIn('recruiter_id', $reportOwnerIds)
            ->whereNotNull('scheduled_date')
            ->max('scheduled_date');

        $latestAssessmentDate = Assessment::query()
            ->whereIn('recruiter_id', $reportOwnerIds)
            ->max('assessment_date');

        $latestAssessmentCreatedDate = Assessment::query()
            ->whereIn('recruiter_id', $reportOwnerIds)
            ->whereNull('assessment_date')
            ->max('created_at');

        $dates = array_filter([$latestLogDate, $latestScheduledDate, $latestAssessmentDate, $latestAssessmentCreatedDate]);
        if ($dates === []) {
            return null;
        }

        rsort($dates);

        return Carbon::parse($dates[0])->format('Y-m');
    }

    private function interviewReportLogDate(Interview $interview): ?Carbon
    {
        if ($interview->application_date) {
            return $interview->application_date;
        }

        return $interview->created_at;
    }

    private function assessmentReportLogDate(Assessment $assessment): ?Carbon
    {
        if ($assessment->assessment_date) {
            return $assessment->assessment_date;
        }

        return $assessment->created_at;
    }

    public function store(Request $request)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        if (!$isAdmin && !$isManager) {
            abort(403);
        }

        // Admin can create manager/recruiter. Managers can create only recruiter.
        // Admin account is unique and cannot be created from this panel.
        $allowedRoles = $isAdmin ? ['manager', 'recruiter'] : ['recruiter'];

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'role'            => ['required', Rule::in($allowedRoles)],
            'status'          => ['required', Rule::in(['active', 'inactive'])],
            'team_manager_id' => [
                Rule::requiredIf(fn () => $request->input('role') === 'recruiter'),
                'nullable',
                'exists:users,id',
            ],
        ]);

        // Managers auto-assign new recruiters to themselves
        if ($isManager) {
            $data['team_manager_id'] = $authUser->id;
        }

        // Non-recruiter roles don't have a team manager
        if (($data['role'] ?? '') !== 'recruiter') {
            $data['team_manager_id'] = null;
        }

        $plainPassword = $data['password'];
        $data['password'] = Hash::make($plainPassword);
        $data['password_plain'] = $plainPassword;
        $data['email']    = strtolower($data['email']);
        $data['inactive_reason'] = ($data['status'] ?? 'active') === 'inactive' ? 'manual' : null;
        $data['leave_override_until'] = null;

        $user = User::create($data);

        AuditLog::log('created', 'users', "Created user: {$user->name}", [], $this->sanitizeAuditValues($data));

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        // Permission checks
        if ($authUser->isRecruiter() && $authUser->id !== $user->id) {
            abort(403, 'You can only edit your own profile.');
        }
        if ($isManager && $authUser->id !== $user->id && $user->team_manager_id !== $authUser->id) {
            abort(403, 'You can only edit members of your team.');
        }

        if ($authUser->isRecruiter()) {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        } elseif ($isManager) {
            $data = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'status'   => ['required', Rule::in(['active', 'inactive'])],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            // Admin: full edit
            if ($user->isAdmin()) {
                // Admin role/email/status are locked and cannot be changed.
                $data = $request->validate([
                    'name'     => ['required', 'string', 'max:255'],
                    'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                ]);
                $data['email'] = $user->email;
                $data['status'] = $user->status;
                $data['role'] = 'admin';
                $data['team_manager_id'] = null;
            } else {
                $data = $request->validate([
                    'name'            => ['required', 'string', 'max:255'],
                    'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                    'role'            => ['required', Rule::in(['manager', 'recruiter'])],
                    'status'          => ['required', Rule::in(['active', 'inactive'])],
                    'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
                    'team_manager_id' => [
                        Rule::requiredIf(fn () => $request->input('role') === 'recruiter'),
                        'nullable',
                        'exists:users,id',
                    ],
                ]);

                if (($data['role'] ?? '') !== 'recruiter') {
                    $data['team_manager_id'] = null;
                }
            }
        }

        $old           = $user->toArray();
        $data['email'] = strtolower($data['email']);

        if (!empty($data['password'])) {
            $plainPassword = $data['password'];
            $data['password'] = Hash::make($plainPassword);
            $data['password_plain'] = $plainPassword;
        } else {
            unset($data['password']);
        }

        if (array_key_exists('status', $data)) {
            if ($data['status'] === 'inactive') {
                $data['inactive_reason'] = 'manual';
                $data['leave_override_until'] = null;
            } else {
                $data['inactive_reason'] = null;
                $data['leave_override_until'] = null;
            }
        }

        $user->update($data);

        if ($authUser->isAdmin() && (($data['status'] ?? null) === 'active')) {
            app(LeaveStatusService::class)->setAdminOverrideForActiveLeave($user);
        }

        AuditLog::log('updated', 'users', "Updated user: {$user->name}", $this->sanitizeAuditValues($old), $this->sanitizeAuditValues($data));

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function destroy(Request $request, User $user)
    {
        $authUser  = $request->user();
        $isManager = $authUser->isManager();

        if ($user->role === 'admin') {
            return back()->withErrors(['delete' => 'Admin accounts cannot be deleted.']);
        }

        if ($authUser->id === $user->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        if ($isManager && $user->team_manager_id !== $authUser->id) {
            abort(403, 'You can only delete members of your team.');
        }

        // Reassign candidates to admin
        $adminId = User::where('role', 'admin')->orderBy('id')->value('id');
        if ($adminId) {
            Candidate::withTrashed()->where('recruiter_id', $user->id)->update(['recruiter_id' => $adminId]);
        }

        // Unlink team members if deleting a manager
        if ($user->role === 'manager') {
            User::where('team_manager_id', $user->id)->update(['team_manager_id' => null]);
        }

        $name  = $user->name;
        $email = $user->email;

        AuditLog::log(
            'deleted',
            'users',
            "Deleted user: {$name}",
            ['user_id' => $user->id, 'email' => $email, 'role' => $user->role],
            ['deleted_by' => $authUser->email, 'candidates_reassigned_to_admin' => $adminId]
        );

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User \"{$name}\" deleted successfully.");
    }

    private function sanitizeAuditValues(array $values): array
    {
        unset($values['password'], $values['password_plain'], $values['remember_token']);
        return $values;
    }
}
