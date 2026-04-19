<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function store(Request $request, Candidate $candidate)
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

        $validated = $request->validate([
            'applications'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'assistant_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'remark'          => ['nullable', 'string', 'max:2000'],
        ]);

        // One per day check
        $today = now()->toDateString();
        if (DailyLog::where('candidate_id', $candidate->id)->where('log_date', $today)->exists()) {
            return back()->withErrors(['log_date' => 'A daily log already exists for today.']);
        }

        // Auto-calculate interview count from interviews created today for this candidate
        $interviewCount = Interview::where('candidate_id', $candidate->id)
            ->whereDate('created_at', today())
            ->count();

        DailyLog::create([
            'candidate_id'    => $candidate->id,
            'recruiter_id'    => $candidate->recruiter_id, // always the candidate's assigned recruiter
            'log_date'        => $today,
            'applications'    => (int) ($validated['applications'] ?? 0),
            'assistant_count' => (int) ($validated['assistant_count'] ?? 0),
            'interview_count' => $interviewCount,
            'remark'          => $validated['remark'] ?? null,
            'created_by'      => $user->id,
        ]);

        AuditLog::log(
            'created',
            'daily_logs',
            "Added daily log for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'log_date' => $today]
        );

        return back()->with('success', 'Daily log added.');
    }

    public function update(Request $request, Candidate $candidate, DailyLog $log)
    {
        $user      = $request->user();
        $isAdmin   = $user->isAdmin();
        $isManager = $user->isManager();

        // Access control
        if ($user->isRecruiter() && $candidate->recruiter_id !== $user->id) {
            return response()->json(['message' => 'Not authorized'], 403);
        }
        if ($isManager
            && $candidate->team_manager_id !== $user->id
            && !in_array($candidate->recruiter_id, $user->teamMemberIds(), true)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $fields = [];

        // Remark: all can edit
        if ($request->has('remark')) {
            $fields['remark'] = $request->input('remark');
        }

        // applications/assistant/interview: only admin
        if ($isAdmin) {
            if ($request->has('applications'))    $fields['applications']    = (int) $request->input('applications');
            if ($request->has('assistant_count')) $fields['assistant_count'] = (int) $request->input('assistant_count');
            if ($request->has('interview_count')) $fields['interview_count'] = (int) $request->input('interview_count');
        }

        $log->update($fields);

        AuditLog::log(
            'updated',
            'daily_logs',
            "Updated daily log for {$candidate->full_name}",
            [],
            $fields
        );

        return response()->json(['success' => true]);
    }
}
