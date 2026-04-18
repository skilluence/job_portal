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
        return view('admin.import-export.index');
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
                'no_of_applications', 'status', 'recruiter', 'portal_login_password',
            ]);
            fputcsv($fh, [
                'John', 'Smith', 'john@example.com', '+1 (555) 123-4567',
                'Java', 'Spring Boot', 'New York', 'NY', '10001',
                'h1b', 'already_obtained',
                '3', 'active', 'Demo Recruiter', 'password123',
            ]);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportCandidates()
    {
        AuditLog::log('exported', 'candidates', 'Exported all candidates as CSV');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
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

            Candidate::with('recruiter')->chunk(300, function ($candidates) use ($fh) {
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header       = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);
        $recruiterMap = User::recruiters()->pluck('id', 'name')->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            if (count($row) !== count($header)) {
                $errors[] = "Row {$rowNum}: column count mismatch. Skipped.";
                $skipped++;
                continue;
            }

            $d = array_combine($header, array_map('trim', $row));
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

            $recruiterName = $d['recruiter'] ?? '';
            $recruiterId   = $recruiterName !== '' ? (int) ($recruiterMap[$recruiterName] ?? 0) : 0;

            if ($recruiterId <= 0) {
                $errors[] = "Row {$rowNum}: recruiter must match an existing recruiter name.";
                $skipped++;
                continue;
            }

            Candidate::create([
                'full_name'          => $fullName,
                'first_name'         => $firstName ?: null,
                'last_name'          => $lastName ?: null,
                'email_id'           => $email,
                'phone_number'       => $d['phone_number'] ?? null,
                'domain'             => $d['domain'] ?? null,
                'sub_domain'         => $d['sub_domain'] ?? null,
                'city'               => $d['city'] ?? null,
                'state_province'     => $d['state_province'] ?? null,
                'zip_code'           => $d['zip_code'] ?? null,
                'visa_immigration_status' => $d['visa_immigration_status'] ?? null,
                'work_auth_status'   => $d['work_auth_status'] ?? null,
                'no_of_applications' => is_numeric($d['no_of_applications'] ?? '') ? (int) $d['no_of_applications'] : 0,
                'interviews_count'   => 0,
                'status'             => $status,
                'recruiter_id'       => $recruiterId,
                'login_password'     => Hash::make($pass),
                'login_password_plain' => $pass,
                'created_by'         => auth()->id(),
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'candidates', "Imported {$imported} candidate(s) from CSV ({$skipped} skipped)", [], ['imported' => $imported, 'skipped' => $skipped]);

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
            fputcsv($fh, ['name', 'email', 'password', 'role', 'status']);
            fputcsv($fh, ['Jane Recruiter', 'jane@company.com', 'password123', 'recruiter', 'active']);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRecruiters()
    {
        AuditLog::log('exported', 'recruiters', 'Exported all users as CSV');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'role', 'status', 'candidates_count', 'created_at']);

            User::withCount('candidates')
                ->whereIn('role', ['recruiter', 'admin'])
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);

        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.']);
        }

        $header   = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);
        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;

            if (count($row) !== count($header)) {
                $errors[] = "Row {$rowNum}: column count mismatch. Skipped.";
                $skipped++;
                continue;
            }

            $d      = array_combine($header, array_map('trim', $row));
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

            if (!in_array($role, ['admin', 'recruiter'], true)) {
                $errors[] = "Row {$rowNum}: role must be admin or recruiter.";
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

            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($pass),
                'role'     => $role,
                'status'   => $status,
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'recruiters', "Imported {$imported} user(s) from CSV ({$skipped} skipped)", [], ['imported' => $imported, 'skipped' => $skipped]);

        $message = "Successfully imported {$imported} user(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) were skipped.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }
}
