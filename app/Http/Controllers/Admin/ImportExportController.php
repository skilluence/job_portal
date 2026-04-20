<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ImportExportController extends Controller
{
    private const CANDIDATE_STATUSES = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive'];

    public function index()
    {
        $history = AuditLog::whereIn('action', ['imported', 'exported'])
            ->whereIn('module', ['candidates', 'recruiters'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $recruiters = User::recruiters()->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.import-export.index', compact('history', 'recruiters'));
    }

    public function downloadCandidateTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'first_name', 'last_name', 'email_id', 'phone_number',
                'domain', 'sub_domain', 'city', 'state_province', 'zip_code',
                'visa_immigration_status', 'work_auth_status',
                'no_of_applications', 'status', 'recruiter', 'team_manager', 'portal_login_password',
            ]);
            // Example row 1 — assigned via recruiter (team_manager left blank)
            fputcsv($fh, [
                'John', 'Smith', 'john@example.com', '+1 (555) 123-4567',
                'Java', 'Spring Boot', 'New York', 'NY', '10001',
                'h1b', 'already_obtained',
                '3', 'active', 'Demo Recruiter', '', 'password123',
            ]);
            // Example row 2 — assigned via team_manager only (recruiter left blank)
            fputcsv($fh, [
                'Jane', 'Doe', 'jane@example.com', '+1 (555) 987-6543',
                'Data Science', 'Python', 'Austin', 'TX', '73301',
                'opt_f1', 'not_applied',
                '2', 'active', '', 'Demo Manager', 'password456',
            ]);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCandidates(Request $request)
    {
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');
        $status      = $request->input('status');
        $recruiterId = $request->input('recruiter_id');

        $parts = array_filter([
            $dateFrom    ? "from:{$dateFrom}"         : null,
            $dateTo      ? "to:{$dateTo}"             : null,
            $status      ? "status:{$status}"         : null,
            $recruiterId ? "recruiter:{$recruiterId}" : null,
        ]);
        $desc = 'Exported candidates as CSV' . ($parts ? ' [' . implode(', ', $parts) . ']' : '');

        AuditLog::log('exported', 'candidates', $desc);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($dateFrom, $dateTo, $status, $recruiterId) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'full_name', 'first_name', 'last_name', 'email_id', 'phone_number',
                'domain', 'sub_domain', 'city', 'state_province', 'zip_code', 'country',
                'visa_immigration_status', 'work_auth_status',
                'no_of_applications', 'interviews_count', 'status', 'recruiter',
                'github_url', 'linkedin_url',
                'masters_university', 'masters_program', 'masters_country',
                'bachelors_university', 'bachelors_program', 'bachelors_country',
                'created_at',
            ]);

            Candidate::with('recruiter')
                ->when($dateFrom,    fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,      fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->when($status,      fn ($q) => $q->where('status', $status))
                ->when($recruiterId, fn ($q) => $q->where('recruiter_id', (int) $recruiterId))
                ->chunk(300, function ($candidates) use ($fh) {
                    foreach ($candidates as $c) {
                        fputcsv($fh, [
                            $c->full_name,
                            $c->first_name,
                            $c->last_name,
                            $c->email_id,
                            $c->phone_number,
                            $c->domain,
                            $c->sub_domain,
                            $c->city,
                            $c->state_province,
                            $c->zip_code,
                            $c->country,
                            $c->visa_immigration_status,
                            $c->work_auth_status,
                            $c->no_of_applications,
                            $c->interviews_count,
                            $c->status,
                            $c->recruiter?->name,
                            $c->github_url,
                            $c->linkedin_url,
                            $c->masters_university,
                            $c->masters_program,
                            $c->masters_country,
                            $c->bachelors_university,
                            $c->bachelors_program,
                            $c->bachelors_country,
                            $c->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCandidates(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload a CSV file (.csv or .txt).']);
        }

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);

        // Build case-insensitive name → id maps for recruiter and manager lookup
        $recruiterMapRaw = User::recruiters()->pluck('id', 'name')->toArray();
        $managerMapRaw   = User::managers()->pluck('id', 'name')->toArray();
        $recruiterMap    = array_combine(
            array_map('strtolower', array_keys($recruiterMapRaw)),
            array_values($recruiterMapRaw)
        );
        $managerMap      = array_combine(
            array_map('strtolower', array_keys($managerMapRaw)),
            array_values($managerMapRaw)
        );

        // Pre-load each recruiter's assigned team_manager_id for auto-derivation
        $recruiterTeamManagers = User::recruiters()->whereNotNull('team_manager_id')
            ->pluck('team_manager_id', 'id')->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            // Skip completely blank rows
            if (count(array_filter($row, fn ($v) => trim($v) !== '')) === 0) {
                continue;
            }

            // Flexible column mapping — pad short rows, truncate long rows
            $rowValues = array_map('trim', $row);
            $headerCount = count($header);
            if (count($rowValues) < $headerCount) {
                $rowValues = array_pad($rowValues, $headerCount, '');
            } elseif (count($rowValues) > $headerCount) {
                $rowValues = array_slice($rowValues, 0, $headerCount);
            }

            $d = array_combine($header, $rowValues);
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid format. Skipped.";
                $skipped++;
                continue;
            }

            // Support both "first_name+last_name" and "full_name" columns
            $firstName = $d['first_name'] ?? '';
            $lastName  = $d['last_name']  ?? '';
            $fullName  = $d['full_name']  ?? trim("{$firstName} {$lastName}");
            $email     = strtolower($d['email_id'] ?? '');
            $pass      = $d['portal_login_password'] ?? ($d['login_password'] ?? '');
            $status    = strtolower($d['status'] ?? 'active');

            if ($fullName === '' || $email === '' || $pass === '') {
                $errors[] = "Row {$rowNum}: first_name+last_name (or full_name), email_id, and portal_login_password are required.";
                $skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: email_id '{$email}' is invalid.";
                $skipped++;
                continue;
            }

            if (!in_array($status, self::CANDIDATE_STATUSES, true)) {
                $status = 'active';
            }

            if (Candidate::where('email_id', $email)->exists()) {
                $errors[] = "Row {$rowNum}: email_id '{$email}' already exists. Skipped.";
                $skipped++;
                continue;
            }

            // ── Recruiter / Team Manager — exactly one must be set ────────────
            $recruiterName   = trim($d['recruiter']     ?? '');
            $teamManagerName = trim($d['team_manager']  ?? '');

            $hasRecruiter   = $recruiterName   !== '';
            $hasManager     = $teamManagerName !== '';

            if ($hasRecruiter && $hasManager) {
                $errors[] = "Row {$rowNum}: provide either 'recruiter' OR 'team_manager', not both.";
                $skipped++;
                continue;
            }

            if (!$hasRecruiter && !$hasManager) {
                $errors[] = "Row {$rowNum}: either 'recruiter' or 'team_manager' is required.";
                $skipped++;
                continue;
            }

            $recruiterId   = null;
            $teamManagerId = null;

            if ($hasRecruiter) {
                $recruiterId = (int) ($recruiterMap[strtolower($recruiterName)] ?? 0) ?: null;
                if (!$recruiterId) {
                    $errors[] = "Row {$rowNum}: recruiter '{$recruiterName}' not found.";
                    $skipped++;
                    continue;
                }
                // Auto-derive team manager from recruiter's assigned manager
                $teamManagerId = $recruiterTeamManagers[$recruiterId] ?? null;
            } else {
                $teamManagerId = (int) ($managerMap[strtolower($teamManagerName)] ?? 0) ?: null;
                if (!$teamManagerId) {
                    $errors[] = "Row {$rowNum}: team_manager '{$teamManagerName}' not found.";
                    $skipped++;
                    continue;
                }
            }

            // Helper: treat empty string as null for optional fields
            $col = fn (string $key) => ($d[$key] ?? '') !== '' ? $d[$key] : null;

            Candidate::create([
                'full_name'               => $fullName,
                'first_name'              => $firstName ?: null,
                'last_name'               => $lastName ?: null,
                'email_id'                => $email,
                'phone_number'            => $col('phone_number'),
                'domain'                  => $col('domain'),
                'sub_domain'              => $col('sub_domain'),
                'city'                    => $col('city'),
                'state_province'          => $col('state_province'),
                'zip_code'                => $col('zip_code'),
                'visa_immigration_status' => $col('visa_immigration_status'),
                'work_auth_status'        => $col('work_auth_status'),
                'no_of_applications'      => is_numeric($d['no_of_applications'] ?? '') ? (int) $d['no_of_applications'] : 0,
                'interviews_count'        => 0,
                'status'                  => $status,
                'recruiter_id'            => $recruiterId,
                'team_manager_id'         => $teamManagerId,
                'login_password'          => Hash::make($pass),
                'login_password_plain'    => $pass,
                'created_by'              => auth()->id(),
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'candidates', "Imported {$imported} candidate(s) from CSV ({$skipped} skipped)", [], [
            'imported'   => $imported,
            'skipped'    => $skipped,
            'has_errors' => count($errors) > 0,
        ]);

        $message = "Successfully imported {$imported} candidate(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }

    public function downloadRecruiterTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="recruiters_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'password', 'role', 'status', 'team_manager']);
            fputcsv($fh, ['Jane Recruiter', 'jane@company.com', 'password123', 'recruiter', 'active', 'Demo Manager']);
            fputcsv($fh, ['Tom Manager', 'tom@company.com', 'password123', 'manager', 'active', '']);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRecruiters(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $role     = $request->input('role');
        $status   = $request->input('status');

        $parts = array_filter([
            $dateFrom ? "from:{$dateFrom}" : null,
            $dateTo   ? "to:{$dateTo}"     : null,
            $role     ? "role:{$role}"     : null,
            $status   ? "status:{$status}" : null,
        ]);
        $desc = 'Exported recruiters as CSV' . ($parts ? ' [' . implode(', ', $parts) . ']' : '');

        AuditLog::log('exported', 'recruiters', $desc);

        $allowedRoles = ['recruiter', 'admin'];

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($dateFrom, $dateTo, $role, $status, $allowedRoles) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'role', 'status', 'candidates_count', 'created_at']);

            User::withCount('candidates')
                ->whereIn('role', $role && in_array($role, $allowedRoles) ? [$role] : $allowedRoles)
                ->when($status,   fn ($q) => $q->where('status', $status))
                ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->chunk(300, function ($users) use ($fh) {
                    foreach ($users as $user) {
                        fputcsv($fh, [
                            $user->name,
                            $user->email,
                            $user->role,
                            $user->status,
                            $user->candidates_count,
                            $user->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importRecruiters(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $authUser  = $request->user();
        $isAdmin   = $authUser->isAdmin();
        $isManager = $authUser->isManager();
        // Managers can only import recruiter-role users, never admin
        $allowedRoles = $isAdmin ? ['admin', 'recruiter', 'manager'] : ['recruiter'];

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload a CSV file (.csv or .txt).']);
        }

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);

        // Case-insensitive manager name → id map
        $managerMapRaw = User::managers()->pluck('id', 'name')->toArray();
        $managerMap    = array_combine(
            array_map('strtolower', array_keys($managerMapRaw)),
            array_values($managerMapRaw)
        );

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            // Skip blank rows
            if (count(array_filter($row, fn ($v) => trim($v) !== '')) === 0) {
                continue;
            }

            // Flexible column mapping
            $rowValues   = array_map('trim', $row);
            $headerCount = count($header);
            if (count($rowValues) < $headerCount) {
                $rowValues = array_pad($rowValues, $headerCount, '');
            } elseif (count($rowValues) > $headerCount) {
                $rowValues = array_slice($rowValues, 0, $headerCount);
            }

            $d      = array_combine($header, $rowValues);
            $name   = $d['name'] ?? '';
            $email  = strtolower($d['email'] ?? '');
            $pass   = $d['password'] ?? '';
            $role   = strtolower($d['role'] ?? '');
            $status = strtolower($d['status'] ?? 'active');

            if ($name === '' || $email === '' || $pass === '' || $role === '') {
                $errors[] = "Row {$rowNum}: name, email, password, and role are required.";
                $skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: '{$email}' is not a valid email.";
                $skipped++;
                continue;
            }

            if (!in_array($role, $allowedRoles, true)) {
                $errors[] = $isAdmin
                    ? "Row {$rowNum}: role must be admin, manager, or recruiter."
                    : "Row {$rowNum}: role must be recruiter (managers cannot import admin/manager users).";
                $skipped++;
                continue;
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if (User::where('email', $email)->exists()) {
                $errors[] = "Row {$rowNum}: email '{$email}' already exists.";
                $skipped++;
                continue;
            }

            // Resolve team_manager_id for recruiter-role users
            $teamManagerId = null;
            if ($role === 'recruiter') {
                $teamManagerName = trim($d['team_manager'] ?? '');
                if ($teamManagerName !== '') {
                    $mid = (int) ($managerMap[strtolower($teamManagerName)] ?? 0);
                    if (!$mid) {
                        $errors[] = "Row {$rowNum}: team_manager '{$teamManagerName}' not found. Check the name matches an existing manager.";
                        $skipped++;
                        continue;
                    }
                    $teamManagerId = $mid;
                }
            }

            User::create([
                'name'            => $name,
                'email'           => $email,
                'password'        => Hash::make($pass),
                'role'            => $role,
                'status'          => $status,
                'team_manager_id' => $teamManagerId,
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'recruiters', "Imported {$imported} user(s) from CSV ({$skipped} skipped)", [], [
            'imported'   => $imported,
            'skipped'    => $skipped,
            'has_errors' => count($errors) > 0,
        ]);

        $message = "Successfully imported {$imported} user(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }
}
