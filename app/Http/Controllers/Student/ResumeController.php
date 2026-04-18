<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    private function student(): Candidate
    {
        return Candidate::with('resumes')->findOrFail(session('student_id'));
    }

    public function store(Request $request)
    {
        $candidate = $this->student();

        $request->validate([
            'resumes'              => ['required', 'array', 'min:1'],
            'resumes.*.designation'=> ['required', 'string', 'max:200'],
            'resumes.*.file'       => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $count = 0;
        foreach ($request->resumes as $entry) {
            $file      = $entry['file'];
            $origName  = $file->getClientOriginalName();
            $path      = $file->store("candidates/{$candidate->id}/resumes", 'local');

            CandidateResume::create([
                'candidate_id'    => $candidate->id,
                'designation'     => trim($entry['designation']),
                'file_path'       => $path,
                'original_filename' => $origName,
            ]);
            $count++;
        }

        AuditLog::log(
            'uploaded',
            'student_resumes',
            "Student uploaded {$count} resume(s): {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'count' => $count]
        );

        return back()->with('success', "{$count} resume(s) uploaded successfully.");
    }

    public function download(CandidateResume $resume)
    {
        $candidate = $this->student();

        if ($resume->candidate_id !== $candidate->id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($resume->file_path)) {
            return back()->withErrors(['file' => 'File not found.']);
        }

        $mimeType = Storage::disk('local')->mimeType($resume->file_path) ?: 'application/octet-stream';

        return response(Storage::disk('local')->get($resume->file_path), 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $resume->original_filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
