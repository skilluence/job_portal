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

    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
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
            'candidates' => $candidates,
            'recruiters' => $recruiters,
            'search' => $search,
            'status' => $status,
            'statusOptions' => self::STATUSES,
            'isAdmin' => $isAdmin,
            'currentUser' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'enrollment_date' => ['nullable', 'date'],
            'sales_agent' => ['nullable', 'string', 'max:255'],
            'no_of_applications' => ['required', 'integer', 'min:0'],
            'interviews_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'recruiter_id' => $isAdmin
                ? ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'recruiter'))]
                : ['nullable'],
            'linkedin_id' => ['nullable', 'string', 'max:255'],
            'linkedin_password' => ['nullable', 'string', 'max:255'],
            'email_id' => ['required', 'email', 'max:255', 'unique:candidates,email_id'],
            'email_password' => ['nullable', 'string', 'max:255'],
            'linkedin_updated' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'profile' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'login_password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $data['email_id'] = strtolower($data['email_id']);
        $plainLoginPassword = $data['login_password'];
        $data['login_password'] = Hash::make($plainLoginPassword);
        $data['login_password_plain'] = $plainLoginPassword;
        $data['interviews_count'] = $data['interviews_count'] ?? 0;
        $data['created_by'] = $user->id;
        $data['recruiter_id'] = $isAdmin ? (int) $data['recruiter_id'] : $user->id;

        $candidate = Candidate::create($data);

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
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'You are not authorized to edit this candidate.');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'enrollment_date' => ['nullable', 'date'],
            'sales_agent' => ['nullable', 'string', 'max:255'],
            'no_of_applications' => ['required', 'integer', 'min:0'],
            'interviews_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'recruiter_id' => $isAdmin
                ? ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'recruiter'))]
                : ['nullable'],
            'linkedin_id' => ['nullable', 'string', 'max:255'],
            'linkedin_password' => ['nullable', 'string', 'max:255'],
            'email_id' => ['required', 'email', 'max:255', Rule::unique('candidates', 'email_id')->ignore($candidate->id)],
            'email_password' => ['nullable', 'string', 'max:255'],
            'linkedin_updated' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'profile' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'login_password' => ['nullable', 'string', 'min:8', 'max:255'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'candidate_details_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        $old = $candidate->toArray();
        $data['email_id'] = strtolower($data['email_id']);
        $data['interviews_count'] = $data['interviews_count'] ?? 0;
        $data['recruiter_id'] = $isAdmin ? (int) $data['recruiter_id'] : $candidate->recruiter_id;

        if (!empty($data['login_password'])) {
            $plainLoginPassword = $data['login_password'];
            $data['login_password'] = Hash::make($plainLoginPassword);
            $data['login_password_plain'] = $plainLoginPassword;
        } else {
            unset($data['login_password']);
            unset($data['login_password_plain']);
        }

        if ($request->hasFile('cv_file')) {
            if ($candidate->cv_file_path) {
                Storage::disk('local')->delete($candidate->cv_file_path);
            }

            $data['cv_file_path'] = $request->file('cv_file')->store("candidates/{$candidate->id}/cv", 'local');
        }

        if ($request->hasFile('candidate_details_file')) {
            if ($candidate->candidate_details_file_path) {
                Storage::disk('local')->delete($candidate->candidate_details_file_path);
            }

            $data['candidate_details_file_path'] = $request->file('candidate_details_file')->store("candidates/{$candidate->id}/details", 'local');
        }

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

        if ($candidate->cv_file_path) {
            Storage::disk('local')->delete($candidate->cv_file_path);
        }

        if ($candidate->candidate_details_file_path) {
            Storage::disk('local')->delete($candidate->candidate_details_file_path);
        }

        $name = $candidate->full_name;
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

        return back()->with('success', "Candidate \"{$name}\" deleted successfully. Student login is disabled.");
    }

    public function downloadFile(Request $request, Candidate $candidate, string $file)
    {
        $user = $request->user();
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'You are not authorized to download this file.');
        }

        $path = $file === 'cv' ? $candidate->cv_file_path : $candidate->candidate_details_file_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => 'Requested file was not found.']);
        }

        AuditLog::log(
            'downloaded',
            'candidates',
            "Downloaded {$file} file for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'file_type' => $file]
        );

        return Storage::disk('local')->download($path);
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
        } catch (\Throwable $error) {
            return response()->json([
                'message' => 'Stored password could not be decrypted. Please set a new portal password first.',
            ], 422);
        }

        if (!$password) {
            return response()->json([
                'message' => 'No recoverable portal password is stored for this candidate. Set a new one first.',
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

    private function sanitizeAuditValues(array $values): array
    {
        unset(
            $values['password'],
            $values['login_password'],
            $values['login_password_plain'],
            $values['email_password'],
            $values['linkedin_password'],
            $values['remember_token']
        );

        return $values;
    }
}
