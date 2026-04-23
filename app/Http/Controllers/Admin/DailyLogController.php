<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\DailyLog;
use App\Services\DailyLogSummaryService;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function store(Request $request, Candidate $candidate, DailyLogSummaryService $dailyLogSummaryService)
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
            'applications' => ['required', 'integer', 'min:0', 'max:9999'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $today = now()->toDateString();
        $log = DailyLog::query()->firstOrNew([
            'candidate_id' => $candidate->id,
            'log_date' => $today,
        ]);

        $isNewLog = !$log->exists;
        $log->recruiter_id = $candidate->recruiter_id;
        $log->applications = (int) $validated['applications'];
        $log->remark = $validated['remark'] ?? null;
        if (!$log->created_by) {
            $log->created_by = $user->id;
        }
        $log->save();

        $log = $dailyLogSummaryService->syncForCandidateDate($candidate, $today, $user->id) ?? $log;

        AuditLog::log(
            $isNewLog ? 'created' : 'updated',
            'daily_logs',
            ($isNewLog ? 'Saved' : 'Updated') . " daily log for {$candidate->full_name}",
            [],
            [
                'candidate_id' => $candidate->id,
                'log_date' => $today,
                'applications' => $log->applications,
                'assistant_count' => $log->assistant_count,
                'interview_count' => $log->interview_count,
            ]
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'logs'])
            ->with('success', $isNewLog ? 'Daily log added.' : 'Daily log updated.');
    }

    public function update(Request $request, Candidate $candidate, DailyLog $log, DailyLogSummaryService $dailyLogSummaryService)
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

        // applications: only admin
        if ($isAdmin) {
            if ($request->has('applications')) {
                $fields['applications'] = (int) $request->input('applications');
            }
        }

        $log->update($fields);
        $dailyLogSummaryService->syncForCandidateDate($candidate, $log->log_date->toDateString(), $user->id);
        $log->refresh();

        AuditLog::log(
            'updated',
            'daily_logs',
            "Updated daily log for {$candidate->full_name}",
            [],
            [
                'applications' => $log->applications,
                'assistant_count' => $log->assistant_count,
                'interview_count' => $log->interview_count,
                'remark' => $log->remark,
            ]
        );

        return response()->json([
            'success' => true,
            'log' => [
                'applications' => $log->applications,
                'assistant_count' => $log->assistant_count,
                'interview_count' => $log->interview_count,
                'remark' => $log->remark,
            ],
        ]);
    }
}
