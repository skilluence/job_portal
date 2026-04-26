<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Interview;
use App\Services\DailyLogSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    private const TYPES = ['phone_call', 'virtual', 'on_site'];
    private const TIMEZONES = ['EST', 'CST', 'MST', 'PST', 'AKST', 'HST', 'EDT', 'CDT', 'MDT', 'PDT'];

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

        $data = $request->validate([
            'application_date'   => ['required', 'date'],
            'application_time'   => ['required', 'date_format:H:i'],
            'role'               => ['required', 'string', 'max:200'],
            'company_name'       => ['required', 'string', 'max:200'],
            'company_domain'     => ['required', 'string', 'max:500'],
            'mail_date'          => ['nullable', 'date'],
            'mail_time'          => ['nullable', 'date_format:H:i'],
            'interview_type'     => ['required', Rule::in(self::TYPES)],
            'remark'             => ['nullable', 'string'],
            'scheduled_date'     => ['nullable', 'date'],
            'scheduled_time'     => ['nullable', 'date_format:H:i'],
            'scheduled_timezone' => ['nullable', Rule::in(self::TIMEZONES)],
            'interview_status'   => ['nullable', Rule::in(['valid', 'invalid', ''])],
        ]);

        // Normalise company_domain — prepend https:// if no scheme given
        if (!empty($data['company_domain']) && !preg_match('#^https?://#i', $data['company_domain'])) {
            $data['company_domain'] = 'https://' . $data['company_domain'];
        }

        $data = $this->normalizeInterviewDateTimes($data);

        $data['candidate_id']     = $candidate->id;
        $data['recruiter_id']     = $candidate->recruiter_id; // always the candidate's assigned recruiter
        $data['created_by']       = $user->id;
        $data['interview_status'] = $isAdmin ? ($data['interview_status'] ?? null) : null;
        if ($data['interview_status'] === '') {
            $data['interview_status'] = null;
        }

        // Non-admin (recruiter/manager) cannot set scheduled fields — only admin/candidate may
        if (!$isAdmin) {
            $data['scheduled_date']     = null;
            $data['scheduled_time']     = null;
            $data['scheduled_timezone'] = null;
        }

        $interview = Interview::create($data);

        $dailyLogSummaryService->syncInterview($interview, null, $user->id);

        AuditLog::log(
            'created',
            'interviews',
            "Added interview for {$candidate->full_name}",
            [],
            ['candidate_id' => $candidate->id, 'company' => $data['company_name']]
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'interviews'])
            ->with('success', 'Interview added.');
    }

    public function update(Request $request, Candidate $candidate, Interview $interview, DailyLogSummaryService $dailyLogSummaryService)
    {
        $user    = $request->user();
        $isAdmin = $user->isAdmin();

        abort_unless((int) $interview->candidate_id === (int) $candidate->id, 404);

        // Only admin can edit after creation
        if (!$isAdmin) {
            return back()->withErrors(['auth' => 'Only admin can edit interviews.']);
        }

        $previousDate = $dailyLogSummaryService->interviewLogDate($interview);

        $data = $request->validate([
            'application_date'   => ['required', 'date'],
            'application_time'   => ['required', 'date_format:H:i'],
            'role'               => ['required', 'string', 'max:200'],
            'company_name'       => ['required', 'string', 'max:200'],
            'company_domain'     => ['required', 'string', 'max:500'],
            'mail_date'          => ['nullable', 'date'],
            'mail_time'          => ['nullable', 'date_format:H:i'],
            'interview_type'     => ['required', Rule::in(self::TYPES)],
            'remark'             => ['nullable', 'string'],
            'scheduled_date'     => ['nullable', 'date'],
            'scheduled_time'     => ['nullable', 'date_format:H:i'],
            'scheduled_timezone' => ['nullable', Rule::in(self::TIMEZONES)],
            'interview_status'   => ['nullable', Rule::in(['valid', 'invalid', ''])],
        ]);

        // Normalise company_domain — prepend https:// if no scheme given
        if (!empty($data['company_domain']) && !preg_match('#^https?://#i', $data['company_domain'])) {
            $data['company_domain'] = 'https://' . $data['company_domain'];
        }

        $data = $this->normalizeInterviewDateTimes($data);
        if (($data['interview_status'] ?? null) === '') {
            $data['interview_status'] = null;
        }

        $interview->update($data);
        $interview->refresh();

        $dailyLogSummaryService->syncInterview($interview, $previousDate, $user->id);

        AuditLog::log(
            'updated',
            'interviews',
            "Updated interview for {$candidate->full_name}",
            [],
            ['interview_id' => $interview->id, 'company' => $data['company_name']]
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'interviews'])
            ->with('success', 'Interview updated.');
    }

    public function destroy(Request $request, Candidate $candidate, Interview $interview, DailyLogSummaryService $dailyLogSummaryService)
    {
        $user = $request->user();
        abort_unless((int) $interview->candidate_id === (int) $candidate->id, 404);

        if (!$user->isAdmin()) {
            return back()->withErrors(['auth' => 'Only admin can delete interviews.']);
        }

        $previousDate = $dailyLogSummaryService->interviewLogDate($interview);
        $interviewId = $interview->id;
        $interview->delete();

        if ($previousDate) {
            $dailyLogSummaryService->syncForCandidateDate($candidate, $previousDate, $user->id);
        }

        AuditLog::log(
            'deleted',
            'interviews',
            "Deleted interview for {$candidate->full_name}",
            ['interview_id' => $interviewId],
            []
        );

        return redirect()->route('admin.candidates.show', [$candidate, 'tab' => 'interviews'])
            ->with('success', 'Interview deleted.');
    }

    private function normalizeInterviewDateTimes(array $data): array
    {
        foreach ([
            'application_date',
            'application_time',
            'mail_date',
            'mail_time',
            'scheduled_date',
            'scheduled_time',
            'scheduled_timezone',
        ] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '') {
                $data[$field] = null;
            }
        }

        foreach (['application_time', 'mail_time', 'scheduled_time'] as $timeField) {
            if (!empty($data[$timeField])) {
                $data[$timeField] = Carbon::createFromFormat('H:i', substr((string) $data[$timeField], 0, 5))
                    ->format('H:i');
            }
        }

        return $data;
    }
}
