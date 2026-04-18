<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateResumeController extends Controller
{
    public function download(Request $request, Candidate $candidate, CandidateResume $resume)
    {
        $user = $request->user();

        // Access control
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            abort(403, 'Not authorized.');
        }
        if ($user->isManager()
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)) {
            abort(403, 'Not authorized.');
        }

        if ($resume->candidate_id !== $candidate->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($resume->file_path)) {
            return back()->withErrors(['file' => 'Resume file not found.']);
        }

        AuditLog::log(
            'downloaded',
            'candidates',
            "Viewed resume ({$resume->designation}) for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'resume_id' => $resume->id]
        );

        $mimeType = Storage::disk('local')->mimeType($resume->file_path) ?: 'application/octet-stream';

        return response(Storage::disk('local')->get($resume->file_path), 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $resume->original_filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
