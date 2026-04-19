<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CandidatesController extends Controller
{
    private const STATUSES = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive'];

    private const VISA_STATUSES = [
        'us_citizen', 'green_card', 'h1b', 'h4_ead', 'opt_f1',
        'stem_opt', 'cpt', 'l1', 'tn_visa', 'other',
    ];

    private const WORK_AUTH_STATUSES = ['applied_pending', 'not_applied', 'already_obtained'];

    public function index(Request $request)
    {
        $user      = $request->user();
        $search    = trim((string) $request->get('search'));
        $status    = $request->get('status');
        $scope     = $request->get('scope'); // 'mine' | 'team' | null (all)
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        $teamMemberIds = $isManager ? $user->teamMemberIds() : [];

        // Manager-specific counts for the three scope cards
        $managerMyCount   = 0;
        $managerTeamCount = 0;
        $managerAllCount  = 0;
        if ($isManager) {
            $managerMyCount   = Candidate::where('team_manager_id', $user->id)->count();
            $managerTeamCount = Candidate::whereIn('recruiter_id', $teamMemberIds)->count();
            // All = distinct union (avoids double-counting candidates with both fields set)
            $managerAllCount  = Candidate::where(function ($q) use ($user, $teamMemberIds) {
                $q->where('team_manager_id', $user->id)
                  ->orWhereIn('recruiter_id', $teamMemberIds);
            })->count();
        }

        $candidates = Candidate::with(['recruiter', 'teamManager', 'resumes'])
            ->when($user->isRecruiter(), fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($isManager, function ($q) use ($user, $teamMemberIds, $scope) {
                if ($scope === 'mine') {
                    $q->where('team_manager_id', $user->id);
                } elseif ($scope === 'team') {
                    $q->whereIn('recruiter_id', $teamMemberIds);
                } else {
                    $q->where(function ($q2) use ($user, $teamMemberIds) {
                        $q2->where('team_manager_id', $user->id)
                           ->orWhereIn('recruiter_id', $teamMemberIds);
                    });
                }
            })
            ->when($search, fn ($q) => $q->search($search))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Admin → all recruiters & managers
        // Manager → only their team's recruiters (managers list not needed, auto-assigned to self)
        // Recruiter → empty (no assign section shown)
        $recruiters = $isAdmin
            ? User::recruiters()->active()->orderBy('name')->get(['id', 'name'])
            : ($isManager
                ? User::where('role', 'recruiter')->where('status', 'active')
                    ->whereIn('id', $user->teamMemberIds())
                    ->orderBy('name')->get(['id', 'name'])
                : collect());

        $managers = $isAdmin
            ? User::managers()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.candidates.index', [
            'candidates'       => $candidates,
            'recruiters'       => $recruiters,
            'managers'         => $managers,
            'search'           => $search,
            'status'           => $status,
            'scope'            => $scope,
            'statusOptions'    => self::STATUSES,
            'isAdmin'          => $isAdmin || $isManager,
            'isRealAdmin'      => $isAdmin,
            'isManager'        => $isManager,
            'currentUser'      => $user,
            'managerMyCount'   => $managerMyCount,
            'managerTeamCount' => $managerTeamCount,
            'managerAllCount'  => $managerAllCount,
        ]);
    }

    public function store(Request $request)
    {
        $user      = $request->user();
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        $data = $request->validate($this->validationRules($isAdmin, $isManager, null));

        $data['email_id'] = strtolower($data['email_id']);
        $data['full_name'] = $this->buildFullName($data);

        $plainPassword = $data['login_password'];
        $data['login_password']       = Hash::make($plainPassword);
        $data['login_password_plain'] = $plainPassword;
        $data['created_by'] = $user->id;
        $data['interviews_count'] = 0;

        // Assign recruiter / manager
        if ($isAdmin) {
            $data['recruiter_id']    = !empty($data['recruiter_id'])    ? (int) $data['recruiter_id']    : null;
            $data['team_manager_id'] = !empty($data['team_manager_id']) ? (int) $data['team_manager_id'] : null;
        } elseif ($isManager) {
            $data['team_manager_id'] = $user->id;
            $recruiter = !empty($data['recruiter_id']) ? (int) $data['recruiter_id'] : 0;
            if ($recruiter && in_array($recruiter, $user->teamMemberIds(), true)) {
                $data['recruiter_id'] = $recruiter;
            } else {
                $data['recruiter_id'] = null;
            }
        } else {
            $data['recruiter_id']    = $user->id;
            $data['team_manager_id'] = null;
        }

        // Sensitive nullable fields — store null when blank
        foreach (['ssn', 'marketing_email_password', 'marketing_linkedin_password'] as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }

        $candidate = Candidate::create($data);

        // Handle file uploads
        $this->handleFileUploads($request, $candidate);
        $this->handleResumeUploads($request, $candidate);

        AuditLog::log(
            'created',
            'candidates',
            "Created candidate: {$candidate->full_name}",
            [],
            $this->sanitizeAuditValues($data)
        );

        return redirect()->route('admin.candidates')
            ->with('success', "Candidate \"{$candidate->full_name}\" added successfully.");
    }

    public function update(Request $request, Candidate $candidate)
    {
        $user      = $request->user();
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        // Access control
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'You are not authorized to edit this candidate.');
        }
        if ($isManager
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)) {
            abort(403, 'You are not authorized to edit this candidate.');
        }

        $data = $request->validate($this->validationRules($isAdmin, $isManager, $candidate->id));

        $old = $candidate->toArray();

        $data['email_id']  = strtolower($data['email_id']);
        $data['full_name'] = $this->buildFullName($data);

        // no_of_applications: only admin can change; others keep existing value
        if (!$isAdmin) {
            $data['no_of_applications'] = $candidate->no_of_applications;
        }

        // Recruiter / manager assignment
        if ($isAdmin) {
            $data['recruiter_id']    = !empty($data['recruiter_id'])    ? (int) $data['recruiter_id']    : null;
            $data['team_manager_id'] = !empty($data['team_manager_id']) ? (int) $data['team_manager_id'] : null;
        } elseif ($isManager) {
            $data['team_manager_id'] = $user->id; // always the manager themselves
            $recruiter = !empty($data['recruiter_id']) ? (int) $data['recruiter_id'] : 0;
            if ($recruiter && in_array($recruiter, $user->teamMemberIds(), true)) {
                $data['recruiter_id'] = $recruiter;
            } else {
                $data['recruiter_id'] = $candidate->recruiter_id;
            }
        } else {
            $data['recruiter_id']    = $candidate->recruiter_id;
            $data['team_manager_id'] = $candidate->team_manager_id;
        }

        // Password: only update if provided
        if (!empty($data['login_password'])) {
            $plain = $data['login_password'];
            $data['login_password']       = Hash::make($plain);
            $data['login_password_plain'] = $plain;
        } else {
            unset($data['login_password'], $data['login_password_plain']);
        }

        // Sensitive nullable fields: blank = keep existing
        foreach (['ssn', 'marketing_email_password', 'marketing_linkedin_password'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                unset($data[$field]);
            }
        }

        // File uploads
        $this->handleFileUploads($request, $candidate, $data);
        $this->handleResumeUploads($request, $candidate);

        $candidate->update($data);

        AuditLog::log(
            'updated',
            'candidates',
            "Updated candidate: {$candidate->full_name}",
            $this->sanitizeAuditValues($old),
            $this->sanitizeAuditValues($data)
        );

        return redirect()->route('admin.candidates')
            ->with('success', "Candidate \"{$candidate->full_name}\" updated successfully.");
    }

    public function destroy(Request $request, Candidate $candidate)
    {
        $authUser  = $request->user();
        $isManager = $authUser->isManager();

        if ($authUser->isRecruiter() && $candidate->recruiter_id !== $authUser->id) {
            abort(403, 'You are not authorized to delete this candidate.');
        }
        if ($isManager
            && $candidate->team_manager_id !== $authUser->id
            && !in_array($candidate->recruiter_id, $authUser->teamMemberIds(), true)) {
            abort(403, 'You are not authorized to delete this candidate.');
        }

        foreach (['cv_file_path', 'speedy_apply_json_path'] as $field) {
            if ($candidate->$field) {
                Storage::disk('local')->delete($candidate->$field);
            }
        }

        // Delete all resumes
        foreach ($candidate->resumes as $resume) {
            Storage::disk('local')->delete($resume->file_path);
        }
        $candidate->resumes()->delete();

        $name  = $candidate->full_name;
        $email = $candidate->email_id;

        $candidate->update(['status' => 'inactive']);
        $candidate->delete();

        AuditLog::log(
            'deleted',
            'candidates',
            "Deleted candidate: {$name}",
            ['candidate_id' => $candidate->id, 'email_id' => $email],
            ['deleted_by' => $request->user()?->email]
        );

        return back()->with('success', "Candidate \"{$name}\" deleted. Student login is disabled.");
    }

    public function downloadFile(Request $request, Candidate $candidate, string $file)
    {
        $user = $request->user();
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'You are not authorized to view this file.');
        }

        $path = match ($file) {
            'cv'      => $candidate->cv_file_path,
            'details' => $candidate->candidate_details_file_path,
            'speedy'  => $candidate->speedy_apply_json_path,
            default   => null,
        };

        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => 'Requested file was not found.']);
        }

        AuditLog::log(
            'downloaded',
            'candidates',
            "Viewed {$file} file for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'file_type' => $file]
        );

        $mimeType = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function updateStatus(Request $request, Candidate $candidate)
    {
        $user = $request->user();
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        $old = ['status' => $candidate->status];
        $candidate->update(['status' => $data['status']]);

        AuditLog::log(
            'updated',
            'candidates',
            "Status changed: {$candidate->full_name} → {$data['status']}",
            $old,
            ['status' => $data['status']]
        );

        return response()->json(['status' => $data['status']]);
    }

    public function revealPassword(Request $request, Candidate $candidate)
    {
        $user = $request->user();
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            return response()->json(['message' => 'You are not authorized to view this password.'], 403);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current account password is incorrect.'], 422);
        }

        try {
            $password = $candidate->login_password_plain;
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Stored password could not be decrypted. Please set a new portal password first.',
            ], 422);
        }

        if (!$password) {
            return response()->json([
                'message' => 'No recoverable portal password stored for this candidate. Set a new one first.',
            ], 422);
        }

        AuditLog::log(
            'revealed',
            'candidates',
            "Revealed student portal password for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id]
        );

        return response()->json(['password' => $password]);
    }

    public function show(Request $request, Candidate $candidate)
    {
        $user      = $request->user();
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        // Access control
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'Not authorized.');
        }
        if ($isManager
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)) {
            abort(403, 'Not authorized.');
        }

        $candidate->load(['recruiter', 'teamManager', 'creator', 'resumes']);

        $dailyLogs = DailyLog::where('candidate_id', $candidate->id)
            ->with(['recruiter', 'creator'])
            ->orderByDesc('log_date')->get();

        $interviews = Interview::where('candidate_id', $candidate->id)
            ->with(['recruiter', 'creator'])
            ->orderByDesc('scheduled_date')->orderByDesc('created_at')->get();

        return view('admin.candidates.show', [
            'candidate'   => $candidate,
            'dailyLogs'   => $dailyLogs,
            'interviews'  => $interviews,
            'isAdmin'     => $isAdmin,
            'isRealAdmin' => $isAdmin,
            'isManager'   => $isManager,
            'currentUser' => $user,
        ]);
    }

    public function patchField(Request $request, Candidate $candidate)
    {
        $user    = $request->user();
        $isAdmin = $user->isAdmin();

        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $field = $request->input('field');
        $value = $request->input('value');

        // Admin-only fields
        $adminOnlyFields = ['no_of_applications', 'recruiter_id', 'team_manager_id'];
        if (in_array($field, $adminOnlyFields) && !$isAdmin) {
            return response()->json(['message' => 'Only admin can update this field'], 403);
        }

        // Allowed fields whitelist
        $allowed = [
            'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'nationality',
            'phone_number', 'domain', 'sub_domain', 'current_salary', 'expected_salary',
            'street_address', 'apartment_unit', 'city', 'state_province', 'zip_code', 'country',
            'visa_immigration_status', 'work_auth_status', 'open_to_relocation', 'preferred_city', 'visa_expiry_date',
            'marketing_phone', 'marketing_email', 'marketing_linkedin_id',
            'masters_university', 'masters_program', 'masters_start', 'masters_end', 'masters_country',
            'bachelors_university', 'bachelors_program', 'bachelors_start', 'bachelors_end', 'bachelors_country',
            'github_url', 'linkedin_url', 'recruiter_notes', 'no_of_applications', 'status',
        ];

        if (!in_array($field, $allowed)) {
            return response()->json(['message' => 'Field not editable'], 422);
        }

        $candidate->$field = $value ?: null;
        if ($field === 'first_name' || $field === 'last_name') {
            $candidate->full_name = trim(($candidate->first_name ?? '') . ' ' . ($candidate->middle_name ?? '') . ' ' . ($candidate->last_name ?? ''));
        }
        $candidate->save();

        AuditLog::log(
            'updated',
            'candidates',
            "Updated {$field} for {$candidate->full_name}",
            [],
            ['field' => $field, 'value' => $value]
        );

        return response()->json(['success' => true, 'full_name' => $candidate->full_name]);
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function buildFullName(array $data): string
    {
        $parts = array_filter([
            $data['first_name']  ?? '',
            $data['middle_name'] ?? '',
            $data['last_name']   ?? '',
        ]);

        return trim(implode(' ', $parts)) ?: ($data['full_name'] ?? '');
    }

    private function validationRules(bool $isAdmin, bool $isManager, ?int $ignoreId): array
    {
        return [
            // Personal Info
            'first_name'           => ['required', 'string', 'max:100'],
            'middle_name'          => ['nullable', 'string', 'max:100'],
            'last_name'            => ['required', 'string', 'max:100'],
            'date_of_birth'        => ['nullable', 'date'],
            'gender'               => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'nationality'          => ['nullable', 'string', 'max:100'],
            'email_id'             => ['required', 'email', 'max:255',
                $ignoreId
                    ? Rule::unique('candidates', 'email_id')->ignore($ignoreId)->withoutTrashed()
                    : Rule::unique('candidates', 'email_id')->withoutTrashed(),
            ],
            'phone_number'         => ['nullable', 'string', 'max:30'],
            'domain'               => ['nullable', 'string', 'max:150'],
            'sub_domain'           => ['nullable', 'string', 'max:500'],
            'ssn'                  => ['nullable', 'string', 'max:20'],
            'date_of_arrival_usa'  => ['nullable', 'date'],
            'current_salary'       => ['nullable', 'numeric', 'min:0'],
            'expected_salary'      => ['nullable', 'numeric', 'min:0'],

            // Address
            'street_address'  => ['nullable', 'string', 'max:255'],
            'apartment_unit'  => ['nullable', 'string', 'max:50'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state_province'  => ['nullable', 'string', 'max:5'],
            'zip_code'        => ['nullable', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:100'],

            // Visa
            'visa_immigration_status' => ['nullable', Rule::in(array_merge(self::VISA_STATUSES, ['']))],
            'work_auth_status'        => ['nullable', Rule::in(array_merge(self::WORK_AUTH_STATUSES, ['']))],
            'open_to_relocation'      => ['nullable', 'boolean'],
            'preferred_city'          => ['nullable', 'string', 'max:100'],
            'visa_expiry_date'        => ['nullable', 'date'],

            // Marketing
            'marketing_phone'             => ['nullable', 'string', 'max:30'],
            'marketing_email'             => ['nullable', 'email', 'max:255'],
            'marketing_email_password'    => ['nullable', 'string', 'max:255'],
            'marketing_linkedin_id'       => ['nullable', 'string', 'max:255'],
            'marketing_linkedin_password' => ['nullable', 'string', 'max:255'],
            'github_url'                  => ['nullable', 'url', 'max:255'],
            'linkedin_url'                => ['nullable', 'url', 'max:255'],
            'speedy_apply_json'           => ['nullable', 'file', 'mimes:json,txt', 'max:2048'],

            // Education
            'masters_university'   => ['nullable', 'string', 'max:255'],
            'masters_program'      => ['nullable', 'string', 'max:200'],
            'masters_start'        => ['nullable', 'date'],
            'masters_end'          => ['nullable', 'date'],
            'masters_country'      => ['nullable', 'string', 'max:100'],
            'bachelors_university' => ['nullable', 'string', 'max:255'],
            'bachelors_program'    => ['nullable', 'string', 'max:200'],
            'bachelors_start'      => ['nullable', 'date'],
            'bachelors_end'        => ['nullable', 'date'],
            'bachelors_country'    => ['nullable', 'string', 'max:100'],

            // Professional / Notes
            'recruiter_notes' => ['nullable', 'string'],

            // Portal access
            'no_of_applications' => ['required', 'integer', 'min:0'],
            'status'             => ['required', Rule::in(self::STATUSES)],
            'recruiter_id'       => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['recruiter']))],
            'team_manager_id'    => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'manager'))],
            'login_password'     => $ignoreId
                ? ['nullable', 'string', 'min:8', 'max:255']
                : ['required', 'string', 'min:8', 'max:255'],

            // Files
            'cv_file'               => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'resumes'               => ['nullable', 'array'],
            'resumes.*.designation' => ['nullable', 'string', 'max:255'],
            'resumes.*.file'        => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    private function handleFileUploads(Request $request, Candidate $candidate, array &$data = []): void
    {
        if ($request->hasFile('cv_file')) {
            if ($candidate->cv_file_path) {
                Storage::disk('local')->delete($candidate->cv_file_path);
            }
            $path = $request->file('cv_file')->store("candidates/{$candidate->id}/cv", 'local');
            $candidate->update(['cv_file_path' => $path]);
        }

        if ($request->hasFile('speedy_apply_json')) {
            if ($candidate->speedy_apply_json_path) {
                Storage::disk('local')->delete($candidate->speedy_apply_json_path);
            }
            $path = $request->file('speedy_apply_json')->store("candidates/{$candidate->id}/speedy", 'local');
            $candidate->update(['speedy_apply_json_path' => $path]);
        }

        unset($data['cv_file'], $data['speedy_apply_json']);
    }

    private function handleResumeUploads(Request $request, Candidate $candidate): void
    {
        $resumeInputs = $request->input('resumes', []);
        $resumeFiles  = $request->file('resumes', []);

        foreach ($resumeInputs as $idx => $entry) {
            $designation = trim($entry['designation'] ?? '');
            $file = $resumeFiles[$idx]['file'] ?? null;
            if (!$designation || !$file) {
                continue;
            }
            $path = $file->store("candidates/{$candidate->id}/resumes", 'local');
            $candidate->resumes()->create([
                'designation'       => $designation,
                'file_path'         => $path,
                'original_filename' => $file->getClientOriginalName(),
            ]);
        }
    }

    private function sanitizeAuditValues(array $values): array
    {
        unset(
            $values['login_password'],
            $values['login_password_plain'],
            $values['ssn'],
            $values['marketing_email_password'],
            $values['marketing_linkedin_password'],
            $values['remember_token']
        );

        return $values;
    }
}
