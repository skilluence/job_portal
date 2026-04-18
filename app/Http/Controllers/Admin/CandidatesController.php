<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
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
        $user    = $request->user();
        $search  = trim((string) $request->get('search'));
        $status  = $request->get('status');
        $isAdmin = $user->isAdmin();

        $candidates = Candidate::with('recruiter')
            ->when($user->isRecruiter(), fn ($q) => $q->where('recruiter_id', $user->id))
            ->when($search, fn ($q) => $q->search($search))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $recruiters = $isAdmin
            ? User::recruiters()->active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.candidates.index', [
            'candidates'    => $candidates,
            'recruiters'    => $recruiters,
            'search'        => $search,
            'status'        => $status,
            'statusOptions' => self::STATUSES,
            'isAdmin'       => $isAdmin,
            'currentUser'   => $user,
        ]);
    }

    public function store(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->isAdmin();

        $data = $request->validate($this->validationRules($isAdmin, null));

        $data['email_id'] = strtolower($data['email_id']);
        $data['full_name'] = $this->buildFullName($data);

        $plainPassword = $data['login_password'];
        $data['login_password']       = Hash::make($plainPassword);
        $data['login_password_plain'] = $plainPassword;
        $data['created_by']    = $user->id;
        $data['recruiter_id']  = $isAdmin ? (int) $data['recruiter_id'] : $user->id;
        $data['interviews_count'] = 0;

        // Handle sensitive nullable fields — store null when blank
        foreach (['ssn', 'marketing_email_password', 'marketing_linkedin_password'] as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }

        $candidate = Candidate::create($data);

        // Handle file uploads
        $this->handleFileUploads($request, $candidate);

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
        $user    = $request->user();
        $isAdmin = $user->isAdmin();

        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'You are not authorized to edit this candidate.');
        }

        $data = $request->validate($this->validationRules($isAdmin, $candidate->id));

        $old = $candidate->toArray();

        $data['email_id']   = strtolower($data['email_id']);
        $data['full_name']  = $this->buildFullName($data);
        $data['recruiter_id'] = $isAdmin ? (int) $data['recruiter_id'] : $candidate->recruiter_id;

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
        if ($request->user()->isRecruiter() && $candidate->recruiter_id !== $request->user()->id) {
            abort(403, 'You are not authorized to delete this candidate.');
        }

        foreach (['cv_file_path', 'candidate_details_file_path', 'speedy_apply_json_path'] as $field) {
            if ($candidate->$field) {
                Storage::disk('local')->delete($candidate->$field);
            }
        }

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

    // ── Private helpers ──────────────────────────────────────────────

    private function buildFullName(array $data): string
    {
        $parts = array_filter([
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['last_name'] ?? '',
        ]);

        return trim(implode(' ', $parts)) ?: ($data['full_name'] ?? '');
    }

    private function validationRules(bool $isAdmin, ?int $ignoreId): array
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
            'sub_domain'           => ['nullable', 'string', 'max:150'],
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
            'marketing_phone'          => ['nullable', 'string', 'max:30'],
            'marketing_email'          => ['nullable', 'email', 'max:255'],
            'marketing_email_password' => ['nullable', 'string', 'max:255'],
            'marketing_linkedin_id'    => ['nullable', 'string', 'max:255'],
            'marketing_linkedin_password' => ['nullable', 'string', 'max:255'],

            // Education
            'masters_university'  => ['nullable', 'string', 'max:255'],
            'masters_program'     => ['nullable', 'string', 'max:200'],
            'masters_start'       => ['nullable', 'date'],
            'masters_end'         => ['nullable', 'date'],
            'masters_country'     => ['nullable', 'string', 'max:100'],
            'bachelors_university'=> ['nullable', 'string', 'max:255'],
            'bachelors_program'   => ['nullable', 'string', 'max:200'],
            'bachelors_start'     => ['nullable', 'date'],
            'bachelors_end'       => ['nullable', 'date'],
            'bachelors_country'   => ['nullable', 'string', 'max:100'],

            // Professional
            'github_url'       => ['nullable', 'url', 'max:255'],
            'linkedin_url'     => ['nullable', 'url', 'max:255'],
            'recruiter_notes'  => ['nullable', 'string'],

            // Portal access
            'no_of_applications' => ['required', 'integer', 'min:0'],
            'status'             => ['required', Rule::in(self::STATUSES)],
            'recruiter_id'       => $isAdmin
                ? ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'recruiter'))]
                : ['nullable'],
            'login_password'     => $ignoreId
                ? ['nullable', 'string', 'min:8', 'max:255']
                : ['required', 'string', 'min:8', 'max:255'],

            // Files
            'cv_file'                   => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'candidate_details_file'    => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'speedy_apply_json'         => ['nullable', 'file', 'mimes:json,txt', 'max:2048'],
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

        if ($request->hasFile('candidate_details_file')) {
            if ($candidate->candidate_details_file_path) {
                Storage::disk('local')->delete($candidate->candidate_details_file_path);
            }
            $path = $request->file('candidate_details_file')->store("candidates/{$candidate->id}/details", 'local');
            $candidate->update(['candidate_details_file_path' => $path]);
        }

        if ($request->hasFile('speedy_apply_json')) {
            if ($candidate->speedy_apply_json_path) {
                Storage::disk('local')->delete($candidate->speedy_apply_json_path);
            }
            $path = $request->file('speedy_apply_json')->store("candidates/{$candidate->id}/speedy", 'local');
            $candidate->update(['speedy_apply_json_path' => $path]);
        }

        // Remove file fields from $data so they don't cause mass-assign issues
        unset($data['cv_file'], $data['candidate_details_file'], $data['speedy_apply_json']);
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
