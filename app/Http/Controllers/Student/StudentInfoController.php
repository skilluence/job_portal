<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentInfoController extends Controller
{
    private function student(): Candidate
    {
        return Candidate::with('recruiter')->findOrFail(session('student_id'));
    }

    public function index()
    {
        return view('student.info.index', ['candidate' => $this->student()]);
    }

    public function update(Request $request)
    {
        $candidate = $this->student();
        $old = $candidate->only(['full_name', 'email_id', 'linkedin_id', 'address', 'profile', 'notes']);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email_id' => ['required', 'email', 'max:255', Rule::unique('candidates', 'email_id')->ignore($candidate->id)],
            'linkedin_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'profile' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['email_id'] = strtolower($data['email_id']);
        $candidate->update($data);

        AuditLog::log('updated', 'student_profile', "Student profile updated: {$candidate->full_name}", $old, $data);

        return back()->with('success', 'Your details have been updated successfully.');
    }

    public function updateDocuments(Request $request)
    {
        $candidate = $this->student();

        $request->validate([
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'candidate_details_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if (!$request->hasFile('cv_file') && !$request->hasFile('candidate_details_file')) {
            return back()->withErrors(['documents' => 'Please choose at least one document to upload.']);
        }

        $changes = [];
        $oldValues = [];

        if ($request->hasFile('cv_file')) {
            $oldValues['cv_file_path'] = $candidate->cv_file_path;

            if ($candidate->cv_file_path) {
                Storage::disk('local')->delete($candidate->cv_file_path);
            }

            $changes['cv_file_path'] = $request->file('cv_file')->store("candidates/{$candidate->id}/cv", 'local');
        }

        if ($request->hasFile('candidate_details_file')) {
            $oldValues['candidate_details_file_path'] = $candidate->candidate_details_file_path;

            if ($candidate->candidate_details_file_path) {
                Storage::disk('local')->delete($candidate->candidate_details_file_path);
            }

            $changes['candidate_details_file_path'] = $request->file('candidate_details_file')->store("candidates/{$candidate->id}/details", 'local');
        }

        $candidate->update($changes);

        AuditLog::log(
            'uploaded',
            'student_documents',
            "Student uploaded documents: {$candidate->full_name}",
            $oldValues,
            $changes
        );

        return back()->with('success', 'Document(s) uploaded successfully.');
    }

    public function updatePassword(Request $request)
    {
        $candidate = $this->student();

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $candidate->login_password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $candidate->update([
            'login_password' => Hash::make($request->password),
            'login_password_plain' => $request->password,
        ]);

        AuditLog::log('updated', 'student_auth', "Student changed portal password: {$candidate->full_name}");

        return back()->with('success', 'Password updated successfully.');
    }

    public function downloadFile(string $file)
    {
        $candidate = $this->student();
        $path = $file === 'cv' ? $candidate->cv_file_path : $candidate->candidate_details_file_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => 'Requested file was not found.']);
        }

        AuditLog::log(
            'downloaded',
            'student_documents',
            "Student downloaded {$file} file: {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'file_type' => $file]
        );

        return Storage::disk('local')->download($path);
    }
}
