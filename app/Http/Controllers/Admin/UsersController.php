<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
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

        $query = User::withCount('candidates')->with('teamManager');

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
        $adminExists = User::where('role', 'admin')->exists();

        return view('admin.users.index', [
            'users'       => $users,
            'search'      => $search,
            'role'        => $role,
            'managers'    => $managers,
            'adminExists' => $adminExists,
            'currentUser' => $authUser,
            'isAdmin'     => $isAdmin,
            'isManager'   => $isManager,
        ]);
    }

    public function report(Request $request, User $user)
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
        $month = $request->get('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        [$year, $mon] = explode('-', $month);
        $startDate  = Carbon::createFromDate((int) $year, (int) $mon, 1)->startOfDay();
        $endDate    = $startDate->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $startDate->daysInMonth;

        // ── Applications / Assistant from daily_logs ─────────────
        // Sum per day across all candidates managed by this recruiter
        $rawLogs = DailyLog::where('recruiter_id', $user->id)
            ->whereBetween('log_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('log_date, SUM(applications) as applications, SUM(assistant_count) as assistant_count, SUM(interview_count) as interview_count')
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get()
            ->keyBy(fn ($l) => Carbon::parse($l->log_date)->format('j')); // key by day-of-month (1-31)

        // Build full-month arrays for chart (0 on days with no log)
        $chartLabels    = [];
        $chartApps      = [];
        $chartAssistant = [];
        $detailRows     = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $log = $rawLogs->get($day);
            $chartLabels[]    = $day;
            $chartApps[]      = $log ? (int) $log->applications    : 0;
            $chartAssistant[] = $log ? (int) $log->assistant_count : 0;
            if ($log && ($log->applications > 0 || $log->assistant_count > 0 || $log->interview_count > 0)) {
                $date = Carbon::createFromDate((int) $year, (int) $mon, $day);
                $detailRows[] = [
                    'date'           => $date->format('M d, Y'),
                    'day_name'       => $date->format('D'),
                    'applications'   => (int) $log->applications,
                    'assistant'      => (int) $log->assistant_count,
                    'interviews'     => (int) $log->interview_count,
                ];
            }
        }

        $totals = [
            'applications' => array_sum($chartApps),
            'assistant'    => array_sum($chartAssistant),
            'log_interviews' => $rawLogs->sum('interview_count'),
        ];

        // ── Interviews scheduled this month ──────────────────────
        $interviews = Interview::where('recruiter_id', $user->id)
            ->whereBetween('scheduled_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('candidate')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // ── Past 13 months dropdown ───────────────────────────────
        $months = [];
        for ($i = 0; $i <= 12; $i++) {
            $m = now()->subMonths($i);
            $months[$m->format('Y-m')] = $m->format('F Y');
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
            'month'         => $month,
            'months'        => $months,
            'startDate'     => $startDate,
            'daysInMonth'   => $daysInMonth,
            'chartLabels'   => $chartLabels,
            'chartApps'     => $chartApps,
            'chartAssistant'=> $chartAssistant,
            'detailRows'    => $detailRows,
            'totals'        => $totals,
            'interviews'    => $interviews,
            'isAdmin'       => $isAdmin,
            'isManager'     => $isManager,
        ]);
    }

    public function store(Request $request)
    {
        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();

        if (!$isAdmin && !$isManager) {
            abort(403);
        }

        // Admins can create any role; managers can only create recruiters
        $allowedRoles = $isAdmin ? ['admin', 'manager', 'recruiter'] : ['recruiter'];

        // Block second admin
        if ($isAdmin && $request->input('role') === 'admin' && User::where('role', 'admin')->exists()) {
            return back()->withErrors(['role' => 'Only one admin account is allowed. The admin already exists.'])->withInput();
        }

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

        $data['password'] = Hash::make($data['password']);
        $data['email']    = strtolower($data['email']);

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
            $data = $request->validate([
                'name'            => ['required', 'string', 'max:255'],
                'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'role'            => ['required', Rule::in(['admin', 'manager', 'recruiter'])],
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

        $old           = $user->toArray();
        $data['email'] = strtolower($data['email']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

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
        unset($values['password'], $values['remember_token']);
        return $values;
    }
}
