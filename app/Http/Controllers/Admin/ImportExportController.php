<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use App\Services\DailyLogSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ImportExportController extends Controller
{
    private const CANDIDATE_STATUSES = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive'];

    public function index()
    {
        $history = AuditLog::whereIn('action', ['imported', 'exported'])
            ->whereIn('module', ['candidates', 'recruiters', 'applications', 'interviews', 'assessments'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $recruiters = User::recruiters()->active()->orderBy('name')->get(['id', 'name']);
        $managers = User::managers()->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.import-export.index', compact('history', 'recruiters', 'managers'));
    }

    public function downloadCandidateTemplate()
    {
        if (!request()->user() || !request()->user()->isAdmin()) {
            abort(403, 'Only admin can download candidate import template.');
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'nationality',
                'email_id', 'phone_number', 'domain', 'sub_domain', 'ssn', 'date_of_arrival_usa',
                'current_salary', 'expected_salary',
                'street_address', 'apartment_unit', 'city', 'state_province', 'zip_code', 'country',
                'visa_immigration_status', 'work_auth_status', 'open_to_relocation', 'preferred_city', 'visa_expiry_date',
                'marketing_phone', 'marketing_email', 'marketing_email_password',
                'marketing_linkedin_id', 'marketing_linkedin_password',
                'github_url', 'linkedin_url', 'portfolio_url',
                'masters_university', 'masters_program', 'masters_start', 'masters_end', 'masters_country',
                'bachelors_university', 'bachelors_program', 'bachelors_start', 'bachelors_end', 'bachelors_country',
                'recruiter_notes', 'no_of_applications', 'status', 'recruiter', 'team_manager', 'portal_login_password',
            ]);
            // Example row 1 — assigned via recruiter (team_manager left blank)
            // preferred_city: comma-separated list of preferred cities (e.g. "New York,Austin")
            fputcsv($fh, [
                'John', 'A', 'Smith', '1997-08-14', 'male', 'Indian',
                'john@example.com', '+1 (555) 123-4567', 'Java', 'Spring Boot,Microservices,Docker', '123-45-6789', '2023-08-15',
                '75000', '95000',
                '123 Main Street', 'Apt 4B', 'New York', 'New York', '10001', 'United States',
                'h1b', 'already_obtained', '1', 'New York,Austin', '2027-10-01',
                '+1 (555) 111-2222', 'john.marketing@example.com', 'mailSecret123',
                'johnsmith-linkedin', 'linkedinSecret123',
                'https://github.com/johnsmith', 'https://linkedin.com/in/johnsmith', 'https://johnsmith.dev',
                'University of Texas at Dallas', 'MS Computer Science', '2021-08-01', '2023-05-15', 'United States',
                'Mumbai University', 'B.E. Computer Engineering', '2016-08-01', '2020-05-15', 'India',
                'Strong communication and client-facing profile.', '3', 'active', 'Demo Recruiter', '', 'password123',
            ]);
            // Example row 2 — assigned via team_manager only (recruiter left blank)
            fputcsv($fh, [
                'Jane', '', 'Doe', '1998-03-22', 'female', 'Nepalese',
                'jane@example.com', '+1 (555) 987-6543', 'Data Science', 'Python,ML,SQL', '', '2024-01-10',
                '68000', '90000',
                '500 Market Street', '', 'Austin', 'Texas', '73301', 'United States',
                'opt_f1', 'not_applied', '0', 'Austin', '',
                '', '', '', '', '',
                'https://github.com/janedoe', 'https://linkedin.com/in/janedoe', 'https://janedoe.dev',
                'Arizona State University', 'MS Data Science', '2022-08-01', '2024-05-15', 'United States',
                'Tribhuvan University', 'B.Tech IT', '2017-08-01', '2021-05-15', 'Nepal',
                'Prefers backend and data engineering roles.', '2', 'enrolled', '', 'Demo Manager', 'password456',
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
        $owner       = (string) $request->input('owner', '');
        $ownerType   = null;
        $ownerId     = null;
        $recruiterId = null;
        $teamManagerId = null;

        if (preg_match('/^(recruiter|manager):(\d+)$/', $owner, $matches)) {
            $ownerType = $matches[1];
            $ownerId = (int) $matches[2];

            if ($ownerType === 'recruiter') {
                $recruiterId = $ownerId;
            } else {
                $teamManagerId = $ownerId;
            }
        } else {
            $recruiterId = $request->integer('recruiter_id') ?: null;
            $teamManagerId = $request->integer('team_manager_id') ?: null;

            if ($recruiterId) {
                $teamManagerId = null;
                $ownerType = 'recruiter';
                $ownerId = $recruiterId;
            } elseif ($teamManagerId) {
                $recruiterId = null;
                $ownerType = 'manager';
                $ownerId = $teamManagerId;
            }
        }

        $parts = array_filter([
            $dateFrom    ? "from:{$dateFrom}"         : null,
            $dateTo      ? "to:{$dateTo}"             : null,
            $status      ? "status:{$status}"         : null,
            $ownerType && $ownerId ? "owner:{$ownerType}:{$ownerId}" : null,
        ]);
        $desc = 'Exported candidates as CSV' . ($parts ? ' [' . implode(', ', $parts) . ']' : '');

        AuditLog::log('exported', 'candidates', $desc);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidates_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($dateFrom, $dateTo, $status, $recruiterId, $teamManagerId) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'id',
                'full_name',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'gender',
                'nationality',
                'email_id',
                'phone_number',
                'domain',
                'sub_domain',
                'ssn',
                'date_of_arrival_usa',
                'current_salary',
                'expected_salary',
                'street_address',
                'apartment_unit',
                'city',
                'state_province',
                'zip_code',
                'country',
                'visa_immigration_status',
                'work_auth_status',
                'open_to_relocation',
                'preferred_city',
                'visa_expiry_date',
                'marketing_phone',
                'marketing_email',
                'marketing_email_password',
                'marketing_linkedin_id',
                'marketing_linkedin_password',
                'github_url',
                'linkedin_url',
                'portfolio_url',
                'masters_university',
                'masters_program',
                'masters_start',
                'masters_end',
                'masters_country',
                'bachelors_university',
                'bachelors_program',
                'bachelors_start',
                'bachelors_end',
                'bachelors_country',
                'recruiter_notes',
                'no_of_applications',
                'interviews_count',
                'status',
                'interview_status',
                'recruiter_id',
                'recruiter_name',
                'team_manager_id',
                'team_manager_name',
                'created_by',
                'created_by_name',
                'enrollment_date',
                'sales_agent',
                'linkedin_id',
                'linkedin_password',
                'email_password',
                'linkedin_updated',
                'address',
                'profile',
                'notes',
                'cv_document_url',
                'candidate_details_document_url',
                'speedy_apply_json_document_url',
                'agreement_document_url',
                'resume_document_urls',
                'cv_file_path',
                'candidate_details_file_path',
                'speedy_apply_json_path',
                'agreement_file_path',
                'created_at',
                'updated_at',
            ]);

            Candidate::with([
                'recruiter:id,name',
                'teamManager:id,name',
                'creator:id,name',
                'resumes:id,candidate_id,designation,file_path,original_filename',
            ])
                ->when($dateFrom,    fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,      fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->when($status,      fn ($q) => $q->where('status', $status))
                ->when($recruiterId, fn ($q) => $q->where('recruiter_id', $recruiterId))
                ->when($teamManagerId, function ($q) use ($teamManagerId) {
                    $q->where(function ($sub) use ($teamManagerId) {
                        $sub->where('team_manager_id', $teamManagerId)
                            ->orWhereHas('recruiter', fn ($rq) => $rq->where('team_manager_id', $teamManagerId));
                    });
                })
                ->chunk(300, function ($candidates) use ($fh) {
                    foreach ($candidates as $c) {
                        $resumeDocumentUrls = $c->resumes->map(function ($resume) use ($c) {
                            $url = route('admin.candidates.resumes.download', [$c, $resume]);
                            return trim(($resume->designation ?: 'Resume') . ': ' . $url);
                        })->implode(' | ');

                        fputcsv($fh, [
                            $c->id,
                            $c->full_name,
                            $c->first_name,
                            $c->middle_name,
                            $c->last_name,
                            $c->date_of_birth?->format('Y-m-d'),
                            $c->gender,
                            $c->nationality,
                            $c->email_id,
                            $c->phone_number,
                            $c->domain,
                            $c->sub_domain,
                            $c->ssn,
                            $c->date_of_arrival_usa?->format('Y-m-d'),
                            $c->current_salary,
                            $c->expected_salary,
                            $c->street_address,
                            $c->apartment_unit,
                            $c->city,
                            $c->state_province,
                            $c->zip_code,
                            $c->country,
                            $c->visa_immigration_status,
                            $c->work_auth_status,
                            $c->open_to_relocation ? 'Yes' : 'No',
                            $c->preferred_city,
                            $c->visa_expiry_date?->format('Y-m-d'),
                            $c->marketing_phone,
                            $c->marketing_email,
                            $c->marketing_email_password,
                            $c->marketing_linkedin_id,
                            $c->marketing_linkedin_password,
                            $c->github_url,
                            $c->linkedin_url,
                            $c->portfolio_url,
                            $c->masters_university,
                            $c->masters_program,
                            $c->masters_start?->format('Y-m-d'),
                            $c->masters_end?->format('Y-m-d'),
                            $c->masters_country,
                            $c->bachelors_university,
                            $c->bachelors_program,
                            $c->bachelors_start?->format('Y-m-d'),
                            $c->bachelors_end?->format('Y-m-d'),
                            $c->bachelors_country,
                            $c->recruiter_notes,
                            $c->no_of_applications,
                            $c->interviews_count,
                            $c->status,
                            $c->interview_status,
                            $c->recruiter_id,
                            $c->recruiter?->name,
                            $c->team_manager_id,
                            $c->teamManager?->name,
                            $c->created_by,
                            $c->creator?->name,
                            $c->enrollment_date,
                            $c->sales_agent,
                            $c->linkedin_id,
                            $c->linkedin_password,
                            $c->email_password,
                            $c->linkedin_updated,
                            $c->address,
                            $c->profile,
                            $c->notes,
                            $c->cv_file_path ? route('admin.candidates.files', [$c, 'cv']) : '',
                            $c->candidate_details_file_path ? route('admin.candidates.files', [$c, 'details']) : '',
                            $c->speedy_apply_json_path ? route('admin.candidates.files', [$c, 'speedy']) : '',
                            $c->agreement_file_path ? route('admin.candidates.files', [$c, 'agreement']) : '',
                            $resumeDocumentUrls,
                            $c->cv_file_path,
                            $c->candidate_details_file_path,
                            $c->speedy_apply_json_path,
                            $c->agreement_file_path,
                            $c->created_at?->format('Y-m-d H:i:s'),
                            $c->updated_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCandidates(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return back()->withErrors([
                'file' => 'Only admin can import candidates.',
            ]);
        }

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

            // Required columns follow Add Candidate form requirements.
            $firstName = trim((string) ($d['first_name'] ?? ''));
            $middleName = trim((string) ($d['middle_name'] ?? ''));
            $lastName = trim((string) ($d['last_name'] ?? ''));
            $email = strtolower(trim((string) ($d['email_id'] ?? '')));
            $pass = trim((string) ($d['portal_login_password'] ?? ($d['login_password'] ?? '')));
            $status = strtolower(trim((string) ($d['status'] ?? '')));
            $noOfApplicationsRaw = trim((string) ($d['no_of_applications'] ?? ''));

            // Backward compatibility: if only full_name exists, split into first/last.
            if (($firstName === '' || $lastName === '') && !empty($d['full_name'])) {
                $parts = preg_split('/\s+/', trim((string) $d['full_name'])) ?: [];
                if ($firstName === '' && isset($parts[0])) {
                    $firstName = $parts[0];
                }
                if ($lastName === '' && count($parts) > 1) {
                    $lastName = implode(' ', array_slice($parts, 1));
                }
            }

            if (
                $firstName === ''
                || $lastName === ''
                || $email === ''
                || $pass === ''
                || $status === ''
                || $noOfApplicationsRaw === ''
            ) {
                $errors[] = "Row {$rowNum}: required fields missing. Must include first_name, last_name, email_id, portal_login_password, status, and no_of_applications.";
                $skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: email_id '{$email}' is invalid.";
                $skipped++;
                continue;
            }

            if (strlen($pass) < 8) {
                $errors[] = "Row {$rowNum}: portal_login_password must be at least 8 characters.";
                $skipped++;
                continue;
            }

            if (!in_array($status, self::CANDIDATE_STATUSES, true)) {
                $errors[] = "Row {$rowNum}: status '{$status}' is invalid.";
                $skipped++;
                continue;
            }

            if (!is_numeric($noOfApplicationsRaw) || (int) $noOfApplicationsRaw < 0) {
                $errors[] = "Row {$rowNum}: no_of_applications must be a non-negative number.";
                $skipped++;
                continue;
            }

            foreach (['github_url', 'linkedin_url', 'portfolio_url'] as $urlField) {
                $urlValue = trim((string) ($d[$urlField] ?? ''));
                if ($urlValue !== '' && !filter_var($urlValue, FILTER_VALIDATE_URL)) {
                    $errors[] = "Row {$rowNum}: {$urlField} must be a valid URL.";
                    $skipped++;
                    continue 2;
                }
            }

            $marketingEmail = trim((string) ($d['marketing_email'] ?? ''));
            if ($marketingEmail !== '' && !filter_var($marketingEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNum}: marketing_email is invalid.";
                $skipped++;
                continue;
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

            // Helper: treat empty string as null for optional fields.
            $col = fn (string $key) => ($d[$key] ?? '') !== '' ? trim((string) $d[$key]) : null;
            $subDomain = $this->normalizeCommaSeparated($d['sub_domain'] ?? null);
            $openToRelocation = $this->normalizeBoolean($d['open_to_relocation'] ?? null);
            $country = $col('country') ?: 'United States';

            $candidatePayload = [
                // Personal
                'full_name' => trim("{$firstName} {$middleName} {$lastName}"),
                'first_name' => $firstName,
                'middle_name' => $middleName ?: null,
                'last_name' => $lastName,
                'date_of_birth' => $this->normalizeDate($d['date_of_birth'] ?? null),
                'gender' => $col('gender'),
                'nationality' => $col('nationality'),
                'email_id' => $email,
                'phone_number' => $col('phone_number'),
                'domain' => $col('domain'),
                'sub_domain' => $subDomain,
                'ssn' => $col('ssn'),
                'date_of_arrival_usa' => $this->normalizeDate($d['date_of_arrival_usa'] ?? null),
                'current_salary' => $this->normalizeDecimal($d['current_salary'] ?? null),
                'expected_salary' => $this->normalizeDecimal($d['expected_salary'] ?? null),

                // Address and visa
                'street_address' => $col('street_address'),
                'apartment_unit' => $col('apartment_unit'),
                'city' => $col('city'),
                'state_province' => $col('state_province'),
                'zip_code' => $col('zip_code'),
                'country' => $country,
                'visa_immigration_status' => $col('visa_immigration_status'),
                'work_auth_status' => $col('work_auth_status'),
                'open_to_relocation' => $openToRelocation ?? false,
                'preferred_city' => $col('preferred_city'),
                'visa_expiry_date' => $this->normalizeDate($d['visa_expiry_date'] ?? null),

                // Marketing
                'marketing_phone' => $col('marketing_phone'),
                'marketing_email' => $col('marketing_email'),
                'marketing_email_password' => $col('marketing_email_password'),
                'marketing_linkedin_id' => $col('marketing_linkedin_id'),
                'marketing_linkedin_password' => $col('marketing_linkedin_password'),
                'github_url' => $col('github_url'),
                'linkedin_url' => $col('linkedin_url'),

                // Education
                'masters_university' => $col('masters_university'),
                'masters_program' => $col('masters_program'),
                'masters_start' => $this->normalizeDate($d['masters_start'] ?? null),
                'masters_end' => $this->normalizeDate($d['masters_end'] ?? null),
                'masters_country' => $col('masters_country'),
                'bachelors_university' => $col('bachelors_university'),
                'bachelors_program' => $col('bachelors_program'),
                'bachelors_start' => $this->normalizeDate($d['bachelors_start'] ?? null),
                'bachelors_end' => $this->normalizeDate($d['bachelors_end'] ?? null),
                'bachelors_country' => $col('bachelors_country'),

                // Notes and portal
                'recruiter_notes' => $col('recruiter_notes'),
                'no_of_applications' => (int) $noOfApplicationsRaw,
                'interviews_count' => 0,
                'status' => $status,
                'recruiter_id' => $recruiterId,
                'team_manager_id' => $teamManagerId,
                'login_password' => Hash::make($pass),
                'login_password_plain' => $pass,
                'created_by' => auth()->id(),
            ];

            if ($this->portfolioColumnExists()) {
                $candidatePayload['portfolio_url'] = $col('portfolio_url');
            }

            Candidate::create($candidatePayload);

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
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $roleFilter = strtolower((string) $request->input('role_filter', $request->input('role', 'all')));
        $status     = $request->input('status');

        $allowedRoleFilters = ['all', 'manager', 'recruiter'];
        if (!in_array($roleFilter, $allowedRoleFilters, true)) {
            $roleFilter = 'all';
        }

        $exportRoles = match ($roleFilter) {
            'manager' => ['manager'],
            'recruiter' => ['recruiter'],
            default => ['manager', 'recruiter'],
        };

        $parts = array_filter([
            $dateFrom ? "from:{$dateFrom}" : null,
            $dateTo   ? "to:{$dateTo}"     : null,
            "role_filter:{$roleFilter}",
            $status   ? "status:{$status}" : null,
        ]);
        $desc = 'Exported recruiters/team managers as CSV' . ($parts ? ' [' . implode(', ', $parts) . ']' : '');

        AuditLog::log('exported', 'recruiters', $desc);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_' . now()->format('Y-m-d') . '.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($dateFrom, $dateTo, $status, $exportRoles) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['name', 'email', 'role', 'status', 'team_manager', 'candidates_count', 'created_at']);

            User::with(['teamManager:id,name'])
                ->withCount(['candidates', 'directManagedCandidates', 'teamCandidates'])
                ->whereIn('role', $exportRoles)
                ->when($status,   fn ($q) => $q->where('status', $status))
                ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->chunk(300, function ($users) use ($fh) {
                    foreach ($users as $user) {
                        $candidateCount = $user->role === 'manager'
                            ? (int) (($user->direct_managed_candidates_count ?? 0) + ($user->team_candidates_count ?? 0))
                            : (int) ($user->candidates_count ?? 0);

                        fputcsv($fh, [
                            $user->name,
                            $user->email,
                            $user->role,
                            $user->status,
                            $user->teamManager?->name ?? '',
                            $candidateCount,
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
        // Managers can only import recruiter-role users.
        // Admin account is unique and cannot be created via import.
        $allowedRoles = $isAdmin ? ['recruiter', 'manager'] : ['recruiter'];

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
                    ? "Row {$rowNum}: role must be manager or recruiter."
                    : "Row {$rowNum}: role must be recruiter (managers cannot import manager users).";
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
                'password_plain'  => $pass,
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

    // ──────────────────────────────────────────────────────────────────
    //  APPLICATIONS (daily logs)
    // ──────────────────────────────────────────────────────────────────

    public function downloadApplicationTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="applications_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Candidate Full Name', 'Candidate Email', 'Recruiter Email', 'Application Date', 'Application Number', 'Remark']);
            fputcsv($fh, ['John Smith', 'john@example.com', 'recruiter@example.com', '2025-01-15', '5', 'Applied to Java developer roles']);
            fputcsv($fh, ['Jane Doe',   'jane@example.com', '', '2025-01-15', '3', 'Applied to Data Science roles']);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importApplications(Request $request)
    {
        session()->flash('import_subtab', 'applications');

        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload a CSV file (.csv or .txt).'])->with('import_subtab', 'applications');
        }

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.'])->with('import_subtab', 'applications');
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);

        // Pre-load all candidates (email + name maps)
        $candidates       = Candidate::get(['id', 'email_id', 'full_name', 'recruiter_id', 'team_manager_id']);
        $byEmail          = $candidates->keyBy(fn ($c) => strtolower((string) $c->email_id));
        $byName           = $candidates->keyBy(fn ($c) => strtolower(trim((string) $c->full_name)));
        $ownersByEmail    = $this->importOwnerEmailMap();

        $imported  = 0;
        $skipped   = 0;
        $errors    = [];
        $rowNum    = 1;
        $batchSeen = []; // tracks (candidate_id-date) pairs in this file to catch sheet duplicates

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn ($v) => trim($v) !== '')) === 0) continue;

            $rowValues   = array_map('trim', $row);
            $headerCount = count($header);
            $rowValues   = count($rowValues) < $headerCount
                ? array_pad($rowValues, $headerCount, '')
                : array_slice($rowValues, 0, $headerCount);

            $d = array_combine($header, $rowValues);
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid format. Skipped.";
                $skipped++;
                continue;
            }

            $email   = strtolower(trim($d['candidate_email']       ?? ''));
            $name    = trim($d['candidate_full_name']               ?? '');
            $ownerEmail = strtolower(trim($d['recruiter_email']     ?? ''));
            $dateRaw = trim($d['application_date']                  ?? '');
            $appNum  = trim($d['application_number']                ?? '');
            $remark  = trim($d['remark']                            ?? '') ?: null;

            if ($email === '' && $name === '') {
                $errors[] = "Row {$rowNum}: Candidate Email or Candidate Full Name is required.";
                $skipped++; continue;
            }
            if ($dateRaw === '') {
                $errors[] = "Row {$rowNum}: Application Date is required.";
                $skipped++; continue;
            }
            if ($appNum === '' || !is_numeric($appNum) || (int) $appNum < 0) {
                $errors[] = "Row {$rowNum}: Application Number must be a non-negative integer.";
                $skipped++; continue;
            }

            $logDate = $this->normalizeDate($dateRaw);
            if (!$logDate) {
                $errors[] = "Row {$rowNum}: Application Date '{$dateRaw}' is not a valid date.";
                $skipped++; continue;
            }

            // Candidate lookup: email first, then name
            $candidate = ($email !== '' ? ($byEmail[strtolower($email)] ?? null) : null)
                      ?? ($name  !== '' ? ($byName[strtolower($name)]   ?? null) : null);
            $candidateLabel = $email ?: $name;

            if (!$candidate) {
                $errors[] = "Row {$rowNum}: Candidate '{$candidateLabel}' not found.";
                $skipped++; continue;
            }

            [$ownerValid, $owner] = $this->resolveImportOwner($ownerEmail, $ownersByEmail);
            if (!$ownerValid) {
                $errors[] = "Row {$rowNum}: Recruiter Email '{$ownerEmail}' must match an existing recruiter or manager.";
                $skipped++; continue;
            }

            $recordRecruiterId = $owner ? $this->applyImportOwner($candidate, $owner) : auth()->id();
            $recordCreatorId = $this->importActorId($owner);

            // Same-day duplicate within this file
            $dupeKey = "{$candidate->id}-{$logDate}";
            if (isset($batchSeen[$dupeKey])) {
                $errors[] = "Row {$rowNum}: Duplicate — {$candidateLabel} on {$logDate} already in this sheet (row {$batchSeen[$dupeKey]}). Skipped.";
                $skipped++; continue;
            }

            // Same-day duplicate already in database
            if (DailyLog::where('candidate_id', $candidate->id)->where('log_date', $logDate)->exists()) {
                $errors[] = "Row {$rowNum}: Application log for '{$candidateLabel}' on {$logDate} already exists. Skipped.";
                $skipped++; continue;
            }

            $batchSeen[$dupeKey] = $rowNum;

            // Auto-count existing interviews on this date for interview_count
            $interviewCount = Interview::where('candidate_id', $candidate->id)
                ->where(function ($query) use ($logDate) {
                    $query->whereDate('application_date', $logDate)
                        ->orWhere(function ($fallbackQuery) use ($logDate) {
                            $fallbackQuery->whereNull('application_date')
                                ->whereDate('mail_date', $logDate);
                        });
                })
                ->count();

            DailyLog::create([
                'candidate_id'    => $candidate->id,
                'recruiter_id'    => $recordRecruiterId,
                'log_date'        => $logDate,
                'applications'    => (int) $appNum,
                'interview_count' => $interviewCount,
                'assistant_count' => 0,
                'remark'          => $remark,
                'created_by'      => $recordCreatorId,
            ]);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'applications', "Imported {$imported} application log(s) from CSV ({$skipped} skipped)", [], [
            'imported'   => $imported,
            'skipped'    => $skipped,
            'has_errors' => count($errors) > 0,
        ]);

        $message = "Successfully imported {$imported} application log(s).";
        if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";

        return back()->with('success', $message)->with('import_errors', $errors)->with('import_subtab', 'applications');
    }

    // ──────────────────────────────────────────────────────────────────
    //  INTERVIEWS
    // ──────────────────────────────────────────────────────────────────

    public function downloadInterviewTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="interviews_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'Candidate Full Name', 'Candidate Email', 'Recruiter Email',
                'Application Date', 'Application Time',
                'Role', 'Company Name', 'Company Domain',
                'Interview Type', 'Mail Date', 'Mail Time',
                'Scheduled Date', 'Scheduled Time', 'Timezone',
                'Status', 'Remark',
            ]);
            // Interview Type: phone_call | virtual | on_site
            // Status: valid | invalid | (leave blank)
            // Times should be entered in 24-hour HH:MM format, e.g. 14:30 displays as 02:30 PM.
            fputcsv($fh, ['John Smith', 'john@example.com', 'recruiter@example.com', '2025-01-09', '13:45', 'Java Developer',  'Acme Corp', 'acmecorp.com',   'virtual',    '2025-01-10', '09:00', '2025-01-20', '14:00', 'EST', 'valid',   'Positive feedback']);
            fputcsv($fh, ['Jane Doe',   'jane@example.com', '', '2025-01-11', '10:30', 'Data Scientist',  'TechCo',    'techco.com',     'phone_call', '2025-01-12', '10:30', '',           '',      '',    '',        'Initial phone screen']);
            fputcsv($fh, ['John Smith', 'john@example.com', 'manager@example.com', '2025-01-13', '16:15', 'Backend Engineer','GlobalTech','globaltech.com', 'on_site',    '2025-01-14', '08:00', '2025-01-25', '09:00', 'PST', 'invalid', 'No show']);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importInterviews(Request $request, DailyLogSummaryService $dailyLogSummaryService)
    {
        session()->flash('import_subtab', 'interviews');

        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload a CSV file (.csv or .txt).'])->with('import_subtab', 'interviews');
        }

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.'])->with('import_subtab', 'interviews');
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);

        $candidates = Candidate::get(['id', 'email_id', 'full_name', 'recruiter_id', 'team_manager_id']);
        $byEmail    = $candidates->keyBy(fn ($c) => strtolower((string) $c->email_id));
        $byName     = $candidates->keyBy(fn ($c) => strtolower(trim((string) $c->full_name)));
        $ownersByEmail = $this->importOwnerEmailMap();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn ($v) => trim($v) !== '')) === 0) continue;

            $rowValues   = array_map('trim', $row);
            $headerCount = count($header);
            $rowValues   = count($rowValues) < $headerCount
                ? array_pad($rowValues, $headerCount, '')
                : array_slice($rowValues, 0, $headerCount);

            $d = array_combine($header, $rowValues);
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid format. Skipped.";
                $skipped++; continue;
            }

            $email    = strtolower(trim($d['candidate_email']   ?? ''));
            $name     = trim($d['candidate_full_name']           ?? '');
            $ownerEmail = strtolower(trim($d['recruiter_email']  ?? ''));
            $appDate  = trim($d['application_date']              ?? '');
            $appTime  = trim($d['application_time']              ?? '');
            $role     = trim($d['role']                          ?? '');
            $company  = trim($d['company_name']                  ?? '');
            $domain   = trim($d['company_domain']                ?? '') ?: null;
            $typeRaw  = trim($d['interview_type']                ?? '');
            $mailDate = trim($d['mail_date']                     ?? '');
            $mailTime = trim($d['mail_time']                     ?? '');
            $schedDate= trim($d['scheduled_date']                ?? '');
            $schedTime= trim($d['scheduled_time']                ?? '');
            $timezone = trim($d['timezone']                      ?? '') ?: null;
            $statusRaw= strtolower(trim($d['status']             ?? ''));
            $remark   = trim($d['remark']                        ?? '') ?: null;

            if ($email === '' && $name === '') {
                $errors[] = "Row {$rowNum}: Candidate Email or Candidate Full Name is required."; $skipped++; continue;
            }
            if ($role === '') {
                $errors[] = "Row {$rowNum}: Role is required."; $skipped++; continue;
            }
            if ($company === '') {
                $errors[] = "Row {$rowNum}: Company Name is required."; $skipped++; continue;
            }

            $interviewType = $this->normalizeInterviewType($typeRaw);
            if (!$interviewType) {
                $errors[] = "Row {$rowNum}: Interview Type '{$typeRaw}' is invalid. Use: phone_call, virtual, on_site.";
                $skipped++; continue;
            }

            $appDateNorm = $appDate !== '' ? $this->normalizeDate($appDate) : null;
            if ($appDate !== '' && !$appDateNorm) {
                $errors[] = "Row {$rowNum}: Application Date '{$appDate}' is invalid.";
                $skipped++; continue;
            }

            $appTimeNorm = $this->normalizeTime($appTime);
            if ($appTime !== '' && !$appTimeNorm) {
                $errors[] = "Row {$rowNum}: Application Time '{$appTime}' is invalid. Use 24-hour HH:MM format, for example 14:30.";
                $skipped++; continue;
            }

            $mailDateNorm = $this->normalizeDate($mailDate);
            if (!$mailDateNorm) {
                $errors[] = "Row {$rowNum}: Mail Date '{$mailDate}' is invalid or missing.";
                $skipped++; continue;
            }

            $candidate = ($email !== '' ? ($byEmail[strtolower($email)] ?? null) : null)
                      ?? ($name  !== '' ? ($byName[strtolower($name)]   ?? null) : null);
            $candidateLabel = $email ?: $name;

            if (!$candidate) {
                $errors[] = "Row {$rowNum}: Candidate '{$candidateLabel}' not found.";
                $skipped++; continue;
            }

            [$ownerValid, $owner] = $this->resolveImportOwner($ownerEmail, $ownersByEmail);
            if (!$ownerValid) {
                $errors[] = "Row {$rowNum}: Recruiter Email '{$ownerEmail}' must match an existing recruiter or manager.";
                $skipped++; continue;
            }

            $recordRecruiterId = $owner ? $this->applyImportOwner($candidate, $owner) : auth()->id();
            $recordCreatorId = $this->importActorId($owner);

            $status = in_array($statusRaw, ['valid', 'invalid'], true) ? $statusRaw : null;

            $interview = Interview::create([
                'candidate_id'       => $candidate->id,
                'recruiter_id'       => $recordRecruiterId,
                'application_date'   => $appDateNorm,
                'application_time'   => $appTimeNorm,
                'role'               => $role,
                'company_name'       => $company,
                'company_domain'     => $domain,
                'mail_date'          => $mailDateNorm,
                'mail_time'          => $this->normalizeTime($mailTime),
                'interview_type'     => $interviewType,
                'interview_status'   => $status,
                'scheduled_date'     => $this->normalizeDate($schedDate),
                'scheduled_time'     => $this->normalizeTime($schedTime),
                'scheduled_timezone' => $timezone,
                'remark'             => $remark,
                'created_by'         => $recordCreatorId,
            ]);

            $dailyLogSummaryService->syncInterview($interview, null, $recordCreatorId);
            $this->restampImportDailyLog($candidate->id, $dailyLogSummaryService->interviewLogDate($interview), $recordRecruiterId, $recordCreatorId);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'interviews', "Imported {$imported} interview(s) from CSV ({$skipped} skipped)", [], [
            'imported'   => $imported,
            'skipped'    => $skipped,
            'has_errors' => count($errors) > 0,
        ]);

        $message = "Successfully imported {$imported} interview(s).";
        if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";

        return back()->with('success', $message)->with('import_errors', $errors)->with('import_subtab', 'interviews');
    }

    // ──────────────────────────────────────────────────────────────────
    //  ASSESSMENTS
    // ──────────────────────────────────────────────────────────────────

    public function downloadAssessmentTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="assessments_template.csv"',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, [
                'Candidate Full Name', 'Candidate Email', 'Recruiter Email',
                'Assessment Date', 'Assessment Time',
                'Role', 'Company Name', 'Domain', 'Company Website',
                'Type', 'Mail Date', 'Mail Time', 'Remark',
            ]);
            // Type: technical | screening | ai_interview | questions | virtual_video_interview
            fputcsv($fh, ['John Smith', 'john@example.com', 'recruiter@example.com', '2025-01-18', '11:00', 'Java Developer',  'Acme Corp', 'Java',         'www.acmecorp.com', 'technical',              '2025-01-10', '09:00', 'LeetCode test']);
            fputcsv($fh, ['Jane Doe',   'jane@example.com', '', '2025-01-19', '14:30', 'Data Scientist',  'TechCo',    'Data Science', 'www.techco.com',   'screening',              '2025-01-12', '10:30', 'HireVue screening']);
            fputcsv($fh, ['John Smith', 'john@example.com', 'manager@example.com', '2025-01-22', '10:00', 'Backend Engineer','GlobalTech','Java',         'www.globaltech.com','virtual_video_interview','2025-01-14', '08:00', 'Recorded video round']);
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importAssessments(Request $request, DailyLogSummaryService $dailyLogSummaryService)
    {
        session()->flash('import_subtab', 'assessments');

        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload a CSV file (.csv or .txt).'])->with('import_subtab', 'assessments');
        }

        $fh        = fopen($request->file('file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($fh);
        if (!$rawHeader) {
            return back()->withErrors(['file' => 'The CSV file appears to be empty or invalid.'])->with('import_subtab', 'assessments');
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rawHeader);

        $candidates = Candidate::get(['id', 'email_id', 'full_name', 'recruiter_id', 'team_manager_id']);
        $byEmail    = $candidates->keyBy(fn ($c) => strtolower((string) $c->email_id));
        $byName     = $candidates->keyBy(fn ($c) => strtolower(trim((string) $c->full_name)));
        $ownersByEmail = $this->importOwnerEmailMap();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $rowNum   = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            if (count(array_filter($row, fn ($v) => trim($v) !== '')) === 0) continue;

            $rowValues   = array_map('trim', $row);
            $headerCount = count($header);
            $rowValues   = count($rowValues) < $headerCount
                ? array_pad($rowValues, $headerCount, '')
                : array_slice($rowValues, 0, $headerCount);

            $d = array_combine($header, $rowValues);
            if ($d === false) {
                $errors[] = "Row {$rowNum}: invalid format. Skipped.";
                $skipped++; continue;
            }

            $email    = strtolower(trim($d['candidate_email']    ?? ''));
            $name     = trim($d['candidate_full_name']            ?? '');
            $ownerEmail = strtolower(trim($d['recruiter_email']   ?? ''));
            $assDate  = trim($d['assessment_date']                ?? '');
            $assTime  = trim($d['assessment_time']                ?? '');
            $role     = trim($d['role']                           ?? '');
            $company  = trim($d['company_name']                   ?? '');
            $domain   = trim($d['domain']                         ?? '') ?: null;
            $website  = trim($d['company_website']                ?? '') ?: null;
            $typeRaw  = trim($d['type']                           ?? '');
            $mailDate = trim($d['mail_date']                      ?? '');
            $mailTime = trim($d['mail_time']                      ?? '');
            $remark   = trim($d['remark']                         ?? '') ?: null;

            if ($email === '' && $name === '') {
                $errors[] = "Row {$rowNum}: Candidate Email or Candidate Full Name is required."; $skipped++; continue;
            }
            if ($role === '') {
                $errors[] = "Row {$rowNum}: Role is required."; $skipped++; continue;
            }
            if ($company === '') {
                $errors[] = "Row {$rowNum}: Company Name is required."; $skipped++; continue;
            }

            $assDateNorm = $this->normalizeDate($assDate);
            if (!$assDateNorm) {
                $errors[] = "Row {$rowNum}: Assessment Date '{$assDate}' is invalid or missing.";
                $skipped++; continue;
            }

            $assessmentType = $this->normalizeAssessmentType($typeRaw);
            if (!$assessmentType) {
                $errors[] = "Row {$rowNum}: Assessment Type '{$typeRaw}' is invalid. Use: technical, screening, ai_interview, questions, virtual_video_interview.";
                $skipped++; continue;
            }

            $candidate = ($email !== '' ? ($byEmail[strtolower($email)] ?? null) : null)
                      ?? ($name  !== '' ? ($byName[strtolower($name)]   ?? null) : null);
            $candidateLabel = $email ?: $name;

            if (!$candidate) {
                $errors[] = "Row {$rowNum}: Candidate '{$candidateLabel}' not found.";
                $skipped++; continue;
            }

            [$ownerValid, $owner] = $this->resolveImportOwner($ownerEmail, $ownersByEmail);
            if (!$ownerValid) {
                $errors[] = "Row {$rowNum}: Recruiter Email '{$ownerEmail}' must match an existing recruiter or manager.";
                $skipped++; continue;
            }

            $recordRecruiterId = $owner ? $this->applyImportOwner($candidate, $owner) : auth()->id();
            $recordCreatorId = $this->importActorId($owner);

            $assessment = Assessment::create([
                'candidate_id'        => $candidate->id,
                'recruiter_id'        => $recordRecruiterId,
                'assessment_date'     => $assDateNorm,
                'assessment_time'     => $this->normalizeTime($assTime),
                'role'                => $role,
                'company_name'        => $company,
                'domain'              => $domain,
                'company_website_url' => $website,
                'assessment_type'     => $assessmentType,
                'mail_date'           => $this->normalizeDate($mailDate),
                'mail_time'           => $this->normalizeTime($mailTime),
                'remark'              => $remark,
                'created_by'          => $recordCreatorId,
            ]);

            $dailyLogSummaryService->syncAssessment($assessment, null, $recordCreatorId);
            $this->restampImportDailyLog($candidate->id, $dailyLogSummaryService->assessmentLogDate($assessment), $recordRecruiterId, $recordCreatorId);

            $imported++;
        }

        fclose($fh);

        AuditLog::log('imported', 'assessments', "Imported {$imported} assessment(s) from CSV ({$skipped} skipped)", [], [
            'imported'   => $imported,
            'skipped'    => $skipped,
            'has_errors' => count($errors) > 0,
        ]);

        $message = "Successfully imported {$imported} assessment(s).";
        if ($skipped > 0) $message .= " {$skipped} row(s) were skipped.";

        return back()->with('success', $message)->with('import_errors', $errors)->with('import_subtab', 'assessments');
    }

    private function importOwnerEmailMap()
    {
        return User::query()
            ->whereIn('role', ['recruiter', 'manager'])
            ->get(['id', 'name', 'email', 'role', 'team_manager_id'])
            ->keyBy(fn ($user) => strtolower(trim((string) $user->email)));
    }

    private function resolveImportOwner(string $email, $ownersByEmail): array
    {
        if ($email === '') {
            return [true, null];
        }

        $owner = $ownersByEmail[$email] ?? null;

        return [$owner !== null, $owner];
    }

    private function applyImportOwner(Candidate $candidate, ?User $owner): ?int
    {
        if (!$owner) {
            return auth()->id();
        }

        $role = strtolower((string) $owner->role);

        if ($role === 'manager') {
            if ((int) ($candidate->team_manager_id ?? 0) !== (int) $owner->id || $candidate->recruiter_id !== null) {
                $candidate->team_manager_id = $owner->id;
                $candidate->recruiter_id = null;
                $candidate->save();
            }

            return $owner->id;
        }

        $managerId = $owner->team_manager_id ?: null;

        if ((int) ($candidate->recruiter_id ?? 0) !== (int) $owner->id || (int) ($candidate->team_manager_id ?? 0) !== (int) ($managerId ?? 0)) {
            $candidate->recruiter_id = $owner->id;
            $candidate->team_manager_id = $managerId;
            $candidate->save();
        }

        return $owner->id;
    }

    private function importActorId(?User $owner): ?int
    {
        return $owner?->id ?? auth()->id();
    }

    private function restampImportDailyLog(int $candidateId, ?string $date, ?int $ownerId, ?int $creatorId): void
    {
        if (!$date) {
            return;
        }

        DailyLog::where('candidate_id', $candidateId)
            ->where('log_date', $date)
            ->update([
                'recruiter_id' => $ownerId,
                'created_by' => $creatorId,
            ]);
    }

    private function normalizeTime(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeInterviewType(mixed $value): ?string
    {
        $raw = strtolower(trim(str_replace([' ', '-'], '_', (string) ($value ?? ''))));

        return match (true) {
            in_array($raw, ['phone_call', 'phone', 'phone_screen'], true)                        => 'phone_call',
            in_array($raw, ['virtual', 'virtual_interview', 'online', 'video'], true)            => 'virtual',
            in_array($raw, ['on_site', 'onsite', 'in_person', 'on_site_interview'], true)        => 'on_site',
            default                                                                               => null,
        };
    }

    private function normalizeAssessmentType(mixed $value): ?string
    {
        $raw = strtolower(trim(str_replace([' ', '-'], '_', (string) ($value ?? ''))));

        return match (true) {
            in_array($raw, ['technical', 'tech', 'technical_assessment'], true)                                          => 'technical',
            in_array($raw, ['screening', 'screen'], true)                                                                => 'screening',
            in_array($raw, ['ai_interview', 'ai', 'ai_screen'], true)                                                   => 'ai_interview',
            in_array($raw, ['questions', 'question', 'q&a', 'qa'], true)                                                => 'questions',
            in_array($raw, ['virtual_video_interview', 'virtual_video', 'video_interview', 'hirevue'], true)            => 'virtual_video_interview',
            default                                                                                                      => null,
        };
    }

    private function normalizeCommaSeparated(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $parts = array_filter(array_map(
            fn ($item) => trim((string) $item),
            explode(',', $value)
        ));

        if (empty($parts)) {
            return null;
        }

        return implode(',', array_values(array_unique($parts)));
    }

    private function normalizeDate(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        $raw = strtolower(trim((string) ($value ?? '')));
        if ($raw === '') {
            return null;
        }

        if (in_array($raw, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($raw, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        return null;
    }

    private function portfolioColumnExists(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('candidates', 'portfolio_url');
        }

        return $hasColumn;
    }
}
