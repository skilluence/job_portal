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
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'full_name',
                'enrollment_date',
                'sales_agent',
                'no_of_applications',
                'interviews_count',
                'status',
                'recruiter',
                'linkedin_id',
                'linkedin_password',
                'email_id',
                'email_password',
                'linkedin_updated',
                'address',
                'profile',
                'notes',
                'portal_login_password',
            ]);
            fputcsv($fh, [
                'John Smith',
                now()->toDateString(),
                'Sales Agent Name',
                '3',
                '1',
                'interview',
                'Demo Recruiter',
                'linkedin.com/in/johnsmith',
                'linkedinpass',
                'john@example.com',
                'emailpass',
                now()->toDateString(),
                '123 Main Street, City, State',
                'Experienced software developer',
                'Strong communication skills',
                'password123',
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
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($dateFrom, $dateTo, $status, $recruiterId) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'full_name', 'enrollment_date', 'sales_agent', 'no_of_applications',
                'interviews_count', 'status', 'recruiter', 'linkedin_id', 'linkedin_password',
                'email_id', 'email_password', 'linkedin_updated', 'address', 'profile',
                'notes', 'created_at',
            ]);

            Candidate::with('recruiter')
                ->when($dateFrom,    fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,      fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->when($status,      fn ($q) => $q->where('status', $status))
                ->when($recruiterId, fn ($q) => $q->where('recruiter_id', (int) $recruiterId))
                ->chunk(300, function ($candidates) use ($fh) {
                    foreach ($candidates as $candidate) {
                        fputcsv($fh, [
                            $candidate->full_name,
                            $candidate->enrollment_date?->format('Y-m-d'),
                            $candidate->sales_agent,
                            $candidate->no_of_applications,
                            $candidate->interviews_count,
                            $candidate->status,
                            $candidate->recruiter?->name,
                            $candidate->linkedin_id,
                            $candidate->linkedin_password,
                            $candidate->email_id,
                            $candidate->email_password,
                            $candidate->linkedin_updated?->format('Y-m-d'),
                            $candidate->address,
                            $candidate->profile,
                            $candidate->notes,
                            $candidate->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCandidates(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'file.mimes' => 'Only CSV files are accepted.',
            'file.max' => 'File may not exceed 10 MB.',
        ]);

        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);
        $recruiterMap = User::recruiters()->pluck('id', 'name')->toArray();

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            if (count($row) !== count($header)) {
                $errors[] = "Row {$rowNum}: column count does not match header. Row skipped.";
                $skipped++;
                continue;
            }

            $d = array_combine($header, array_map('trim', $row));
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid row format. Row skipped.";
                $skipped++;
                continue;
            }

            $fullName = $d['full_name'] ?? '';
            $email = strtolower($d['email_id'] ?? '');
            $portalPass = $d['portal_login_password'] ?? ($d['login_password'] ?? '');
            $status = strtolower($d['status'] ?? 'active');

            if ($fullName === '' || $email === '' || $portalPass === '') {
                $errors[] = "Row {$rowNum}: full_name, email_id, and portal_login_password are required.";
                $skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: email_id is not a valid email.";
                $skipped++;
                continue;
            }

            if (!in_array($status, self::CANDIDATE_STATUSES, true)) {
                $status = 'active';
            }

            if (Candidate::where('email_id', $email)->exists()) {
                $errors[] = "Row {$rowNum}: email_id '{$email}' already exists. Row skipped.";
                $skipped++;
                continue;
            }

            $recruiterName = $d['recruiter'] ?? '';
            $recruiterId = $recruiterName !== '' ? (int) ($recruiterMap[$recruiterName] ?? 0) : 0;

            if ($recruiterId <= 0) {
                $errors[] = "Row {$rowNum}: recruiter is required and must match an existing recruiter name.";
                $skipped++;
                continue;
            }

            Candidate::create([
                'full_name' => $fullName,
                'enrollment_date' => $d['enrollment_date'] ?: null,
                'sales_agent' => $d['sales_agent'] ?: null,
                'no_of_applications' => is_numeric($d['no_of_applications'] ?? '') ? (int) $d['no_of_applications'] : 0,
                'interviews_count' => is_numeric($d['interviews_count'] ?? '') ? (int) $d['interviews_count'] : 0,
                'status' => $status,
                'recruiter_id' => $recruiterId,
                'linkedin_id' => $d['linkedin_id'] ?: null,
                'linkedin_password' => $d['linkedin_password'] ?: null,
                'email_id' => $email,
                'email_password' => $d['email_password'] ?: null,
                'linkedin_updated' => $d['linkedin_updated'] ?: null,
                'address' => $d['address'] ?: null,
                'profile' => $d['profile'] ?: null,
                'notes' => $d['notes'] ?: null,
                'login_password' => Hash::make($portalPass),
                'login_password_plain' => $portalPass,
                'created_by' => auth()->id(),
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log(
            'imported',
            'candidates',
            "Imported {$imported} candidate(s) from CSV ({$skipped} skipped)",
            [],
            ['imported' => $imported, 'skipped' => $skipped]
        );

        $message = "Successfully imported {$imported} candidate(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }

    public function downloadRecruiterTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="recruiters_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'password', 'role', 'status', 'team_manager_email']);
            fputcsv($fh, ['Jane Recruiter', 'jane@company.com', 'password123', 'recruiter', 'active', 'admin@skilluence.com']);
            fputcsv($fh, ['Bob Manager', 'bob@company.com', 'password123', 'manager', 'active', 'admin@skilluence.com']);
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

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="recruiters_' . now()->format('Y-m-d') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $allowedRoles = ['recruiter', 'manager', 'admin'];

        $callback = function () use ($dateFrom, $dateTo, $role, $status, $allowedRoles) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'role', 'team_manager', 'status', 'candidates_count', 'created_at']);

            User::with('teamManager')
                ->withCount('candidates')
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
                            $user->teamManager?->name,
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'file.mimes' => 'Only CSV files are accepted.',
        ]);

        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);
        $managerMap = User::whereIn('role', ['manager', 'admin'])->pluck('id', 'email')->toArray();

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            if (count($row) !== count($header)) {
                $errors[] = "Row {$rowNum}: column count does not match header. Row skipped.";
                $skipped++;
                continue;
            }

            $d = array_combine($header, array_map('trim', $row));
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid row format. Row skipped.";
                $skipped++;
                continue;
            }

            $name = $d['name'] ?? '';
            $email = strtolower($d['email'] ?? '');
            $pass = $d['password'] ?? '';
            $role = strtolower($d['role'] ?? '');
            $status = strtolower($d['status'] ?? 'active');

            if ($role === 'team_manager') {
                $role = 'manager';
            }

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

            if (!in_array($role, ['admin', 'recruiter', 'manager'], true)) {
                $errors[] = "Row {$rowNum}: role must be admin, recruiter, or manager.";
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

            $managerEmail = strtolower($d['team_manager_email'] ?? '');
            $managerId = $managerEmail !== '' ? (int) ($managerMap[$managerEmail] ?? 0) : 0;

            if ($role === 'recruiter' && $managerId <= 0) {
                $managerId = (int) User::where('role', 'admin')->orderBy('id')->value('id');
            }

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($pass),
                'role' => $role,
                'status' => $status,
                'team_manager_id' => $managerId ?: null,
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log(
            'imported',
            'recruiters',
            "Imported {$imported} recruiter(s) from CSV ({$skipped} skipped)",
            [],
            ['imported' => $imported, 'skipped' => $skipped]
        );

        $message = "Successfully imported {$imported} recruiter(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }
}
